<?php

declare(strict_types=1);

final class Hotel
{
    private array $hotels;
    private bool $demo;

    public function __construct(array $hotels, bool $demo = false)
    {
        $this->hotels = array_values(array_filter($hotels, static fn (array $hotel): bool =>
            (int) ($hotel['activo'] ?? 1) === 1
        ));
        $this->demo = $demo;
    }

    public static function fromDatabase(PDO $connection): self
    {
        $hotels = $connection->query('SELECT * FROM hoteles WHERE activo = 1 ORDER BY nombre ASC')
            ->fetchAll(PDO::FETCH_ASSOC);
        $links = $connection->query(
            'SELECT hl.hotel_id, hl.destino_id FROM hotel_lugar hl
             INNER JOIN hoteles h ON h.id = hl.hotel_id
             INNER JOIN lugares l ON l.id = hl.destino_id
             WHERE h.activo = 1 AND l.activo = 1
             ORDER BY hl.hotel_id, hl.destino_id'
        )->fetchAll(PDO::FETCH_ASSOC);
        $byHotel = [];
        foreach ($links as $link) {
            $byHotel[(int) $link['hotel_id']][] = (int) $link['destino_id'];
        }
        foreach ($hotels as &$hotel) {
            $hotel['destino_ids'] = $byHotel[(int) $hotel['id']] ?? [];
        }
        unset($hotel);

        return new self($hotels);
    }

    public function isDemo(): bool
    {
        return $this->demo;
    }

    public function all(): array
    {
        return $this->hotels;
    }

    public function find(int $id): ?array
    {
        foreach ($this->hotels as $hotel) {
            if ((int) $hotel['id'] === $id) {
                return $hotel;
            }
        }
        return null;
    }

    public function search(string $query, string $province, string $municipio = ''): array
    {
        $normalized = self::normalizeSearch($query);
        return array_values(array_filter($this->hotels, static function (array $hotel) use ($normalized, $province, $municipio): bool {
            $text = implode(' ', [
                $hotel['nombre'] ?? '', $hotel['descripcion'] ?? '', $hotel['provincia'] ?? '',
                $hotel['municipio'] ?? '', $hotel['ubicacion'] ?? '', $hotel['servicios'] ?? '',
            ]);
            return ($normalized === '' || str_contains(self::normalizeSearch($text), $normalized))
                && ($province === '' || ($hotel['provincia'] ?? '') === $province)
                && ($municipio === '' || ($hotel['municipio'] ?? '') === $municipio);
        }));
    }

    public function options(string $field): array
    {
        $values = [];
        foreach ($this->hotels as $hotel) {
            $value = $hotel[$field] ?? null;
            if (is_string($value) && $value !== '') {
                $values[] = $value;
            }
        }
        $values = array_values(array_unique($values));
        sort($values, SORT_STRING);
        return $values;
    }

    /** Solo muestra asociaciones guardadas. distance_km es distancia en línea recta. */
    public function forPlace(array $place, int $limit = 3): array
    {
        $placeId = (int) ($place['id'] ?? 0);
        if ($placeId < 1 || $limit < 1 || (isset($place['activo']) && (int) $place['activo'] !== 1)) {
            return [];
        }
        $coordinates = self::coordinates($place);
        $matches = [];
        foreach ($this->hotels as $hotel) {
            $ids = is_array($hotel['destino_ids'] ?? null) ? array_map('intval', $hotel['destino_ids']) : [];
            if (!in_array($placeId, $ids, true)) {
                continue;
            }
            $hotelCoordinates = self::coordinates($hotel);
            $hotel['distance_km'] = $coordinates !== null && $hotelCoordinates !== null
                ? self::distance($coordinates, $hotelCoordinates) : null;
            $matches[] = $hotel;
        }
        usort($matches, static function (array $first, array $second): int {
            $firstDistance = $first['distance_km'];
            $secondDistance = $second['distance_km'];
            if ($firstDistance === null && $secondDistance !== null) { return 1; }
            if ($firstDistance !== null && $secondDistance === null) { return -1; }
            if ($firstDistance !== null && $secondDistance !== null && $firstDistance !== $secondDistance) {
                return $firstDistance <=> $secondDistance;
            }
            $nameOrder = strnatcasecmp($first['nombre'] ?? '', $second['nombre'] ?? '');
            return $nameOrder !== 0 ? $nameOrder : ((int) $first['id'] <=> (int) $second['id']);
        });
        return array_slice($matches, 0, $limit);
    }

    private static function normalizeSearch(string $value): string
    {
        return strtr(mb_strtolower(trim($value), 'UTF-8'), [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u',
        ]);
    }

    private static function coordinates(array $record): ?array
    {
        $latitude = $record['latitud'] ?? null;
        $longitude = $record['longitud'] ?? null;
        if (!is_numeric($latitude) || !is_numeric($longitude)
            || !is_finite((float) $latitude) || !is_finite((float) $longitude)
            || abs((float) $latitude) > 90 || abs((float) $longitude) > 180) {
            return null;
        }
        return [(float) $latitude, (float) $longitude];
    }

    private static function distance(array $origin, array $destination): float
    {
        $latitudeDelta = deg2rad($destination[0] - $origin[0]);
        $longitudeDelta = deg2rad($destination[1] - $origin[1]);
        $a = sin($latitudeDelta / 2) ** 2
            + cos(deg2rad($origin[0])) * cos(deg2rad($destination[0])) * sin($longitudeDelta / 2) ** 2;
        $a = max(0.0, min(1.0, $a));
        return 6371.0 * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
