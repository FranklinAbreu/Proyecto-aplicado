<?php

declare(strict_types=1);

final class AdminSchema
{
    private const COLUMNS = [
        'imagen' => 'VARCHAR(255)', 'imagen_alt' => 'VARCHAR(255)',
        'foto_autor' => 'VARCHAR(150)', 'foto_autor_url' => 'VARCHAR(500)',
        'foto_fuente' => 'VARCHAR(500)', 'foto_fuente_label' => 'VARCHAR(100)',
        'foto_licencia' => 'VARCHAR(100)', 'foto_licencia_url' => 'VARCHAR(500)',
        'fuente_url' => 'VARCHAR(500)', 'destacado' => 'VARCHAR(150)',
        'region' => 'VARCHAR(100)', 'updated_at' => 'TIMESTAMP',
    ];

    public static function ensure(PDO $connection): void
    {
        $existing = array_column($connection->query('SHOW COLUMNS FROM lugares')->fetchAll(PDO::FETCH_ASSOC), 'Field');
        foreach (self::COLUMNS as $column => $definition) {
            if (!in_array($column, $existing, true)) {
                $connection->exec('ALTER TABLE lugares ADD COLUMN ' . $column . ' ' . $definition . ' NULL DEFAULT NULL');
            }
        }
        $connection->exec('CREATE TABLE IF NOT EXISTS administradores (
            id INT UNSIGNED NOT NULL PRIMARY KEY,
            usuario VARCHAR(40) NOT NULL UNIQUE,
            clave_hash VARCHAR(255) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public static function isReady(PDO $connection): bool
    {
        try {
            $existing = array_column($connection->query('SHOW COLUMNS FROM lugares')->fetchAll(PDO::FETCH_ASSOC), 'Field');
            if (array_diff(array_keys(self::COLUMNS), $existing) !== []) {
                return false;
            }
            $connection->query('SELECT id, usuario, clave_hash FROM administradores LIMIT 0');
            return true;
        } catch (PDOException $exception) {
            return false;
        }
    }
}
