<?php

declare(strict_types=1);

// API de lectura: origen numérico y destinos activos del mismo catálogo PHP/MySQL.
header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

function respondRoutes(array $data, int $status): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    header('Allow: GET');
    respondRoutes(['status' => 'error', 'message' => 'Este recurso solo acepta consultas de rutas.', 'routes' => []], 405);
}

$lat = $_GET['lat'] ?? null;
$lng = $_GET['lng'] ?? null;
if (!is_string($lat) || !is_string($lng) || strlen($lat) > 32 || strlen($lng) > 32
    || filter_var($lat, FILTER_VALIDATE_FLOAT) === false || filter_var($lng, FILTER_VALIDATE_FLOAT) === false) {
    respondRoutes(['status' => 'error', 'message' => 'Indica una latitud y una longitud numéricas válidas.', 'routes' => []], 400);
}
$latitude = (float) $lat;
$longitude = (float) $lng;
if (!is_finite($latitude) || !is_finite($longitude) || abs($latitude) > 90 || abs($longitude) > 180) {
    respondRoutes(['status' => 'error', 'message' => 'Las coordenadas están fuera del rango permitido.', 'routes' => []], 400);
}

require dirname(__DIR__, 2) . '/app/Core/RouteService.php';
if (!RouteService::inCatalogArea($latitude, $longitude)) {
    respondRoutes(['status' => 'error', 'message' => 'El mapa calcula rutas dentro del área de República Dominicana. Selecciona una ubicación en el país.', 'routes' => []], 400);
}

try {
    // Bootstrap elige MySQL o el catálogo local sin renderizar ninguna página.
    require dirname(__DIR__, 2) . '/app/bootstrap.php';
    $result = (new RouteService())->nearbyRoutes($latitude, $longitude, $model->all());
    respondRoutes($result, $result['status'] === 'ok' ? 200 : 503);
} catch (Throwable $exception) {
    // Evita publicar consultas SQL, rutas locales o información interna.
    error_log('CercaRD: servicio de rutas temporalmente no disponible.');
    respondRoutes([
        'status' => 'unavailable',
        'message' => 'No se pudo consultar el catálogo de rutas. Inténtalo de nuevo en unos momentos.',
        'routes' => [],
        'nearby' => [],
        'warnings' => [],
    ], 503);
}
