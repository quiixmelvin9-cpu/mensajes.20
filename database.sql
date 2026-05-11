-- Base de datos nueva para este sistema
CREATE DATABASE IF NOT EXISTS bd_mensajeria_nueva CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bd_mensajeria_nueva;

-- Tabla principal de cuentas de usuario
CREATE TABLE IF NOT EXISTS cuentas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_usuario VARCHAR(30) NOT NULL UNIQUE,
    nombre_completo VARCHAR(100) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    foto_perfil VARCHAR(255) NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Bloque de compatibilidad para bases existentes sin columna foto_perfil
SET @col_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'cuentas'
      AND COLUMN_NAME = 'foto_perfil'
);
SET @sql := IF(@col_exists = 0, 'ALTER TABLE cuentas ADD COLUMN foto_perfil VARCHAR(255) NULL AFTER password_hash', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Si existia una version previa con comentarios, se elimina
DROP TABLE IF EXISTS chat_comentarios;

-- Tabla de mensajes privados entre dos cuentas
CREATE TABLE IF NOT EXISTS chat_mensajes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    remitente_id INT NOT NULL,
    destinatario_id INT NOT NULL,
    contenido TEXT NOT NULL,
    enviado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_chat_mensajes_remitente FOREIGN KEY (remitente_id) REFERENCES cuentas(id) ON DELETE CASCADE,
    CONSTRAINT fk_chat_mensajes_destinatario FOREIGN KEY (destinatario_id) REFERENCES cuentas(id) ON DELETE CASCADE,
    INDEX idx_chat (remitente_id, destinatario_id, enviado_en)
) ENGINE=InnoDB;
