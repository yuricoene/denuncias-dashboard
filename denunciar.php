<?php
declare(strict_types=1);
require __DIR__ . '/includes/data.php';

$erros = [];
$protocoloGerado = null;

$tipo = '';
$relato = '';
$local = '';
$dataOcorrencia = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = trim((string) ($_POST['tipo'] ?? ''));
    $relato = trim((string) ($_POST['relato'] ?? ''));
    $local = trim((string) ($_POST['local'] ?? ''));
    $dataOcorrencia = trim((string) ($_POST['data_ocorrencia'] ?? ''));

    if (!in_array($tipo, TIPOS, true)) {
        $erros[] = 'Selecione o tipo da denúncia.';
    }
    if (mb_strlen($relato) < 20) {
        $erros[] = 'Descreva o ocorrido com um pouco mais de detalhe (mínimo 20 caracteres).';
    }
    if ($dataOcorrencia !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataOcorrencia)) {
        $erros[] = 'Data da ocorrência inválida.';
    }

    // RF04 / RN06: evidencias opcionais, limitadas a jpg/png/pdf, 5MB cada.
    $extensoesPermitidas = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'pdf' => 'application/pdf'];
    $tamanhoMaximo = 5 * 1024 * 1024;
    $arquivosValidados = [];

    if (!empty($_FILES['evidencias']['name'][0] ?? '')) {
        $qtd = count($_FILES['evidencias']['name']);
        for ($i = 0; $i < $qtd; $i++) {
            if ($_FILES['evidencias']['error'][$i] === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if ($_FILES['evidencias']['error'][$i] !== UPLOAD_ERR_OK) {
                $erros[] = 'Não foi possível enviar um dos arquivos.';
                continue;
            }
            $nomeOriginal = (string) $_FILES['evidencias']['name'][$i];
            $ext = strtolower(pathinfo($nomeOriginal, PATHINFO_EXTENSION));
            if (!isset($extensoesPermitidas[$ext])) {
                $erros[] = "Arquivo \"{$nomeOriginal}\" tem um formato não permitido (use jpg, png ou pdf).";
                continue;
            }
            if ($_FILES['evidencias']['size'][$i] > $tamanhoMaximo) {
                $erros[] = "Arquivo \"{$nomeOriginal}\" excede o limite de 5MB.";
                continue;
            }
            $arquivosValidados[] = [
                'tmp_name' => $_FILES['evidencias']['tmp_name'][$i],
                'ext' => $ext,
                'tipo' => $extensoesPermitidas[$ext],
            ];
        }
    }

    if (empty($erros)) {
        $pastaDestino = __DIR__ . '/uploads/evidencias';
        if (!is_dir($pastaDestino)) {
            mkdir($pastaDestino, 0755, true);
        }

        $arquivosSalvos = [];
        foreach ($arquivosValidados as $arq) {
            // nome aleatorio no disco -- nao guarda o nome original do arquivo
            $nomeArquivo = bin2hex(random_bytes(16)) . '.' . $arq['ext'];
            $destino = $pastaDestino . '/' . $nomeArquivo;
            if (move_uploaded_file($arq['tmp_name'], $destino)) {
                $arquivosSalvos[] = ['caminho' => 'uploads/evidencias/' . $nomeArquivo, 'tipo' => $arq['tipo']];
            }
        }

        $protocoloGerado = denuncia_criar($pdo, [
            'tipo' => $tipo,
            'relato' => $relato,
            'local' => $local,
            'data_ocorrencia' => $dataOcorrencia,
        ], $arquivosSalvos);
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Registrar denúncia — Canal de Denúncias</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Source+Serif+4:opsz,wght@8..60,400;8..60,600&family=IBM+Plex+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css">
</head>
<body>

<header class="topo">
  <div class="topo__texto">
    <h1><a href="index.php" class="topo__voltar">Canal de Denúncias</a></h1>
    <p>Nenhum campo abaixo identifica você. Ao enviar, você recebe um protocolo — guarde-o.</p>
  </div>
</header>

<main class="landing">

<?php if ($protocoloGerado !== null): ?>
  <section class="cartao-confirmacao">
    <span class="cartao-confirmacao__rotulo">Denúncia registrada</span>
    <p class="cartao-confirmacao__protocolo"><?= htmlspecialchars($protocoloGerado) ?></p>
    <p class="cartao-confirmacao__aviso">
      Guarde este número — ele é a <strong>única</strong> forma de acompanhar o andamento do caso.
      Não é possível recuperá-lo depois, pois não vinculamos a denúncia a nenhum dado seu.
    </p>
    <div class="cartao-confirmacao__acoes">
      <a class="btn btn--primario" href="consultar.php?protocolo=<?= urlencode($protocoloGerado) ?>">Consultar este protocolo</a>
      <a class="btn" href="index.php">Voltar ao início</a>
    </div>
  </section>

<?php else: ?>

  <?php if (!empty($erros)): ?>
  <div class="alerta alerta--erro">
    <ul>
      <?php foreach ($erros as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
    </ul>
  </div>
  <?php endif; ?>

  <form class="form" method="post" enctype="multipart/form-data" novalidate>
    <div class="form__campo">
      <label for="tipo">Tipo da denúncia</label>
      <select id="tipo" name="tipo" required>
        <option value="">Selecione…</option>
        <?php foreach (TIPOS as $t): ?>
        <option value="<?= htmlspecialchars($t) ?>" <?= $tipo === $t ? 'selected' : '' ?>><?= htmlspecialchars($t) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form__campo">
      <label for="relato">Relato do ocorrido</label>
      <textarea id="relato" name="relato" rows="7" required placeholder="Descreva o que aconteceu, com o máximo de detalhes que se sentir confortável em compartilhar."><?= htmlspecialchars($relato) ?></textarea>
    </div>

    <div class="form__linha">
      <div class="form__campo">
        <label for="local">Local (opcional)</label>
        <input type="text" id="local" name="local" value="<?= htmlspecialchars($local) ?>" placeholder="Ex.: Setor de Produção">
      </div>
      <div class="form__campo">
        <label for="data_ocorrencia">Data aproximada (opcional)</label>
        <input type="date" id="data_ocorrencia" name="data_ocorrencia" value="<?= htmlspecialchars($dataOcorrencia) ?>">
      </div>
    </div>

    <div class="form__campo">
      <label for="evidencias">Evidências (opcional — imagem ou PDF, até 5MB cada)</label>
      <input type="file" id="evidencias" name="evidencias[]" accept=".jpg,.jpeg,.png,.pdf" multiple>
    </div>

    <button type="submit" class="btn btn--primario">Enviar denúncia</button>
  </form>

<?php endif; ?>

</main>

<footer class="rodape-pagina">
  <p>Este formulário não registra seu nome, e-mail, IP ou qualquer identificador.</p>
</footer>
</body>
</html>
