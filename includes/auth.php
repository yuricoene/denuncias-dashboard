<?php
declare(strict_types=1);

/**
 * Autenticacao da area administrativa (RF06 / RN03).
 * Sessao guarda so o id, nome e perfil do analista -- nada sobre
 * denunciantes passa por aqui.
 */

function auth_iniciar_sessao(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function auth_logado(): bool
{
    auth_iniciar_sessao();
    return isset($_SESSION['analista_id']);
}

/** Usar em paginas HTML do admin: redireciona para o login. */
function auth_exigir_login(): void
{
    auth_iniciar_sessao();
    if (!auth_logado()) {
        header('Location: login.php');
        exit;
    }
}

/** Usar em endpoints JSON do admin: responde 401 em vez de redirecionar. */
function auth_exigir_login_api(): void
{
    auth_iniciar_sessao();
    if (!auth_logado()) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['erro' => 'Nao autenticado.']);
        exit;
    }
}

function auth_tentar_login(PDO $pdo, string $email, string $senha): bool
{
    $stmt = $pdo->prepare('SELECT id, nome, senha_hash, perfil FROM usuarios_analistas WHERE email = :email');
    $stmt->execute(['email' => $email]);
    $usuario = $stmt->fetch();

    if (!$usuario || !password_verify($senha, $usuario['senha_hash'])) {
        return false;
    }

    auth_iniciar_sessao();
    session_regenerate_id(true);
    $_SESSION['analista_id']     = $usuario['id'];
    $_SESSION['analista_nome']   = $usuario['nome'];
    $_SESSION['analista_perfil'] = $usuario['perfil'];
    return true;
}

function auth_logout(): void
{
    auth_iniciar_sessao();
    $_SESSION = [];
    session_destroy();
}
