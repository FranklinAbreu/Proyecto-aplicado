<?php

declare(strict_types=1);

define('PROJECT_ROOT', dirname(__DIR__));
$app = require PROJECT_ROOT . '/config/app.php';
date_default_timezone_set($app['timezone']);

require PROJECT_ROOT . '/app/Core/helpers.php';
require PROJECT_ROOT . '/app/Core/Database.php';
require PROJECT_ROOT . '/app/Models/Lugar.php';
require PROJECT_ROOT . '/app/Controllers/LugarController.php';

$connection = null;
try {
    $databaseConfig = require PROJECT_ROOT . '/config/database.php';
    $connection = Database::connect($databaseConfig);
    $model = Lugar::fromDatabase($connection);
} catch (PDOException $exception) {
    // El prototipo permanece navegable mientras se prepara MySQL.
    error_log('CercaRD: catálogo inicial local; conexión o tabla de MySQL no disponible.');
    $model = new Lugar(require PROJECT_ROOT . '/database/demo.php', true);
}

$controller = new LugarController($model, $app);
