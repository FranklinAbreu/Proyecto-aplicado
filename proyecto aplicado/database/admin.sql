-- Campos del panel de administración para MariaDB 10.4 de XAMPP.
-- Importar después de schema.sql. Puede ejecutarse varias veces.
-- Conserva los destinos, sus IDs, fotos anteriores y demás datos existentes.
USE turismo_rd;
SET NAMES utf8mb4;

ALTER TABLE lugares
    ADD COLUMN IF NOT EXISTS imagen VARCHAR(255) NULL DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS imagen_alt VARCHAR(255) NULL DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS foto_autor VARCHAR(150) NULL DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS foto_autor_url VARCHAR(500) NULL DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS foto_fuente VARCHAR(500) NULL DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS foto_fuente_label VARCHAR(100) NULL DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS foto_licencia VARCHAR(100) NULL DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS foto_licencia_url VARCHAR(500) NULL DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS fuente_url VARCHAR(500) NULL DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS destacado VARCHAR(150) NULL DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS region VARCHAR(100) NULL DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL DEFAULT NULL;

-- La cuenta inicial se crea desde localhost; no se incluye una contraseña predeterminada.
CREATE TABLE IF NOT EXISTS administradores (
    id INT UNSIGNED NOT NULL PRIMARY KEY,
    usuario VARCHAR(40) NOT NULL UNIQUE,
    clave_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
