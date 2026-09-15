<?php
declare(strict_types=1);
require __DIR__ . '/../includes/data.php';
require __DIR__ . '/../includes/auth.php';
auth_exigir_login_api();

header('Content-Type: application/json; charset=utf-8');

$termo  = isset($_GET['q']) ? (string) $_GET['q'] : '';
$tipo   = isset($_GET['tipo']) ? (string) $_GET['tipo'] : '';
$status = isset($_GET['status']) ? (string) $_GET['status'] : '';

$busca = denuncias_buscar($pdo, $termo, $tipo, $status, 20, 0);

echo json_encode([
    'total'      => $busca['total'],
    'exibindo'   => count($busca['resultados']),
    'resultados' => array_map(static fn (array $r): array => [
        'id'        => (int) $r['id'],
        'protocolo' => $r['protocolo'],
        'tipo'      => $r['tipo'],
        'status'    => $r['status'],
    ], $busca['resultados']),
], JSON_UNESCAPED_UNICODE);
