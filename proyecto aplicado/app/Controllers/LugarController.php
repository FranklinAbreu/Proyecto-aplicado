<?php

declare(strict_types=1);

final class LugarController
{
    private Lugar $model;
    private array $app;

    public function __construct(Lugar $model, array $app)
    {
        $this->model = $model;
        $this->app = $app;
    }

    public function handle(string $page): void
    {
        $data = [
            'app' => $this->app,
            'isDemo' => $this->model->isDemo(),
            'currentPage' => $page,
            'catalogTotal' => count($this->model->all()),
            'provinceTotal' => count($this->model->options('provincia')),
        ];

        if ($page === 'inicio' || $page === 'lugares') {
            $query = input_text('q');
            $province = input_text('provincia');
            $type = input_text('tipo');
            $mapPlaces = [];
            $mapOrigins = [];
            foreach ($this->model->all() as $place) {
                if (maps_url($place) === null) {
                    continue;
                }
                $mapPlaces[] = [
                    'id' => (int) $place['id'],
                    'nombre' => $place['nombre'],
                    'provincia' => $place['provincia'],
                    'tipo' => $place['tipo'],
                    'latitud' => (float) $place['latitud'],
                    'longitud' => (float) $place['longitud'],
                    'url' => url(['vista' => 'detalle', 'id' => $place['id']]),
                ];
                $originNames = [201 => 'Cotuí', 203 => 'Fantino'];
                if (isset($originNames[(int) $place['id']])) {
                    $mapOrigins[] = [
                        'nombre' => $originNames[(int) $place['id']],
                        'latitud' => (float) $place['latitud'],
                        'longitud' => (float) $place['longitud'],
                    ];
                }
            }
            render('lugares/index', array_merge($data, [
                'title' => $page === 'inicio' ? 'Descubre República Dominicana' : 'Explorar lugares',
                'places' => $this->model->search($query, $province, $type),
                'query' => $query,
                'province' => $province,
                'type' => $type,
                'provinces' => $this->model->options('provincia'),
                'types' => $this->model->options('tipo'),
                'mapPlaces' => $mapPlaces,
                'mapOrigins' => $mapOrigins,
            ]));
            return;
        }

        if ($page === 'detalle') {
            $rawId = $_GET['id'] ?? null;
            $id = is_string($rawId) ? filter_var($rawId, FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1],
            ]) : false;
            $place = $id !== false ? $this->model->find($id) : null;
            if ($place !== null) {
                render('lugares/detail', array_merge($data, ['title' => $place['nombre'], 'place' => $place]));
                return;
            }
        }

        http_response_code(404);
        render('errors/404', array_merge($data, ['title' => 'Página no encontrada']));
    }
}
