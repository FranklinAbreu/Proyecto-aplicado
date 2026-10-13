<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/app/Core/Database.php';
require dirname(__DIR__) . '/app/Core/HotelSchema.php';

try {
    $connection = Database::connect(require dirname(__DIR__) . '/config/database.php');
    HotelSchema::ensure($connection);
    echo "Estructura de hoteles preparada. Los datos se agregan desde administración.\n";
} catch (PDOException $exception) {
    fwrite(STDERR, "No se pudo preparar la estructura de hoteles. Comprueba MySQL y la instalación de la base de datos.\n");
    exit(1);
}
