<?php

declare(strict_types=1);

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function base_url(): string
{
    $directory = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    $segments = array_filter(explode('/', $directory), function (string $segment): bool {
        return $segment !== '' && $segment !== '.';
    });

    return $segments ? '/' . implode('/', array_map(function (string $segment): string {
        return rawurlencode(rawurldecode($segment));
    }, $segments)) : '';
}

function url(array $parameters = []): string
{
    return base_url() . '/index.php' . ($parameters ? '?' . http_build_query($parameters) : '');
}

function asset_url(string $path): string
{
    $directPublicEntry = realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === realpath(PROJECT_ROOT . '/public/index.php');
    return base_url() . ($directPublicEntry ? '/assets/' : '/public/assets/') . ltrim($path, '/');
}

function input_text(string $key, int $maxLength = 100): string
{
    $value = $_GET[$key] ?? '';
    if (!is_string($value)) {
        return '';
    }

    return mb_substr(trim($value), 0, $maxLength, 'UTF-8');
}

function api_url(string $endpoint): string
{
    $directPublicEntry = realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === realpath(PROJECT_ROOT . '/public/index.php');
    return base_url() . ($directPublicEntry ? '/api/' : '/public/api/') . basename($endpoint);
}

function place_style(string $type): array
{
    $styles = [
        'Playa' => ['cover-playa', '☀'],
        'Río' => ['cover-rio', '≈'],
        'Parque' => ['cover-parque', '❋'],
        'Montaña' => ['cover-montana', '△'],
        'Cultural' => ['cover-cultural', '⌂'],
    ];

    return $styles[$type] ?? ['cover-parque', '◇'];
}

function destination_meta(array $place): array
{
    static $destinations = null;
    if ($destinations === null) {
        $file = PROJECT_ROOT . '/config/destinations.php';
        $destinations = is_file($file) ? require $file : [];
        $localFile = PROJECT_ROOT . '/config/local_destinations.php';
        if (is_file($localFile)) {
            $destinations = array_replace($destinations, require $localFile);
        }
    }

    $defaults = [
        'image' => '',
        'image_alt' => $place['nombre'] ?? 'Paisaje de República Dominicana',
        'photo_author' => '',
        'photo_author_url' => '',
        'photo_license' => '',
        'photo_license_url' => '',
        'photo_source' => '',
        'photo_source_label' => 'Fuente de la fotografía',
        'source_url' => '',
        'highlight' => '',
        'region' => '',
    ];
    $metadata = $destinations[(int) ($place['id'] ?? 0)] ?? [];

    // Los registros añadidos por el usuario pueden tener los mismos IDs del catálogo inicial.
    if (isset($place['nombre']) && ($metadata['nombre'] ?? '') !== $place['nombre']) {
        $metadata = [];
    }

    $result = array_replace($defaults, $metadata);
    foreach (destination_columns() as $column => $key) {
        // NULL conserva la metadata del catálogo inicial; una cadena vacía permite retirarla.
        if (array_key_exists($column, $place) && $place[$column] !== null) {
            $result[$key] = (string) $place[$column];
        }
    }
    return $result;
}

function destination_columns(): array
{
    return [
        'imagen' => 'image', 'imagen_alt' => 'image_alt',
        'foto_autor' => 'photo_author', 'foto_autor_url' => 'photo_author_url',
        'foto_fuente' => 'photo_source', 'foto_fuente_label' => 'photo_source_label',
        'foto_licencia' => 'photo_license', 'foto_licencia_url' => 'photo_license_url',
        'fuente_url' => 'source_url', 'destacado' => 'highlight', 'region' => 'region',
    ];
}

function image_url(array $place): string
{
    $metadata = destination_meta($place);
    return $metadata['image'] !== '' ? asset_url($metadata['image']) : '';
}

function maps_url(array $place): ?string
{
    $latitude = $place['latitud'] ?? null;
    $longitude = $place['longitud'] ?? null;
    if (!is_numeric($latitude) || !is_numeric($longitude)
        || abs((float) $latitude) > 90 || abs((float) $longitude) > 180) {
        return null;
    }

    return 'https://www.google.com/maps/search/?' . http_build_query([
        'api' => 1,
        'query' => $latitude . ',' . $longitude,
    ]);
}

function render(string $view, array $data): void
{
    // Las vistas se eligen en el controlador; nunca desde un parámetro de la URL.
    extract($data, EXTR_SKIP);
    require PROJECT_ROOT . '/app/Views/layout/header.php';
    require PROJECT_ROOT . '/app/Views/' . $view . '.php';
    require PROJECT_ROOT . '/app/Views/layout/footer.php';
}

function renderAdmin(string $view, array $data): void
{
    extract($data, EXTR_SKIP);
    require PROJECT_ROOT . '/app/Views/admin/layout.php';
}

function safe_external_url($value): ?string
{
    if (!is_string($value) || $value === '' || !filter_var($value, FILTER_VALIDATE_URL)) {
        return null;
    }
    $parts = parse_url($value);
    return $parts !== false && in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)
        && !isset($parts['user']) && !isset($parts['pass']) ? $value : null;
}

function hotel_meta(array $hotel): array
{
    $metadata = [
        'image' => '', 'image_alt' => $hotel['nombre'] ?? 'Fotografía del alojamiento',
        'photo_author' => '', 'photo_author_url' => '', 'photo_source' => '',
        'photo_source_label' => 'Fuente de la fotografía', 'photo_license' => '',
        'photo_license_url' => '', 'source_url' => '', 'highlight' => '', 'region' => '',
    ];
    foreach (destination_columns() as $column => $key) {
        if (isset($hotel[$column])) { $metadata[$key] = (string) $hotel[$column]; }
    }
    foreach (['photo_author_url', 'photo_source', 'photo_license_url', 'source_url'] as $key) {
        $metadata[$key] = safe_external_url($metadata[$key]) ?? '';
    }
    return $metadata;
}

function hotel_image_url(array $hotel): string
{
    $path = hotel_meta($hotel)['image'];
    return preg_match('~\A(?:images|uploads)/[a-zA-Z0-9_/-]+\.(?:jpg|jpeg|png|webp)\z~D', $path)
        ? asset_url($path) : '';
}

function hotel_maps_url(array $hotel): string
{
    return maps_url($hotel) ?? 'https://www.google.com/maps/search/?' . http_build_query([
        'api' => 1, 'query' => implode(', ', array_filter([
            $hotel['nombre'] ?? '', $hotel['ubicacion'] ?? '', $hotel['municipio'] ?? '', 'República Dominicana',
        ])),
    ]);
}

function hotel_booking_url(array $hotel): ?string
{
    return safe_external_url($hotel['reserva_url'] ?? '') ?? safe_external_url($hotel['sitio_web'] ?? '');
}
