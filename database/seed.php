<?php
declare(strict_types=1);

/**
 * Script de uso unico. Rode pelo navegador (http://localhost/.../database/seed.php)
 * ou via CLI (php database/seed.php) depois de importar schema.sql.
 * Cria o usuario analista de demonstracao e algumas denuncias de exemplo,
 * so se o banco ainda estiver vazio.
 */

require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/protocolo.php';

header('Content-Type: text/plain; charset=utf-8');

$emailPadrao = 'compliance@empresa.com';
$senhaPadrao = 'compliance123';

$existe = $pdo->prepare('SELECT COUNT(*) FROM usuarios_analistas WHERE email = :email');
$existe->execute(['email' => $emailPadrao]);

if ((int) $existe->fetchColumn() === 0) {
    $hash = password_hash($senhaPadrao, PASSWORD_DEFAULT);
    $pdo->prepare('INSERT INTO usuarios_analistas (nome, email, senha_hash, perfil) VALUES (:nome, :email, :hash, "Analista")')
        ->execute(['nome' => 'Equipe de Compliance', 'email' => $emailPadrao, 'hash' => $hash]);
    echo "Usuario analista criado: {$emailPadrao} / {$senhaPadrao}\n";
} else {
    echo "Usuario analista ja existe ({$emailPadrao}).\n";
}

$totalDenuncias = (int) $pdo->query('SELECT COUNT(*) FROM denuncias')->fetchColumn();
if ($totalDenuncias === 0) {
    $exemplos = [
        ['tipo' => 'Assédio moral', 'relato' => 'Recebo cobrancas humilhantes na frente de outros colaboradores durante as reunioes semanais.', 'local' => 'Setor de Producao', 'dias_atras' => 40, 'status_final' => 'Em investigação'],
        ['tipo' => 'Assédio sexual', 'relato' => 'Um colega insiste em comentarios de cunho sexual mesmo apos eu pedir para parar.', 'local' => 'Almoxarifado', 'dias_atras' => 25, 'status_final' => 'Em análise'],
        ['tipo' => 'Assédio moral', 'relato' => 'Fui isolado das decisoes da equipe depois de reportar uma falha de seguranca no setor.', 'local' => 'Manutencao', 'dias_atras' => 60, 'status_final' => 'Concluída'],
        ['tipo' => 'Outros', 'relato' => 'Testemunhei favoritismo constante na distribuicao de horas extras entre colegas.', 'local' => 'Logistica', 'dias_atras' => 3, 'status_final' => 'Recebida'],
    ];

    $caminhoAtual = ['Recebida', 'Em análise', 'Em investigação', 'Concluída'];

    foreach ($exemplos as $ex) {
        $protocolo = gerar_protocolo($pdo);
        $criadoEm = date('Y-m-d H:i:s', strtotime("-{$ex['dias_atras']} days"));

        $pdo->prepare(
            'INSERT INTO denuncias (protocolo, tipo, relato, local, status, criado_em) VALUES (:protocolo, :tipo, :relato, :local, :status, :criado_em)'
        )->execute([
            'protocolo' => $protocolo,
            'tipo' => $ex['tipo'],
            'relato' => $ex['relato'],
            'local' => $ex['local'],
            'status' => $ex['status_final'],
            'criado_em' => $criadoEm,
        ]);
        $id = (int) $pdo->lastInsertId();

        // monta um historico plausivel ate o status final do exemplo
        $anterior = null;
        $quando = $criadoEm;
        foreach ($caminhoAtual as $passo) {
            $pdo->prepare('INSERT INTO historico_status (denuncia_id, status_anterior, status_novo, alterado_em) VALUES (:id, :de, :para, :quando)')
                ->execute(['id' => $id, 'de' => $anterior, 'para' => $passo, 'quando' => $quando]);
            if ($passo === $ex['status_final']) {
                break;
            }
            $anterior = $passo;
            $quando = date('Y-m-d H:i:s', strtotime($quando . ' +3 days'));
        }
    }

    echo "4 denuncias de exemplo criadas.\n";
} else {
    echo "Ja existem denuncias no banco -- nada foi adicionado.\n";
}

echo "Pronto.\n";
