<?php
$host   = '127.0.0.1';
$user   = 'root';
$pass   = '';
$dbname = 'denuncias';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    $pdo->exec("USE `$dbname`;");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS usuarios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            senha VARCHAR(255) NOT NULL,
            funcao VARCHAR(50) DEFAULT 'admin',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB;

        CREATE TABLE IF NOT EXISTS denuncias (
            id INT AUTO_INCREMENT PRIMARY KEY,
            protocolo VARCHAR(20) UNIQUE NOT NULL,
            tipo_assedio VARCHAR(100) NOT NULL,
            descricao TEXT NOT NULL,
            local_ocorrencia VARCHAR(150),
            data_ocorrencia DATE,
            status VARCHAR(50) DEFAULT 'Pendente',
            resposta_admin TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB;
    ");

    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute(['compliance@empresa.com']);
    if (!$stmt->fetch()) {
        $senhaHash = password_hash('compliance123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)");
        $stmt->execute(['Oficial de Compliance', 'compliance@empresa.com', $senhaHash]);
    }
} catch (PDOException $e) {
    die("Erro no Banco de Dados: " . $e->getMessage());
}