<?php

declare(strict_types=1);

/**
 * Compara destinos mediante OSRM y devuelve trazados de carretera reales.
 * Referencia: https://project-osrm.org/docs/v5.24.0/api/#table-service
 * OSRM calcula trayectos rápidos; su distancia permite ordenar los destinos.
 */
final class RouteService
{
    private const API_ORIGIN = 'https://router.project-osrm.org';
    private const MAX_CANDIDATES = 20;
    private const MAX_ROUTES = 3;
    private const REQUEST_TIMEOUT_MS = 4200;
    private const TOTAL_TIMEOUT_SECONDS = 15;
    private const MAX_RESPONSE_BYTES = 2000000;

    /** Área de trabajo del catálogo dominicano; evita solicitudes mundiales. */
    public static function inCatalogArea(float $latitude, float $longitude): bool
    {
        return is_finite($latitude) && is_finite($longitude)
            && $latitude >= 17 && $latitude <= 20.5
            && $longitude >= -72.2 && $longitude <= -68;
    }

    public function nearbyRoutes(float $latitude, float $longitude, array $places): array
    {
        if (!self::inCatalogArea($latitude, $longitude)) {
            throw new InvalidArgumentException('Selecciona una ubicación en el área de República Dominicana.');
        }

        $origin = ['lat' => $latitude, 'lng' => $longitude];
        $candidates = $this->candidates($latitude, $longitude, $places);
        $base = [
            'status' => 'unavailable',
            'origin' => $origin,
            'routes' => [],
            'nearby' => array_slice($candidates, 0, self::MAX_ROUTES),
            'warnings' => [],
            'source' => 'OSRM / OpenStreetMap',
            'candidate_count' => count($candidates),
        ];

        if (!$candidates) {
            $base['message'] = 'El catálogo aún no tiene destinos con coordenadas verificadas para calcular rutas.';
            return $base;
        }
        if (!extension_loaded('curl')) {
            $base['message'] = 'El servicio de rutas no está disponible en este momento. Puedes explorar los destinos del mapa.';
            return $base;
        }

        $deadline = microtime(true) + self::TOTAL_TIMEOUT_SECONDS;
        $coordinates = [$this->coordinates($latitude, $longitude)];
        foreach ($candidates as $candidate) {
            $place = $candidate['place'];
            $coordinates[] = $this->coordinates($place['latitud'], $place['longitud']);
        }

        try {
            $table = $this->request('table', $coordinates, [
                'sources' => '0',
                'destinations' => implode(';', range(1, count($candidates))),
                'annotations' => 'distance,duration',
                'generate_hints' => 'false',
            ], $deadline);
        } catch (RuntimeException $exception) {
            $base['message'] = 'No se pudieron calcular rutas por carretera. Inténtalo de nuevo en unos momentos.';
            $base['warnings'][] = 'Los destinos cercanos mostrados se ordenan por distancia en línea recta; no son rutas calculadas.';
            return $base;
        }

        $ranked = [];
        $missing = 0;
        $distantAccess = 0;
        $originAccessDistance = $this->accessDistance($latitude, $longitude, $table['sources'][0] ?? null);
        if ($this->validMeasure($originAccessDistance) && $originAccessDistance > 1000) {
            $base['warnings'][] = 'La ubicación inicial está a más de 1 km de la carretera identificada. El recorrido empieza en ese acceso; comprueba el tramo inicial.';
        }
        foreach ($candidates as $index => $candidate) {
            $distance = $table['distances'][0][$index] ?? null;
            $duration = $table['durations'][0][$index] ?? null;
            if (!$this->validMeasure($distance) || !$this->validMeasure($duration)) {
                $missing++;
                continue;
            }
            $candidatePlace = $candidate['place'];
            $accessDistance = $this->accessDistance($candidatePlace['latitud'], $candidatePlace['longitud'], $table['destinations'][$index] ?? null);
            if ($this->validMeasure($accessDistance) && $accessDistance > 1000) {
                $distantAccess++;
                continue;
            }
            $ranked[] = $candidate + [
                'distance_m' => (float) $distance,
                'duration_s' => (float) $duration,
            ];
        }
        usort($ranked, static function (array $first, array $second): int {
            return ($first['distance_m'] <=> $second['distance_m'])
                ?: ($first['duration_s'] <=> $second['duration_s']);
        });

        if ($missing > 0) {
            $base['warnings'][] = 'Algunos destinos no tienen una conexión por carretera disponible en el servicio de rutas.';
        }
        if ($distantAccess > 0) {
            $base['warnings'][] = 'Se omitieron destinos situados a más de 1 km del acceso vial identificado para evitar mostrar una llegada imprecisa.';
        }
        foreach (array_slice($ranked, 0, self::MAX_ROUTES) as $candidate) {
            $place = $candidate['place'];
            try {
                $result = $this->request('route', [
                    $this->coordinates($latitude, $longitude),
                    $this->coordinates($place['latitud'], $place['longitud']),
                ], [
                    'alternatives' => 'false',
                    'steps' => 'false',
                    'geometries' => 'geojson',
                    'overview' => 'full',
                    'generate_hints' => 'false',
                ], $deadline);
                $route = $result['routes'][0] ?? [];
                if (!$this->validMeasure($route['distance'] ?? null)
                    || !$this->validMeasure($route['duration'] ?? null)
                    || !$this->validGeometry($route['geometry'] ?? null)) {
                    throw new RuntimeException('Respuesta de ruta incompleta.');
                }
                $lastAccessDistance = $this->accessDistance($place['latitud'], $place['longitud'], $result['waypoints'][1] ?? null);
                if ($this->validMeasure($lastAccessDistance) && $lastAccessDistance > 1000) {
                    $base['warnings'][] = 'Se omitió la ruta de ' . $place['nombre'] . ' porque su acceso vial está a más de 1 km del destino.';
                    continue;
                }
                $base['routes'][] = [
                    'place' => $place,
                    'distance_m' => (float) $route['distance'],
                    'duration_s' => (float) $route['duration'],
                    'geometry' => $route['geometry'],
                ];

                // El trazado puede terminar en un acceso cercano, no en el atractivo.
                if ($this->validMeasure($lastAccessDistance) && $lastAccessDistance > 150) {
                    $base['warnings'][] = 'La ruta de ' . $place['nombre'] . ' termina en el acceso por carretera más cercano; el tramo final puede requerir caminar u otro transporte.';
                }
                $routeOriginDistance = $this->accessDistance($latitude, $longitude, $result['waypoints'][0] ?? null);
                if ($this->validMeasure($routeOriginDistance) && $routeOriginDistance > 1000
                    && !($this->validMeasure($originAccessDistance) && $originAccessDistance > 1000)) {
                    $base['warnings'][] = 'El recorrido de ' . $place['nombre'] . ' empieza en una carretera a más de 1 km de la ubicación inicial; comprueba el tramo de acceso.';
                }
            } catch (RuntimeException $exception) {
                $base['warnings'][] = 'No se pudo dibujar la ruta de ' . $place['nombre'] . '. Puedes intentarlo nuevamente.';
            }
        }

        if (!$base['routes']) {
            $base['message'] = 'No hay rutas por carretera disponibles para este punto en este momento.';
            $base['warnings'][] = 'Los destinos cercanos mostrados se ordenan por distancia en línea recta; no son rutas calculadas.';
            return $base;
        }

        usort($base['routes'], static fn (array $first, array $second): int => $first['distance_m'] <=> $second['distance_m']);
        $base['status'] = 'ok';
        // No crea un historial de ubicaciones en la base de datos ni usa estimaciones como rutas reales.
        unset($base['nearby']);
        return $base;
    }

    private function candidates(float $latitude, float $longitude, array $places): array
    {
        $candidates = [];
        foreach ($places as $place) {
            if (!is_array($place) || (isset($place['activo']) && (int) $place['activo'] !== 1)
                || !is_numeric($place['latitud'] ?? null) || !is_numeric($place['longitud'] ?? null)) {
                continue;
            }
            $placeLatitude = (float) $place['latitud'];
            $placeLongitude = (float) $place['longitud'];
            if (!self::inCatalogArea($placeLatitude, $placeLongitude)) {
                continue;
            }
            $candidates[] = [
                'place' => [
                    'id' => (int) ($place['id'] ?? 0),
                    'nombre' => (string) ($place['nombre'] ?? ''),
                    'provincia' => (string) ($place['provincia'] ?? ''),
                    'tipo' => (string) ($place['tipo'] ?? ''),
                    'latitud' => $placeLatitude,
                    'longitud' => $placeLongitude,
                ],
                'distance_en_linea_m' => round($this->straightDistance($latitude, $longitude, $placeLatitude, $placeLongitude), 1),
            ];
        }
        usort($candidates, static fn (array $first, array $second): int => $first['distance_en_linea_m'] <=> $second['distance_en_linea_m']);
        return array_slice($candidates, 0, self::MAX_CANDIDATES);
    }

    private function straightDistance(float $latitude, float $longitude, float $otherLatitude, float $otherLongitude): float
    {
        $deltaLatitude = deg2rad($otherLatitude - $latitude);
        $deltaLongitude = deg2rad($otherLongitude - $longitude);
        $a = sin($deltaLatitude / 2) ** 2
            + cos(deg2rad($latitude)) * cos(deg2rad($otherLatitude)) * sin($deltaLongitude / 2) ** 2;
        return 6371000 * 2 * atan2(sqrt(min(1, $a)), sqrt(max(0, 1 - $a)));
    }

    private function coordinates(float $latitude, float $longitude): string
    {
        // Formato fijo independiente del idioma y sin segmentos de URL del usuario.
        return number_format($longitude, 6, '.', '') . ',' . number_format($latitude, 6, '.', '');
    }

    private function request(string $service, array $coordinates, array $options, float $deadline): array
    {
        if (!in_array($service, ['route', 'table'], true)) {
            throw new RuntimeException('Servicio no permitido.');
        }
        $remainingMs = (int) floor(($deadline - microtime(true)) * 1000);
        if ($remainingMs < 100) {
            throw new RuntimeException('Tiempo de espera agotado.');
        }
        $url = self::API_ORIGIN . '/' . $service . '/v1/driving/' . implode(';', $coordinates)
            . '?' . http_build_query($options, '', '&', PHP_QUERY_RFC3986);
        $handle = curl_init($url);
        if ($handle === false) {
            throw new RuntimeException('Servicio temporalmente no disponible.');
        }
        $body = '';
        $settings = [
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT_MS => min(2000, $remainingMs),
            CURLOPT_TIMEOUT_MS => min(self::REQUEST_TIMEOUT_MS, $remainingMs),
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
            CURLOPT_USERAGENT => 'CercaRD/0.3 (local educational tourism catalog)',
            CURLOPT_WRITEFUNCTION => static function ($curl, string $chunk) use (&$body): int {
                if (strlen($body) + strlen($chunk) > self::MAX_RESPONSE_BYTES) {
                    return 0;
                }
                $body .= $chunk;
                return strlen($chunk);
            },
        ];
        // XAMPP incluye este almacén CA; siempre se mantiene la verificación TLS.
        $xamppCa = 'C:/xampp/apache/bin/curl-ca-bundle.crt';
        if (ini_get('curl.cainfo') === '' && is_readable($xamppCa)) {
            $settings[CURLOPT_CAINFO] = $xamppCa;
        }
        curl_setopt_array($handle, $settings);
        $success = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        curl_close($handle);
        if ($success === false || $status !== 200) {
            throw new RuntimeException('Servicio de rutas no disponible.');
        }
        try {
            $data = json_decode($body, true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Respuesta no válida del servicio de rutas.');
        }
        if (!is_array($data) || ($data['code'] ?? '') !== 'Ok') {
            throw new RuntimeException('No se encontró una conexión por carretera.');
        }
        return $data;
    }

    private function validMeasure($measure): bool
    {
        return (is_int($measure) || is_float($measure)) && is_finite((float) $measure) && $measure >= 0;
    }

    private function accessDistance(float $latitude, float $longitude, $waypoint): ?float
    {
        if (!is_array($waypoint)) {
            return null;
        }
        if ($this->validMeasure($waypoint['distance'] ?? null)) {
            return (float) $waypoint['distance'];
        }
        // Table omite a veces distance: mide hasta el punto ajustado a la red vial.
        $location = $waypoint['location'] ?? null;
        if (!is_array($location) || count($location) < 2
            || !(is_int($location[0]) || is_float($location[0]))
            || !(is_int($location[1]) || is_float($location[1]))
            || !is_finite((float) $location[0]) || !is_finite((float) $location[1])
            || abs($location[0]) > 180 || abs($location[1]) > 90) {
            return null;
        }
        return $this->straightDistance($latitude, $longitude, (float) $location[1], (float) $location[0]);
    }

    private function validGeometry($geometry): bool
    {
        if (!is_array($geometry) || ($geometry['type'] ?? '') !== 'LineString'
            || !is_array($geometry['coordinates'] ?? null)
            || count($geometry['coordinates']) < 2 || count($geometry['coordinates']) > 50000) {
            return false;
        }
        foreach ($geometry['coordinates'] as $coordinate) {
            if (!is_array($coordinate) || count($coordinate) < 2
                || !(is_int($coordinate[0]) || is_float($coordinate[0]))
                || !(is_int($coordinate[1]) || is_float($coordinate[1]))
                || !is_finite((float) $coordinate[0]) || !is_finite((float) $coordinate[1])
                || abs($coordinate[0]) > 180 || abs($coordinate[1]) > 90) {
                return false;
            }
        }
        return true;
    }
}
