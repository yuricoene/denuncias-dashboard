<?php
declare(strict_types=1);
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/auth.php';

auth_iniciar_sessao();
if (auth_logado()) {
    header('Location: painel.php');
    exit;
}

$erro = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $senha = (string) ($_POST['senha'] ?? '');

    if (auth_tentar_login($pdo, $email, $senha)) {
        header('Location: painel.php');
        exit;
    }
    $erro = 'E-mail ou senha inválidos.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — Compliance</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Source+Serif+4:opsz,wght@8..60,400;8..60,600&family=IBM+Plex+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<header class="topo">
  <div class="topo__texto">
    <h1><a href="../index.php" class="topo__voltar">Canal de Denúncias</a></h1>
    <p>Acesso restrito à equipe de RH/Compliance.</p>
  </div>
</header>

<main class="landing">
  <?php if ($erro): ?>
  <div class="alerta alerta--erro"><p><?= htmlspecialchars($erro) ?></p></div>
  <?php endif; ?>

  <form class="form login-box" method="post">
    <div class="form__campo">
      <label for="email">E-mail</label>
      <input type="email" id="email" name="email" required autofocus>
    </div>
    <div class="form__campo">
      <label for="senha">Senha</label>
      <input type="password" id="senha" name="senha" required>
    </div>
    <button type="submit" class="btn btn--primario">Entrar</button>
  </form>
</main>

<footer class="rodape-pagina">
  <p>Use database/seed.php uma vez para criar o usuário de demonstração.</p>
</footer>
</body>
</html>
