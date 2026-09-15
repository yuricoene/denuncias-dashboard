<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Canal de Denúncias — Assédio Moral e Sexual</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Source+Serif+4:opsz,wght@8..60,400;8..60,600&family=IBM+Plex+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/denuncias-dashboard/assets/style.css?v=999"></head>
<body>

<header class="topo">
  <div class="topo__texto">
    <h1>Canal de Denúncias</h1>
    <p>Espaço seguro e anônimo para relatar situações de assédio moral ou sexual no ambiente de trabalho.</p>
  </div>
</header>

<main class="landing">
  <section class="landing__grade">
    <a class="landing__card" href="denunciar.php">
      <span class="landing__card-rotulo">Denunciante</span>
      <h2>Registrar uma denúncia</h2>
      <p>Relate o que aconteceu sem precisar se identificar. Você recebe um protocolo para acompanhar o caso.</p>
    </a>
    <a class="landing__card" href="consultar.php">
      <span class="landing__card-rotulo">Denunciante</span>
      <h2>Consultar status</h2>
      <p>Já registrou uma denúncia? Informe o protocolo para ver em que etapa ela está.</p>
    </a>
    <a class="landing__card landing__card--admin" href="admin/login.php">
      <span class="landing__card-rotulo">Equipe de Compliance</span>
      <h2>Acessar o painel</h2>
      <p>Login restrito à equipe de RH/Compliance para análise e acompanhamento das denúncias.</p>
    </a>
  </section>

  <p class="landing__aviso">
    Este canal não coleta nome, e-mail, IP ou qualquer dado que identifique quem denuncia.
    O protocolo gerado no envio é a única forma de acompanhar o caso depois.
  </p>
</main>

<footer class="rodape-pagina">
  <p>Canal de Ética e Conduta — conforme princípios de minimização de dados da LGPD.</p>
</footer>
</body>
</html>
