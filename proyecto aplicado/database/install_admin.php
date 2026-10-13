<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/app/Core/Database.php';
require dirname(__DIR__) . '/app/Core/AdminSchema.php';

try {
    $connection = Database::connect(require dirname(__DIR__) . '/config/database.php');
    AdminSchema::ensure($connection);
    echo "Panel preparado. Crea tu cuenta desde el acceso de administración en localhost.\n";
} catch (PDOException $exception) {
    fwrite(STDERR, "No se pudo preparar el panel. Comprueba MySQL y la configuración de la base de datos.\n");
    exit(1);
}
