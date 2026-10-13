<?php

declare(strict_types=1);

// Static visual previews only: no bootstrap, connection, sessions or administrator account.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

define('PROJECT_ROOT', dirname(__DIR__));
require PROJECT_ROOT . '/app/Core/helpers.php';

$options = getopt('', ['base:']);
$base = (string) ($options['base'] ?? '/' . rawurlencode(basename(PROJECT_ROOT)));
if ($base !== '' && (!str_starts_with($base, '/') || str_contains($base, '//')
    || preg_match('/[?#\x00-\x20]/', $base) || str_contains(rawurldecode($base), '..'))) {
    fwrite(STDERR, "--base debe ser la ruta URL de la carpeta del proyecto, por ejemplo /proyecto%20aplicado.\n");
    exit(1);
}
$base = rtrim($base, '/');
$_SERVER['SCRIPT_NAME'] = rawurldecode($base) . '/index.php';
$_SERVER['SCRIPT_FILENAME'] = PROJECT_ROOT . '/index.php';
$_GET = [];

$app = require PROJECT_ROOT . '/config/app.php';
$places = require PROJECT_ROOT . '/database/demo.php';
foreach ($places as &$place) {
    $metadata = destination_meta($place);
    foreach (destination_columns() as $column => $key) {
        $place[$column] = $metadata[$key];
    }
}
unset($place);
$stats = ['total' => count($places), 'activos' => 0, 'sin_coordenadas' => 0, 'fotos' => 0];
foreach ($places as $place) {
    $stats['activos'] += (int) $place['activo'] === 1 ? 1 : 0;
    $stats['sin_coordenadas'] += !is_numeric($place['latitud']) || !is_numeric($place['longitud']) ? 1 : 0;
    $stats['fotos'] += $place['imagen'] !== '' ? 1 : 0;
}
$stats['con_coordenadas'] = $stats['total'] - $stats['sin_coordenadas'];
$editing = array_values(array_filter($places, static fn (array $place): bool => (int) $place['id'] === 201))[0] ?? $places[0];

$prefix = 'admin_' . bin2hex(random_bytes(8));
$directory = PROJECT_ROOT . '/public/assets/previews';
if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
    throw new RuntimeException('No se pudo preparar la carpeta de vistas previas.');
}
$previewLinks = [];
foreach (['index', 'form', 'setup', 'login'] as $viewName) {
    $previewLinks[$viewName] = asset_url('previews/' . $prefix . '_' . $viewName . '.html');
}
$routeViews = [
    'admin' => 'index', 'admin_nuevo' => 'form', 'admin_editar' => 'form',
    'admin_setup' => 'setup', 'admin_login' => 'login',
];
$result = [];
foreach (['index', 'form', 'setup', 'login'] as $viewName) {
    $data = [
        'app' => $app,
        'title' => ['index' => 'Tus destinos', 'form' => 'Editar destino', 'setup' => 'Crea tu administrador', 'login' => 'Acceso al panel'][$viewName],
        'currentUser' => in_array($viewName, ['index', 'form'], true) ? ['id' => 0, 'usuario' => 'Vista previa'] : null,
        'csrfToken' => 'NO_REAL_PREVIEW_TOKEN', 'canSetup' => true, 'dbAvailable' => true,
        'errors' => [], 'message' => null, 'places' => $places, 'stats' => $stats,
        'values' => $viewName === 'form' ? $editing : ['usuario' => ''],
    ];
    ob_start();
    renderAdmin($viewName, $data);
    $html = ob_get_clean();

    $document = new DOMDocument('1.0', 'UTF-8');
    @$document->loadHTML('<?xml encoding="UTF-8">' . $html);
    $xpath = new DOMXPath($document);
    foreach ($xpath->query('//form') as $form) {
        $form->setAttribute('action', '#');
        $form->setAttribute('method', 'get');
        $form->removeAttribute('enctype');
        $form->setAttribute('data-static-preview', 'true');
    }
    foreach ($xpath->query('//button[not(@type) or @type="submit"] | //input[@type="submit"]') as $submit) {
        $submit->setAttribute('disabled', 'disabled');
        $submit->setAttribute('title', 'Vista previa: acción desactivada');
    }
    foreach ($xpath->query('//*[@autofocus]') as $field) {
        $field->removeAttribute('autofocus');
    }
    foreach ($xpath->query('//input[@name="csrf_token"]') as $field) {
        $field->setAttribute('value', 'NO_REAL_PREVIEW_TOKEN');
    }
    foreach ($xpath->query('//input[@type="password"]') as $field) {
        $field->setAttribute('value', '');
    }
    foreach ($xpath->query('//a[@href]') as $link) {
        $parsed = parse_url(html_entity_decode($link->getAttribute('href'), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $query = [];
        parse_str($parsed['query'] ?? '', $query);
        if (is_string($query['vista'] ?? null) && isset($routeViews[$query['vista']])) {
            $link->setAttribute('href', $previewLinks[$routeViews[$query['vista']]]);
        }
    }
    $head = $document->getElementsByTagName('head')->item(0);
    $policy = $document->createElement('meta');
    $policy->setAttribute('http-equiv', 'Content-Security-Policy');
    $policy->setAttribute('content', "form-action 'none'; base-uri 'none'; object-src 'none'");
    $head->insertBefore($policy, $head->firstChild);
    // DOMDocument's UTF-8 parsing marker is not part of the delivered HTML.
    foreach (iterator_to_array($document->childNodes) as $node) {
        if ($node->nodeType === XML_PI_NODE && $node->nodeName === 'xml') {
            $document->removeChild($node);
        }
    }
    $preview = $document->saveHTML();
    $path = $directory . '/' . $prefix . '_' . $viewName . '.html';
    if (file_put_contents($path, $preview, LOCK_EX) === false) {
        throw new RuntimeException('No se pudo guardar la vista previa.');
    }
    $result[$viewName] = ['path' => $path, 'url' => 'http://localhost' . $previewLinks[$viewName]];
}
echo json_encode(['prefix' => $prefix, 'views' => $result], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
