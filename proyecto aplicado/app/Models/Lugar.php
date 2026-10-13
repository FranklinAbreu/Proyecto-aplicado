<?php

declare(strict_types=1);

final class Lugar
{
    private array $places;
    private bool $demo;

    public function __construct(array $places, bool $demo = false)
    {
        $this->places = $places;
        $this->demo = $demo;
    }

    public static function fromDatabase(PDO $connection): self
    {
        // Primera versión: catálogo pequeño, sin consultas construidas con entrada del usuario.
        $places = $connection->query(
            'SELECT * FROM lugares WHERE activo = 1 ORDER BY nombre ASC'
        )->fetchAll();

        return new self($places);
    }

    public function isDemo(): bool
    {
        return $this->demo;
    }

    public function all(): array
    {
        return $this->places;
    }

    public function search(string $query, string $province, string $type): array
    {
        $normalizedQuery = self::normalizeSearch($query);
        return array_values(array_filter($this->places, function (array $place) use ($normalizedQuery, $province, $type): bool {
            $searchable = $place['nombre'] . ' ' . $place['descripcion'] . ' ' . $place['provincia'] . ' ' . $place['ubicacion'];
            return ($normalizedQuery === '' || mb_strpos(self::normalizeSearch($searchable), $normalizedQuery, 0, 'UTF-8') !== false)
                && ($province === '' || $place['provincia'] === $province)
                && ($type === '' || $place['tipo'] === $type);
        }));
    }

    private static function normalizeSearch(string $value): string
    {
        // Permite encontrar Limón o Rincón aunque se escriban sin tilde.
        return strtr(mb_strtolower($value, 'UTF-8'), [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u',
        ]);
    }

    public function find(int $id): ?array
    {
        foreach ($this->places as $place) {
            if ((int) $place['id'] === $id) {
                return $place;
            }
        }

        return null;
    }

    public function options(string $field): array
    {
        $options = array_values(array_unique(array_column($this->places, $field)));
        sort($options, SORT_STRING);
        return $options;
    }
}
