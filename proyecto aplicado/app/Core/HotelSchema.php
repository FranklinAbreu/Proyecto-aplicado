<?php

declare(strict_types=1);

final class HotelSchema
{
    // La instalación se invoca desde install_hotels.php; las páginas solo consultan isReady().
    public static function ensure(PDO $connection): void
    {
        $connection->exec('CREATE TABLE IF NOT EXISTS hoteles (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(150) NOT NULL,
            provincia VARCHAR(100) NOT NULL,
            municipio VARCHAR(100) NOT NULL,
            descripcion TEXT NOT NULL,
            ubicacion VARCHAR(255) NOT NULL,
            latitud DECIMAL(10, 7) NULL DEFAULT NULL,
            longitud DECIMAL(10, 7) NULL DEFAULT NULL,
            telefono VARCHAR(50) NULL DEFAULT NULL,
            sitio_web VARCHAR(500) NULL DEFAULT NULL,
            reserva_url VARCHAR(500) NULL DEFAULT NULL,
            servicios TEXT NULL,
            imagen VARCHAR(255) NULL DEFAULT NULL,
            imagen_alt VARCHAR(255) NULL DEFAULT NULL,
            foto_autor VARCHAR(150) NULL DEFAULT NULL,
            foto_autor_url VARCHAR(500) NULL DEFAULT NULL,
            foto_fuente VARCHAR(500) NULL DEFAULT NULL,
            foto_fuente_label VARCHAR(100) NULL DEFAULT NULL,
            foto_licencia VARCHAR(100) NULL DEFAULT NULL,
            foto_licencia_url VARCHAR(500) NULL DEFAULT NULL,
            fuente_url VARCHAR(500) NULL DEFAULT NULL,
            verificado_en DATE NULL DEFAULT NULL,
            activo TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $connection->exec('CREATE TABLE IF NOT EXISTS hotel_lugar (
            hotel_id INT UNSIGNED NOT NULL,
            destino_id INT UNSIGNED NOT NULL,
            PRIMARY KEY (hotel_id, destino_id),
            KEY idx_hotel_lugar_destino (destino_id),
            CONSTRAINT fk_hotel_lugar_hotel FOREIGN KEY (hotel_id) REFERENCES hoteles (id) ON DELETE CASCADE,
            CONSTRAINT fk_hotel_lugar_destino FOREIGN KEY (destino_id) REFERENCES lugares (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public static function isReady(PDO $connection): bool
    {
        try {
            $connection->query('SELECT id, nombre, provincia, municipio, descripcion, ubicacion,
                latitud, longitud, telefono, sitio_web, reserva_url, servicios,
                imagen, imagen_alt, foto_autor, foto_autor_url, foto_fuente,
                foto_fuente_label, foto_licencia, foto_licencia_url, fuente_url,
                verificado_en, activo, created_at, updated_at FROM hoteles LIMIT 0');
            $connection->query('SELECT hotel_id, destino_id FROM hotel_lugar LIMIT 0');
            return true;
        } catch (PDOException $exception) {
            return false;
        }
    }
}
