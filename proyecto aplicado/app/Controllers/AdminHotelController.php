<?php

declare(strict_types=1);

final class AdminHotelController
{
    private ?AdminAuth $auth = null;
    private ?AdminHotel $hotels = null;

    public function __construct(private ?PDO $connection, private array $app) {}

    public function handle(string $page): void
    {
        header('Cache-Control: no-store, private');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: same-origin');
        if ($this->connection === null || !AdminSchema::isReady($this->connection) || !HotelSchema::isReady($this->connection)) {
            $this->show('error', 'Hoteles no disponibles', ['errors' => ['La base de datos de hoteles aún no está preparada. Revisa la instalación del proyecto.']], 503);
            return;
        }
        try {
            $this->auth = new AdminAuth($this->connection);
            $this->hotels = new AdminHotel($this->connection);
            if ($this->auth->currentUser() === null) {
                $this->redirect($this->auth->canSetup() ? 'admin_setup' : 'admin_login');
                return;
            }
            if ($page === 'admin_hotel_guardar') {
                $this->save();
                return;
            }
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
                $this->methodError();
                return;
            }
            if ($page === 'admin_hotel_nuevo' || $page === 'admin_hotel_editar') {
                $values = ['activo' => 1, 'destino_ids' => [], 'foto_fuente_label' => 'Fuente de la fotografía'];
                if ($page === 'admin_hotel_editar') {
                    $id = self::parseId($_GET['id'] ?? null);
                    $values = $id === null ? null : $this->hotels->find($id);
                    if ($values === null) {
                        $this->show('error', 'Hotel no encontrado', [], 404);
                        return;
                    }
                }
                $this->show('hotel_form', $page === 'admin_hotel_editar' ? 'Editar hotel' : 'Agregar hotel', ['values' => $values, 'destinations' => $this->destinations()]);
                return;
            }
            $this->dashboard();
        } catch (PDOException $exception) {
            error_log('CercaRD: no se pudo completar una operación del panel de hoteles.');
            $this->auth = null;
            $this->show('error', 'No se pudo guardar la información', ['errors' => ['Comprueba que MySQL siga activo y vuelve a intentarlo.']], 503);
        }
    }

    private function dashboard(): void
    {
        $hotels = $this->hotels->all();
        $stats = ['total' => count($hotels), 'activos' => 0, 'sin_coordenadas' => 0];
        foreach ($hotels as $hotel) {
            $stats['activos'] += (int) $hotel['activo'] === 1 ? 1 : 0;
            $stats['sin_coordenadas'] += !is_numeric($hotel['latitud']) || !is_numeric($hotel['longitud']) ? 1 : 0;
        }
        $query = input_text('q');
        if ($query !== '') {
            $needle = self::searchText($query);
            $hotels = array_values(array_filter($hotels, static fn (array $hotel): bool =>
                str_contains(self::searchText($hotel['nombre'] . ' ' . $hotel['provincia'] . ' ' . $hotel['municipio']), $needle)
            ));
        }
        $message = $_SESSION['admin_hotel_message'] ?? null;
        unset($_SESSION['admin_hotel_message']);
        $this->show('hotel_index', 'Hoteles', ['hotels' => $hotels, 'stats' => $stats, 'message' => $message, 'destinations' => $this->destinations()]);
    }

    private function save(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->methodError();
            return;
        }
        if (!$this->auth->verifyToken($_POST['csrf_token'] ?? null)) {
            $this->show('error', 'Vuelve a abrir el formulario', ['errors' => ['El formulario venció o la carga fue demasiado grande. Abre el formulario de nuevo; la foto debe pesar como máximo 5 MB.']], 403);
            return;
        }
        $rawId = $_POST['id'] ?? '';
        $id = $rawId === '' ? null : self::parseId($rawId);
        if ($rawId !== '' && $id === null) {
            $this->show('error', 'Hotel no válido', [], 400);
            return;
        }
        $current = $id === null ? [] : $this->hotels->find($id);
        if ($current === null) {
            $this->show('error', 'Hotel no encontrado', [], 404);
            return;
        }
        [$data, $errors] = self::validateHotel($_POST);
        $destinations = $this->destinations();
        $available = array_fill_keys(array_column($destinations, 'id'), true);
        $destinationIds = [];
        $rawDestinations = $_POST['destino_ids'] ?? [];
        if (!is_array($rawDestinations)) {
            $errors[] = 'Selecciona los destinos de la lista disponible.';
        } else {
            foreach ($rawDestinations as $rawDestinationId) {
                $destinationId = self::parseId($rawDestinationId);
                if ($destinationId === null || !isset($available[$destinationId])) {
                    $errors[] = 'Uno de los destinos seleccionados no está disponible. Revisa la selección.';
                    continue;
                }
                $destinationIds[$destinationId] = $destinationId;
            }
        }
        $destinationIds = array_values($destinationIds);
        foreach (['foto_autor_url', 'foto_licencia_url', 'foto_fuente_label'] as $field) {
            if (!array_key_exists($field, $_POST) && array_key_exists($field, $current)) {
                $data[$field] = $current[$field];
            }
        }
        // La fotografía y la fecha de verificación no se pueden elegir mediante campos ocultos.
        $data['imagen'] = $current['imagen'] ?? '';
        $data['verificado_en'] = $current['verificado_en'] ?? null;
        if ($errors === []) {
            try {
                $file = $_FILES['foto'] ?? ['error' => UPLOAD_ERR_NO_FILE];
                if (!is_array($file)) {
                    throw new InvalidArgumentException('Selecciona una fotografía válida.');
                }
                $image = PhotoUpload::store($file);
                if ($image !== null) {
                    $data['imagen'] = $image;
                }
                if ($data['imagen_alt'] === '') {
                    $data['imagen_alt'] = $data['nombre'];
                }
                $this->hotels->save($id, $data, $destinationIds);
                $_SESSION['admin_hotel_message'] = $id === null ? 'Hotel creado correctamente.' : 'Los cambios del hotel se guardaron correctamente.';
                $this->redirect('admin_hoteles');
                return;
            } catch (InvalidArgumentException | RuntimeException $exception) {
                if ($exception instanceof PDOException) { throw $exception; }
                $errors[] = $exception->getMessage();
            }
        }
        $values = array_replace($current, $data, ['id' => $id ?? '', 'destino_ids' => $destinationIds]);
        $this->show('hotel_form', $id === null ? 'Agregar hotel' : 'Editar hotel', ['values' => $values, 'errors' => array_values(array_unique($errors)), 'destinations' => $destinations], 422);
    }

    public static function validateHotel(array $post): array
    {
        $data = [];
        $errors = [];
        $fields = [
            'nombre' => [150, 'Nombre del hotel', true], 'provincia' => [100, 'Provincia', true],
            'municipio' => [100, 'Municipio', true], 'descripcion' => [5000, 'Descripción', true],
            'ubicacion' => [255, 'Dirección o referencia', true], 'telefono' => [50, 'Teléfono', false],
            'sitio_web' => [500, 'Sitio web', false], 'reserva_url' => [500, 'Enlace de reserva', false],
            'servicios' => [1000, 'Servicios', false], 'imagen_alt' => [255, 'Descripción de la fotografía', false],
            'foto_autor' => [150, 'Autor de la fotografía', false], 'foto_autor_url' => [500, 'Enlace del autor', false],
            'foto_fuente' => [500, 'Enlace de la fotografía', false], 'foto_fuente_label' => [100, 'Nombre de la fuente', false],
            'foto_licencia' => [100, 'Licencia o permiso', false], 'foto_licencia_url' => [500, 'Enlace de la licencia', false],
            'fuente_url' => [500, 'Fuente de la información', false],
        ];
        foreach ($fields as $field => [$maximum, $label, $required]) {
            $raw = $post[$field] ?? '';
            $value = is_string($raw) ? trim($raw) : '';
            if (!is_string($raw) || !mb_check_encoding($value, 'UTF-8') || str_contains($value, "\0")) {
                $errors[] = $label . ': escribe un texto válido.';
                $value = '';
            } elseif ($required && $value === '') {
                $errors[] = $label . ': este campo es obligatorio.';
            } elseif (mb_strlen($value, 'UTF-8') > $maximum) {
                $errors[] = $label . ': usa como máximo ' . $maximum . ' caracteres.';
            }
            $data[$field] = $value;
        }
        foreach (['sitio_web', 'reserva_url', 'foto_autor_url', 'foto_fuente', 'foto_licencia_url', 'fuente_url'] as $field) {
            if ($data[$field] !== '') {
                $parts = parse_url($data[$field]);
                if (!filter_var($data[$field], FILTER_VALIDATE_URL) || $parts === false
                    || !in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)
                    || isset($parts['user']) || isset($parts['pass'])) {
                    $errors[] = $fields[$field][1] . ': usa un enlace http:// o https:// válido.';
                }
            }
        }
        $data['latitud'] = $data['longitud'] = null;
        $lat = $post['latitud'] ?? '';
        $lng = $post['longitud'] ?? '';
        if (!is_string($lat) || !is_string($lng)) {
            $errors[] = 'Escribe coordenadas válidas.';
        } else {
            $lat = trim($lat); $lng = trim($lng);
            if (($lat === '') !== ($lng === '')) {
                $errors[] = 'Completa ambas coordenadas o deja las dos vacías.';
            } elseif ($lat !== '') {
                if (!is_numeric($lat) || !is_numeric($lng) || !is_finite((float) $lat) || !is_finite((float) $lng)
                    || abs((float) $lat) > 90 || abs((float) $lng) > 180) {
                    $errors[] = 'La latitud debe estar entre -90 y 90, y la longitud entre -180 y 180.';
                } else {
                    $data['latitud'] = round((float) $lat, 7);
                    $data['longitud'] = round((float) $lng, 7);
                }
            }
        }
        $active = $post['activo'] ?? '0';
        if (!is_string($active) || !in_array($active, ['0', '1'], true)) {
            $errors[] = 'El estado de publicación no es válido.';
        }
        $data['activo'] = $active === '1' ? 1 : 0;
        return [$data, $errors];
    }

    private function destinations(): array
    {
        return $this->connection->query('SELECT id, nombre, activo FROM lugares ORDER BY nombre ASC')->fetchAll(PDO::FETCH_ASSOC);
    }

    private static function parseId($raw): ?int
    {
        if (!is_string($raw) || !preg_match('/\A[1-9][0-9]{0,9}\z/D', $raw)) { return null; }
        $id = filter_var($raw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 4294967295]]);
        return $id === false ? null : $id;
    }

    private static function searchText(string $text): string
    {
        return strtr(mb_strtolower($text, 'UTF-8'), ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u']);
    }

    private function methodError(): void
    {
        header('Allow: GET, POST');
        $this->show('error', 'Acción no disponible', [], 405);
    }

    private function redirect(string $page): void
    {
        header('Location: ' . url(['vista' => $page]), true, 303);
    }

    private function show(string $view, string $title, array $data = [], int $status = 200): void
    {
        http_response_code($status);
        renderAdmin($view, array_replace([
            'title' => $title, 'app' => $this->app, 'currentUser' => $this->auth?->currentUser(),
            'csrfToken' => $this->auth?->token() ?? '', 'canSetup' => $this->auth?->canSetup() ?? false,
            'dbAvailable' => $this->auth !== null, 'errors' => [], 'values' => [], 'message' => null,
            'hotels' => [], 'destinations' => [], 'stats' => ['total' => 0, 'activos' => 0, 'sin_coordenadas' => 0],
        ], $data));
    }
}
