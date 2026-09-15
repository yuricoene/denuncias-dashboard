<?php
declare(strict_types=1);
require __DIR__ . '/../includes/data.php';
require __DIR__ . '/../includes/auth.php';
auth_exigir_login();

$id = (int) ($_GET['id'] ?? 0);
$denuncia = denuncia_por_id($pdo, $id);
if (!$denuncia) {
    http_response_code(404);
    die('Denúncia não encontrada.');
}

$mensagem = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = (string) ($_POST['acao'] ?? '');

    if ($acao === 'status') {
        $novoStatus = (string) ($_POST['status'] ?? '');
        if (denuncia_atualizar_status($pdo, $id, $novoStatus)) {
            $mensagem = 'Status atualizado para "' . $novoStatus . '".';
        } else {
            $mensagem = 'Essa transição de status não é permitida a partir de "' . $denuncia['status'] . '".';
        }
    } elseif ($acao === 'classificacao') {
        $tipo = (string) ($_POST['tipo'] ?? $denuncia['tipo']);
        $gravidade = (string) ($_POST['gravidade'] ?? '');
        denuncia_atualizar_classificacao($pdo, $id, $tipo, $gravidade !== '' ? $gravidade : null);
        $mensagem = 'Classificação atualizada.';
    }

    $denuncia = denuncia_por_id($pdo, $id);
}

$historico = denuncia_historico($pdo, $id);
$evidencias = denuncia_evidencias($pdo, $id);
$proximosStatus = TRANSICOES_PERMITIDAS[$denuncia['status']] ?? [];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($denuncia['protocolo']) ?> — Canal de Denúncias</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Source+Serif+4:opsz,wght@8..60,400;8..60,600&family=IBM+Plex+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<header class="topo">
  <div class="topo__texto">
    <h1><a href="painel.php" class="topo__voltar">← Painel</a></h1>
    <p class="mono"><?= htmlspecialchars($denuncia['protocolo']) ?></p>
  </div>
  <div class="topo__periodo">
    <span>Logado como</span>
    <strong><?= htmlspecialchars($_SESSION['analista_nome']) ?></strong>
    <a href="logout.php" class="topo__sair">Sair</a>
  </div>
</header>

<main>

  <?php if ($mensagem): ?>
  <div class="alerta"><p><?= htmlspecialchars($mensagem) ?></p></div>
  <?php endif; ?>

  <section class="detalhe">
    <div class="detalhe__coluna-principal">

      <div class="painel">
        <h2>Relato</h2>
        <p class="detalhe__relato"><?= nl2br(htmlspecialchars($denuncia['relato'])) ?></p>
        <dl class="detalhe__meta">
          <div><dt>Tipo</dt><dd><?= htmlspecialchars($denuncia['tipo']) ?></dd></div>
          <div><dt>Local</dt><dd><?= $denuncia['local'] ? htmlspecialchars($denuncia['local']) : '—' ?></dd></div>
          <div><dt>Data aproximada</dt><dd><?= $denuncia['data_ocorrencia'] ? (new DateTime($denuncia['data_ocorrencia']))->format('d/m/Y') : '—' ?></dd></div>
          <div><dt>Recebida em</dt><dd><?= (new DateTime($denuncia['criado_em']))->format('d/m/Y H:i') ?></dd></div>
          <div><dt>Gravidade</dt><dd><?= $denuncia['gravidade'] ? htmlspecialchars($denuncia['gravidade']) : 'Não classificada' ?></dd></div>
        </dl>
      </div>

      <?php if (!empty($evidencias)): ?>
      <div class="painel">
        <h2>Evidências anexadas</h2>
        <ul class="evidencias__lista">
          <?php foreach ($evidencias as $ev): ?>
          <li>
            <?php if ($ev['tipo_arquivo'] === 'application/pdf'): ?>
              <a href="../<?= htmlspecialchars($ev['caminho_arquivo']) ?>" target="_blank" rel="noopener">Documento PDF</a>
            <?php else: ?>
              <a href="../<?= htmlspecialchars($ev['caminho_arquivo']) ?>" target="_blank" rel="noopener">
                <img src="../<?= htmlspecialchars($ev['caminho_arquivo']) ?>" alt="Evidência anexada" class="evidencias__miniatura">
              </a>
            <?php endif; ?>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>

      <div class="painel">
        <h2>Histórico</h2>
        <ol class="timeline">
          <?php foreach ($historico as $h): ?>
          <li class="timeline__item">
            <span class="timeline__data"><?= (new DateTime($h['alterado_em']))->format('d/m/Y H:i') ?></span>
            <span class="timeline__texto">
              <?php if ($h['status_anterior'] === null): ?>
                Denúncia recebida
              <?php else: ?>
                <?= htmlspecialchars($h['status_anterior']) ?> → <?= htmlspecialchars($h['status_novo']) ?>
              <?php endif; ?>
            </span>
          </li>
          <?php endforeach; ?>
        </ol>
      </div>

    </div>

    <div class="detalhe__coluna-lateral">

      <div class="painel">
        <h2>Status atual</h2>
        <p><span class="badge badge--status-<?= str_replace(' ', '-', mb_strtolower($denuncia['status'])) ?>"><?= htmlspecialchars($denuncia['status']) ?></span></p>

        <?php if (!empty($proximosStatus)): ?>
        <form method="post" class="form">
          <input type="hidden" name="acao" value="status">
          <div class="form__campo">
            <label for="status">Mover para</label>
            <select id="status" name="status">
              <?php foreach ($proximosStatus as $s): ?>
              <option value="<?= htmlspecialchars($s) ?>"><?= htmlspecialchars($s) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <button type="submit" class="btn btn--primario">Atualizar status</button>
        </form>
        <?php else: ?>
        <p class="detalhe__nota">Este é um status final — sem novas transições.</p>
        <?php endif; ?>
      </div>

      <div class="painel">
        <h2>Classificação</h2>
        <form method="post" class="form">
          <input type="hidden" name="acao" value="classificacao">
          <div class="form__campo">
            <label for="tipo_class">Tipo</label>
            <select id="tipo_class" name="tipo">
              <?php foreach (TIPOS as $t): ?>
              <option value="<?= htmlspecialchars($t) ?>" <?= $denuncia['tipo'] === $t ? 'selected' : '' ?>><?= htmlspecialchars($t) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form__campo">
            <label for="gravidade">Gravidade</label>
            <select id="gravidade" name="gravidade">
              <option value="">Não classificada</option>
              <?php foreach (GRAVIDADES as $g): ?>
              <option value="<?= htmlspecialchars($g) ?>" <?= $denuncia['gravidade'] === $g ? 'selected' : '' ?>><?= htmlspecialchars($g) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <button type="submit" class="btn">Salvar classificação</button>
        </form>
      </div>

    </div>
  </section>

</main>

<footer class="rodape-pagina">
  <p>Nenhum dado do denunciante é exibido aqui — só o protocolo o vincula ao caso, e só ele sabe qual é.</p>
</footer>
</body>
</html>
