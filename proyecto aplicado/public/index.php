<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

header('Content-Type: text/html; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

$page = input_text('vista', 30);
if (in_array($page, ['admin', 'admin_setup', 'admin_login', 'admin_nuevo', 'admin_editar', 'admin_guardar', 'admin_logout'], true)) {
    require PROJECT_ROOT . '/app/Core/AdminSchema.php';
    require PROJECT_ROOT . '/app/Core/AdminAuth.php';
    require PROJECT_ROOT . '/app/Core/PhotoUpload.php';
    require PROJECT_ROOT . '/app/Models/AdminLugar.php';
    require PROJECT_ROOT . '/app/Controllers/AdminController.php';
    (new AdminController($connection, $app))->handle($page);
    exit;
}
$controller->handle($page === '' ? 'inicio' : $page);
