<?php

// Valores habituales de una instalación local de XAMPP.
// Si tu instalación usa otros datos, crea database.local.php con un array igual.
$config = [
    'host' => getenv('DB_HOST') ?: '127.0.0.1',
    'port' => getenv('DB_PORT') ?: '3306',
    'name' => getenv('DB_NAME') ?: 'turismo_rd',
    'user' => getenv('DB_USER') ?: 'root',
    'password' => getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : '',
];

$localFile = __DIR__ . '/database.local.php';
if (is_file($localFile)) {
    $config = array_replace($config, require $localFile);
}

return $config;
