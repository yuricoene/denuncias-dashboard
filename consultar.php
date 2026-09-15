<?php
declare(strict_types=1);
require __DIR__ . '/includes/data.php';

$protocolo = trim((string) ($_GET['protocolo'] ?? ($_POST['protocolo'] ?? '')));
$denuncia = null;
$historico = [];
$naoEncontrada = false;

if ($protocolo !== '') {
    $denuncia = denuncia_por_protocolo($pdo, $protocolo);
    if ($denuncia) {
        $historico = denuncia_historico($pdo, (int) $denuncia['id']);
    } else {
        $naoEncontrada = true;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Consultar protocolo — Canal de Denúncias</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Source+Serif+4:opsz,wght@8..60,400;8..60,600&family=IBM+Plex+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css">
</head>
<body>

<header class="topo">
  <div class="topo__texto">
    <h1><a href="index.php" class="topo__voltar">Canal de Denúncias</a></h1>
    <p>Informe o protocolo recebido no envio para ver a situação atual do caso.</p>
  </div>
</header>

<main class="landing">

  <form class="form form--consulta" method="get">
    <div class="form__campo">
      <label for="protocolo">Número do protocolo</label>
      <input type="text" id="protocolo" name="protocolo" value="<?= htmlspecialchars($protocolo) ?>" placeholder="DEN-XXXXXXXX" required>
    </div>
    <button type="submit" class="btn btn--primario">Consultar</button>
  </form>

  <?php if ($naoEncontrada): ?>
  <div class="alerta alerta--erro">
    <p>Nenhuma denúncia encontrada com esse protocolo. Confira se digitou corretamente.</p>
  </div>
  <?php endif; ?>

  <?php if ($denuncia): ?>
  <section class="cartao-status">
    <div class="cartao-status__cabecalho">
      <span class="cartao-status__protocolo mono"><?= htmlspecialchars($denuncia['protocolo']) ?></span>
      <span class="badge badge--status-<?= str_replace(' ', '-', mb_strtolower($denuncia['status'])) ?>"><?= htmlspecialchars($denuncia['status']) ?></span>
    </div>
    <p class="cartao-status__meta">Tipo: <?= htmlspecialchars($denuncia['tipo']) ?> · Registrada em <?= (new DateTime($denuncia['criado_em']))->format('d/m/Y') ?></p>

    <ol class="timeline">
      <?php foreach ($historico as $h): ?>
      <li class="timeline__item">
        <span class="timeline__data"><?= (new DateTime($h['alterado_em']))->format('d/m/Y \à\s H:i') ?></span>
        <span class="timeline__texto">
          <?php if ($h['status_anterior'] === null): ?>
            Denúncia recebida
          <?php else: ?>
            Status alterado de <strong><?= htmlspecialchars($h['status_anterior']) ?></strong> para <strong><?= htmlspecialchars($h['status_novo']) ?></strong>
          <?php endif; ?>
        </span>
      </li>
      <?php endforeach; ?>
    </ol>
  </section>
  <?php endif; ?>

</main>

<footer class="rodape-pagina">
  <p>Somente quem possui o protocolo consegue ver o status deste caso.</p>
</footer>
</body>
</html>
