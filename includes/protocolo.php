<?php
declare(strict_types=1);

/**
 * Gera um protocolo unico e aleatorio (RF02). Nao usa nenhum dado do
 * denunciante como semente -- e puramente aleatorio (random_int, CSPRNG),
 * e checa contra o banco para garantir unicidade (RN02).
 */
function gerar_protocolo(PDO $pdo): string
{
    // sem caracteres ambiguos (0/O, 1/I)
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    do {
        $codigo = '';
        for ($i = 0; $i < 8; $i++) {
            $codigo .= $chars[random_int(0, strlen($chars) - 1)];
        }
        $protocolo = 'DEN-' . $codigo;

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM denuncias WHERE protocolo = :p');
        $stmt->execute(['p' => $protocolo]);
        $existe = (int) $stmt->fetchColumn() > 0;
    } while ($existe);

    return $protocolo;
}
