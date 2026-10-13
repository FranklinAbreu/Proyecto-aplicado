<?php

declare(strict_types=1);

final class HotelController
{
    public function __construct(private Hotel $hotels, private Lugar $places, private array $app, private bool $unavailable = false)
    {
    }

    public function handle(string $page): void
    {
        $data = [
            'app' => $this->app, 'isDemo' => $this->hotels->isDemo(), 'currentPage' => $page,
            'catalogTotal' => count($this->places->all()), 'provinceTotal' => count($this->places->options('provincia')),
            'hotelTotal' => count($this->hotels->all()), 'hotelUnavailable' => $this->unavailable,
        ];
        if ($page === 'hoteles') {
            $query = input_text('q'); $province = input_text('provincia'); $municipio = input_text('municipio');
            $hotels = $this->hotels->search($query, $province, $municipio);
            $destination = null;
            if (array_key_exists('destino', $_GET)) {
                $id = self::id($_GET['destino']);
                $destination = $id === null ? null : $this->places->find($id);
                if ($destination === null) {
                    $this->notFound($data);
                    return;
                }
                $related = $this->hotels->forPlace($destination, max(1, count($this->hotels->all())));
                $allowed = array_flip(array_column($hotels, 'id'));
                $hotels = array_values(array_filter($related, static fn (array $hotel): bool => isset($allowed[$hotel['id']])));
            }
            render('hotels/index', array_merge($data, [
                'title' => 'Hoteles y alojamientos', 'hotels' => $hotels, 'query' => $query,
                'province' => $province, 'municipio' => $municipio,
                'provinces' => $this->hotels->options('provincia'), 'municipios' => $this->hotels->options('municipio'),
                'destinationFilter' => $destination,
            ]));
            return;
        }
        if ($page === 'hotel') {
            $id = self::id($_GET['id'] ?? null);
            $hotel = $id === null ? null : $this->hotels->find($id);
            if ($hotel !== null) {
                $ids = array_map('intval', $hotel['destino_ids'] ?? []);
                $nearbyPlaces = array_values(array_filter($this->places->all(), static fn (array $place): bool => in_array((int) $place['id'], $ids, true)));
                render('hotels/detail', array_merge($data, ['title' => $hotel['nombre'], 'hotel' => $hotel, 'nearbyPlaces' => $nearbyPlaces]));
                return;
            }
        }
        $this->notFound($data);
    }

    private static function id($value): ?int
    {
        if (!is_string($value) || !preg_match('/\A[1-9][0-9]{0,9}\z/D', $value)) { return null; }
        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 4294967295]]);
        return $id === false ? null : $id;
    }

    private function notFound(array $data): void
    {
        http_response_code(404);
        render('errors/404', array_merge($data, ['title' => 'Alojamiento no encontrado']));
    }
}
