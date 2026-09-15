-- Sistema de Registro de Denúncias Anônimas de Assédio Moral e Sexual
-- Esquema do banco de dados (MySQL / MariaDB)

CREATE DATABASE IF NOT EXISTS denuncias_dashboard
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE denuncias_dashboard;

CREATE TABLE IF NOT EXISTS denuncias (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    protocolo        VARCHAR(20) NOT NULL UNIQUE,
    tipo             ENUM('Assédio moral','Assédio sexual','Outros') NOT NULL,
    relato           TEXT NOT NULL,
    local            VARCHAR(255) NULL,
    data_ocorrencia  DATE NULL,
    gravidade        ENUM('Baixa','Média','Alta') NULL,
    status           ENUM('Recebida','Em análise','Em investigação','Concluída','Arquivada')
                     NOT NULL DEFAULT 'Recebida',
    criado_em        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- RN02: nenhuma coluna acima identifica o denunciante. O protocolo
-- (gerado aleatoriamente) e a unica ponte entre pessoa e caso, e essa
-- ponte so existe com quem denunciou -- nao e armazenada em nenhuma
-- outra tabela.

CREATE TABLE IF NOT EXISTS evidencias (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    denuncia_id      INT NOT NULL,
    caminho_arquivo  VARCHAR(255) NOT NULL,
    tipo_arquivo     VARCHAR(50) NOT NULL,
    FOREIGN KEY (denuncia_id) REFERENCES denuncias(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS historico_status (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    denuncia_id      INT NOT NULL,
    status_anterior  VARCHAR(30) NULL,
    status_novo      VARCHAR(30) NOT NULL,
    alterado_em      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (denuncia_id) REFERENCES denuncias(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS usuarios_analistas (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    nome         VARCHAR(120) NOT NULL,
    email        VARCHAR(150) NOT NULL UNIQUE,
    senha_hash   VARCHAR(255) NOT NULL,
    perfil       VARCHAR(30) NOT NULL DEFAULT 'Analista'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
