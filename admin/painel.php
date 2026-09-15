<?php
declare(strict_types=1);
require __DIR__ . '/../includes/data.php';
require __DIR__ . '/../includes/auth.php';
auth_exigir_login();

$resumo = denuncias_resumo($pdo);
$total  = max($resumo['total'], 1);

$coresTipo = [
    'Assédio moral'  => '#24405C',
    'Assédio sexual' => '#7A3030',
    'Outros'         => '#B8813C',
];
$coresStatus = [
    'Recebida'        => '#6B7A87',
    'Em análise'      => '#B8813C',
    'Em investigação' => '#24405C',
    'Concluída'       => '#4B6A53',
    'Arquivada'       => '#8C6A5A',
];

// ---- geometria do anel de status (SVG stroke-dasharray) ----
$raio = 70;
$circunf = 2 * M_PI * $raio;
$offsetAcumulado = 0;
$segmentosAnel = [];
foreach (STATUS_ORDEM as $status) {
    $qtd = $resumo['por_status'][$status];
    $fracao = $qtd / $total;
    $comprimento = $fracao * $circunf;
    $segmentosAnel[] = [
        'status' => $status,
        'qtd' => $qtd,
        'cor' => $coresStatus[$status],
        'dasharray' => sprintf('%.2f %.2f', $comprimento, $circunf - $comprimento),
        'offset' => sprintf('%.2f', -$offsetAcumulado),
    ];
    $offsetAcumulado += $comprimento;
}

// ---- geometria da linha de evolução ----
$meses = array_keys($resumo['por_mes']);
$valores = array_values($resumo['por_mes']);
$maxValor = max($valores) ?: 1;
$larguraChart = 640;
$alturaChart = 180;
$passoX = $larguraChart / (count($meses) - 1);
$pontos = [];
foreach ($valores as $i => $v) {
    $x = $i * $passoX;
    $y = $alturaChart - ($v / $maxValor) * ($alturaChart - 20) - 10;
    $pontos[] = sprintf('%.1f,%.1f', $x, $y);
}
$linhaPoints = implode(' ', $pontos);
$areaPoints = $linhaPoints . sprintf(' %.1f,%.1f 0,%.1f', $larguraChart, $alturaChart, $alturaChart);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Painel — Canal de Denúncias</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Source+Serif+4:opsz,wght@8..60,400;8..60,600&family=IBM+Plex+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<header class="topo">
  <div class="topo__texto">
    <h1>Canal de Denúncias</h1>
    <p>Painel da equipe de Compliance — acompanhamento das manifestações recebidas.</p>
  </div>
  <div class="topo__periodo">
    <span>Logado como</span>
    <strong><?= htmlspecialchars($_SESSION['analista_nome']) ?></strong>
    <a href="logout.php" class="topo__sair">Sair</a>
  </div>
</header>

<main>

  <?php if ($resumo['total'] === 0): ?>
  <p class="landing__aviso" style="margin-top:32px;">Nenhuma denúncia registrada ainda. Assim que alguém enviar uma pelo canal público, ela aparece aqui.</p>
  <?php endif; ?>

  <section class="tira-stats" aria-label="Resumo geral">
    <div class="stat">
      <strong><?= $resumo['total'] ?></strong>
      <span>Total</span>
    </div>
    <?php foreach (STATUS_ORDEM as $status): ?>
    <div class="stat">
      <strong><?= $resumo['por_status'][$status] ?></strong>
      <span><?= htmlspecialchars($status) ?></span>
    </div>
    <?php endforeach; ?>
  </section>

  <section class="grade-dupla">

    <div class="painel painel--tipo">
      <h2>Denúncias por tipo</h2>
      <ul class="lista-barras">
        <?php foreach ($resumo['por_tipo'] as $tipo => $qtd):
          $pct = round(($qtd / $total) * 100); ?>
        <li>
          <div class="lista-barras__cabecalho">
            <span><?= htmlspecialchars($tipo) ?></span>
            <span class="lista-barras__pct"><?= $pct ?>%</span>
          </div>
          <div class="lista-barras__trilho">
            <div class="lista-barras__preenchido" style="width: <?= $pct ?>%; background: <?= $coresTipo[$tipo] ?>;"></div>
          </div>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>

    <div class="painel painel--status">
      <h2>Status das denúncias</h2>
      <div class="anel-wrap">
        <svg viewBox="0 0 180 180" class="anel" role="img" aria-label="Distribuição por status">
          <circle cx="90" cy="90" r="<?= $raio ?>" class="anel__base" />
          <?php foreach ($segmentosAnel as $seg): ?>
          <circle cx="90" cy="90" r="<?= $raio ?>"
                  stroke="<?= $seg['cor'] ?>"
                  stroke-dasharray="<?= $seg['dasharray'] ?>"
                  stroke-dashoffset="<?= $seg['offset'] ?>"
                  class="anel__segmento" />
          <?php endforeach; ?>
          <text x="90" y="85" text-anchor="middle" class="anel__numero"><?= $resumo['total'] ?></text>
          <text x="90" y="103" text-anchor="middle" class="anel__legenda-central">no total</text>
        </svg>
        <ul class="legenda-status">
          <?php foreach ($segmentosAnel as $seg): ?>
          <li>
            <span class="ponto" style="background: <?= $seg['cor'] ?>;"></span>
            <span><?= htmlspecialchars($seg['status']) ?></span>
            <strong><?= $seg['qtd'] ?></strong>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>

  </section>

  <section class="painel painel--evolucao">
    <h2>Evolução das denúncias</h2>
    <svg viewBox="0 0 <?= $larguraChart ?> <?= $alturaChart + 24 ?>" class="grafico-linha" preserveAspectRatio="none" role="img" aria-label="Evolução mensal">
      <defs>
        <linearGradient id="areaFill" x1="0" y1="0" x2="0" y2="1">
          <stop offset="0%" stop-color="#24405C" stop-opacity="0.16" />
          <stop offset="100%" stop-color="#24405C" stop-opacity="0" />
        </linearGradient>
      </defs>
      <polygon points="<?= $areaPoints ?>" fill="url(#areaFill)" />
      <polyline points="<?= $linhaPoints ?>" class="grafico-linha__traco" />
      <?php foreach ($pontos as $p): [$x, $y] = explode(',', $p); ?>
      <circle cx="<?= $x ?>" cy="<?= $y ?>" r="3.5" class="grafico-linha__ponto" />
      <?php endforeach; ?>
    </svg>
    <div class="grafico-linha__eixo">
      <?php foreach ($meses as $m): ?><span><?= $m ?></span><?php endforeach; ?>
    </div>
  </section>

  <section class="painel painel--busca">
    <div class="busca__cabecalho">
      <h2>Todas as denúncias</h2>
      <div class="busca__controles">
        <input type="search" id="campoBusca" placeholder="Buscar por número do protocolo" aria-label="Buscar por número do protocolo">
        <select id="filtroTipo" aria-label="Filtrar por tipo">
          <option value="">Todos os tipos</option>
          <?php foreach (TIPOS as $t): ?>
          <option value="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars($t) ?></option>
          <?php endforeach; ?>
        </select>
        <select id="filtroStatus" aria-label="Filtrar por status">
          <option value="">Todos os status</option>
          <?php foreach (STATUS_ORDEM as $s): ?>
          <option value="<?= htmlspecialchars($s) ?>"><?= htmlspecialchars($s) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <table class="tabela-protocolos">
      <thead>
        <tr>
          <th>Protocolo</th>
          <th>Tipo</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody id="corpoTabela"></tbody>
    </table>
    <p class="busca__rodape" id="rodapeBusca">Carregando…</p>
  </section>

</main>

<footer class="rodape-pagina">
  <p>As identidades das pessoas denunciantes não são exibidas neste painel — elas nunca são coletadas.</p>
</footer>

<script src="../assets/script.js"></script>
</body>
</html>
