<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/protocolo.php';

// RN05: fluxo oficial de status.
const STATUS_ORDEM = ['Recebida', 'Em análise', 'Em investigação', 'Concluída', 'Arquivada'];

const TIPOS = ['Assédio moral', 'Assédio sexual', 'Outros'];

const GRAVIDADES = ['Baixa', 'Média', 'Alta'];

// RN05: "o fluxo de status segue a ordem [...]" -- cada status so pode
// avancar para os proximos da lista, ou ser arquivado a qualquer momento.
// Isso e checado em denuncia_atualizar_status().
const TRANSICOES_PERMITIDAS = [
    'Recebida'         => ['Em análise', 'Arquivada'],
    'Em análise'       => ['Em investigação', 'Arquivada'],
    'Em investigação'  => ['Concluída', 'Arquivada'],
    'Concluída'        => [],
    'Arquivada'        => [],
];

/** RF10: indicadores agregados para o painel (contagem por status/tipo/mes). */
function denuncias_resumo(PDO $pdo): array
{
    $total = (int) $pdo->query('SELECT COUNT(*) FROM denuncias')->fetchColumn();

    $porStatus = array_fill_keys(STATUS_ORDEM, 0);
    foreach ($pdo->query('SELECT status, COUNT(*) AS qtd FROM denuncias GROUP BY status') as $row) {
        $porStatus[$row['status']] = (int) $row['qtd'];
    }

    $porTipo = array_fill_keys(TIPOS, 0);
    foreach ($pdo->query('SELECT tipo, COUNT(*) AS qtd FROM denuncias GROUP BY tipo') as $row) {
        $porTipo[$row['tipo']] = (int) $row['qtd'];
    }

    $stmt = $pdo->prepare(
        "SELECT DATE_FORMAT(criado_em, '%Y-%m') AS chave, COUNT(*) AS qtd
         FROM denuncias
         WHERE criado_em >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
         GROUP BY chave"
    );
    $stmt->execute();
    $porMesBanco = [];
    foreach ($stmt as $row) {
        $porMesBanco[$row['chave']] = (int) $row['qtd'];
    }

    $nomesMes = ['01' => 'Jan', '02' => 'Fev', '03' => 'Mar', '04' => 'Abr', '05' => 'Mai', '06' => 'Jun',
                 '07' => 'Jul', '08' => 'Ago', '09' => 'Set', '10' => 'Out', '11' => 'Nov', '12' => 'Dez'];
    $porMes = [];
    for ($i = 5; $i >= 0; $i--) {
        $chave = date('Y-m', strtotime("-{$i} months"));
        $mesNum = substr($chave, 5, 2);
        $porMes[$nomesMes[$mesNum]] = $porMesBanco[$chave] ?? 0;
    }

    return ['total' => $total, 'por_status' => $porStatus, 'por_tipo' => $porTipo, 'por_mes' => $porMes];
}

/** RF07: listar/filtrar denuncias (usado pelo painel e pela api.php). */
function denuncias_buscar(PDO $pdo, string $termo = '', string $tipo = '', string $status = '', int $limite = 20, int $offset = 0): array
{
    $where = ' WHERE 1=1';
    $params = [];

    if ($termo !== '') {
        $where .= ' AND protocolo LIKE :termo';
        $params['termo'] = '%' . strtoupper($termo) . '%';
    }
    if ($tipo !== '' && in_array($tipo, TIPOS, true)) {
        $where .= ' AND tipo = :tipo';
        $params['tipo'] = $tipo;
    }
    if ($status !== '' && in_array($status, STATUS_ORDEM, true)) {
        $where .= ' AND status = :status';
        $params['status'] = $status;
    }

    $totalStmt = $pdo->prepare('SELECT COUNT(*) FROM denuncias' . $where);
    $totalStmt->execute($params);
    $total = (int) $totalStmt->fetchColumn();

    $stmt = $pdo->prepare(
        'SELECT id, protocolo, tipo, status, criado_em FROM denuncias' . $where .
        ' ORDER BY criado_em DESC LIMIT :limite OFFSET :offset'
    );
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue('limite', $limite, PDO::PARAM_INT);
    $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    return ['total' => $total, 'resultados' => $stmt->fetchAll()];
}

/** RF05: consulta publica de status por protocolo. */
function denuncia_por_protocolo(PDO $pdo, string $protocolo): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM denuncias WHERE protocolo = :p');
    $stmt->execute(['p' => strtoupper(trim($protocolo))]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function denuncia_por_id(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM denuncias WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/** RF09: historico de mudancas de status, sem dados do denunciante. */
function denuncia_historico(PDO $pdo, int $id): array
{
    $stmt = $pdo->prepare(
        'SELECT status_anterior, status_novo, alterado_em FROM historico_status
         WHERE denuncia_id = :id ORDER BY alterado_em ASC'
    );
    $stmt->execute(['id' => $id]);
    return $stmt->fetchAll();
}

function denuncia_evidencias(PDO $pdo, int $id): array
{
    $stmt = $pdo->prepare('SELECT caminho_arquivo, tipo_arquivo FROM evidencias WHERE denuncia_id = :id');
    $stmt->execute(['id' => $id]);
    return $stmt->fetchAll();
}

/**
 * RF08/RN03: so deve ser chamada depois de auth_exigir_login().
 * RN05: aplica a maquina de estados de TRANSICOES_PERMITIDAS.
 * Retorna true se mudou, false se a transicao nao era permitida.
 */
function denuncia_atualizar_status(PDO $pdo, int $id, string $novoStatus): bool
{
    $atual = denuncia_por_id($pdo, $id);
    if (!$atual) {
        return false;
    }
    if ($atual['status'] === $novoStatus) {
        return false;
    }
    if (!in_array($novoStatus, TRANSICOES_PERMITIDAS[$atual['status']] ?? [], true)) {
        return false;
    }

    $pdo->beginTransaction();
    $pdo->prepare('UPDATE denuncias SET status = :status WHERE id = :id')
        ->execute(['status' => $novoStatus, 'id' => $id]);
    $pdo->prepare('INSERT INTO historico_status (denuncia_id, status_anterior, status_novo) VALUES (:id, :de, :para)')
        ->execute(['id' => $id, 'de' => $atual['status'], 'para' => $novoStatus]);
    $pdo->commit();

    return true;
}

/** RF08: classificacao (tipo/gravidade) feita pelo analista. */
function denuncia_atualizar_classificacao(PDO $pdo, int $id, string $tipo, ?string $gravidade): void
{
    if (!in_array($tipo, TIPOS, true)) {
        return;
    }
    if ($gravidade !== null && !in_array($gravidade, GRAVIDADES, true)) {
        $gravidade = null;
    }
    $stmt = $pdo->prepare('UPDATE denuncias SET tipo = :tipo, gravidade = :gravidade WHERE id = :id');
    $stmt->execute(['tipo' => $tipo, 'gravidade' => $gravidade, 'id' => $id]);
}

/** RF01/RF02/RF03: cria a denuncia publica, sem nenhum dado do denunciante. */
function denuncia_criar(PDO $pdo, array $dados, array $arquivos = []): string
{
    $protocolo = gerar_protocolo($pdo);

    $stmt = $pdo->prepare(
        'INSERT INTO denuncias (protocolo, tipo, relato, local, data_ocorrencia, status)
         VALUES (:protocolo, :tipo, :relato, :local, :data_ocorrencia, "Recebida")'
    );
    $stmt->execute([
        'protocolo'       => $protocolo,
        'tipo'            => $dados['tipo'],
        'relato'          => $dados['relato'],
        'local'           => $dados['local'] !== '' ? $dados['local'] : null,
        'data_ocorrencia' => $dados['data_ocorrencia'] !== '' ? $dados['data_ocorrencia'] : null,
    ]);
    $id = (int) $pdo->lastInsertId();

    $pdo->prepare('INSERT INTO historico_status (denuncia_id, status_anterior, status_novo) VALUES (:id, NULL, "Recebida")')
        ->execute(['id' => $id]);

    foreach ($arquivos as $arq) {
        $pdo->prepare('INSERT INTO evidencias (denuncia_id, caminho_arquivo, tipo_arquivo) VALUES (:id, :caminho, :tipo)')
            ->execute(['id' => $id, 'caminho' => $arq['caminho'], 'tipo' => $arq['tipo']]);
    }

    return $protocolo;
}
