<?php

declare(strict_types=1);

final class AdminController
{
    private ?AdminAuth $auth = null;
    private ?AdminLugar $places = null;

    public function __construct(private ?PDO $connection, private array $app)
    {
    }

    public function handle(string $page): void
    {
        header('Cache-Control: no-store, private');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: same-origin');
        if ($this->connection === null || !AdminSchema::isReady($this->connection)) {
            $this->show('error', 'Panel no disponible', [
                'errors' => ['La base de datos del panel aún no está preparada. Revisa la instalación del proyecto.'],
            ], 503);
            return;
        }

        try {
            $this->auth = new AdminAuth($this->connection);
            $this->places = new AdminLugar($this->connection);
            if ($page === 'admin_setup' || $page === 'admin_login') {
                $this->access($page);
                return;
            }
            if ($this->auth->currentUser() === null) {
                $this->redirect($this->auth->canSetup() ? 'admin_setup' : 'admin_login');
                return;
            }
            switch ($page) {
                case 'admin_logout':
                    if (!$this->checkPost()) { return; }
                    $this->auth->logout();
                    $this->redirect('admin_login');
                    return;
                case 'admin_guardar':
                    $this->save();
                    return;
                case 'admin_nuevo':
                case 'admin_editar':
                    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
                        $this->methodError();
                        return;
                    }
                    $values = ['activo' => 1, 'foto_fuente_label' => 'Fuente de la fotografía'];
                    if ($page === 'admin_editar') {
                        $id = self::parseId($_GET['id'] ?? null);
                        $place = $id === null ? null : $this->places->find($id);
                        if ($place === null) {
                            $this->show('error', 'Destino no encontrado', [], 404);
                            return;
                        }
                        $values = self::withMetadata($place);
                    }
                    $this->show('form', $page === 'admin_editar' ? 'Editar destino' : 'Agregar destino', ['values' => $values]);
                    return;
                default:
                    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
                        $this->methodError();
                        return;
                    }
                    $this->dashboard();
            }
        } catch (PDOException $exception) {
            error_log('CercaRD: no se pudo completar una operación del panel de administración.');
            $this->auth = null;
            $this->show('error', 'No se pudo guardar la información', ['errors' => ['Comprueba que MySQL siga activo y vuelve a intentarlo.']], 503);
        }
    }

    private function access(string $page): void
    {
        if ($this->auth->currentUser() !== null) {
            $this->redirect('admin');
            return;
        }
        $setup = $page === 'admin_setup';
        if ($setup && !$this->auth->canSetup()) {
            if ($this->auth->hasAdministrators()) {
                $this->redirect('admin_login');
            } else {
                $this->show('setup', 'Configuración inicial', [], 403);
            }
            return;
        }
        if (!$setup && $this->auth->canSetup() && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
            $this->redirect('admin_setup');
            return;
        }
        $errors = [];
        $username = '';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if ($method === 'POST') {
            if (!$this->checkPost()) { return; }
            $username = is_string($_POST['usuario'] ?? null) ? trim($_POST['usuario']) : '';
            $password = is_string($_POST['clave'] ?? null) ? $_POST['clave'] : '';
            try {
                if ($setup) {
                    $confirmation = $_POST['confirmar_clave'] ?? null;
                    if (!is_string($confirmation) || $password !== $confirmation) {
                        throw new InvalidArgumentException('Las contraseñas deben coincidir.');
                    }
                    $this->auth->setup($username, $password);
                    $_SESSION['admin_message'] = 'Tu cuenta está lista. Ya puedes administrar los destinos.';
                    $this->redirect('admin');
                    return;
                }
                if (strlen($username) <= 40 && $this->auth->login($username, $password)) {
                    $this->redirect('admin');
                    return;
                }
                $errors[] = 'El usuario o la contraseña no son correctos.';
            } catch (InvalidArgumentException $exception) {
                $errors[] = $exception->getMessage();
            }
        } elseif ($method !== 'GET') {
            $this->methodError();
            return;
        }
        $this->show($setup ? 'setup' : 'login', $setup ? 'Crea tu administrador' : 'Acceso al panel', [
            'errors' => $errors, 'values' => ['usuario' => mb_substr($username, 0, 40, 'UTF-8')],
        ], $errors ? 422 : 200);
    }

    private function dashboard(): void
    {
        $places = array_map([self::class, 'withMetadata'], $this->places->all());
        $stats = ['total' => count($places), 'activos' => 0, 'sin_coordenadas' => 0];
        foreach ($places as $place) {
            $stats['activos'] += (int) $place['activo'] === 1 ? 1 : 0;
            $stats['sin_coordenadas'] += !is_numeric($place['latitud']) || !is_numeric($place['longitud']) ? 1 : 0;
        }
        $query = input_text('q');
        if ($query !== '') {
            $normalized = self::searchText($query);
            $places = array_values(array_filter($places, static fn (array $place): bool =>
                str_contains(self::searchText($place['nombre'] . ' ' . $place['provincia'] . ' ' . $place['tipo']), $normalized)
            ));
        }
        $message = $_SESSION['admin_message'] ?? null;
        unset($_SESSION['admin_message']);
        $this->show('index', 'Tus destinos', ['places' => $places, 'stats' => $stats, 'message' => $message]);
    }

    private function save(): void
    {
        if (!$this->checkPost()) { return; }
        $rawId = $_POST['id'] ?? '';
        $id = $rawId === '' ? null : self::parseId($rawId);
        if ($rawId !== '' && $id === null) {
            $this->show('error', 'Destino no válido', [], 400);
            return;
        }
        $current = $id === null ? [] : $this->places->find($id);
        if ($current === null) {
            $this->show('error', 'Destino no encontrado', [], 404);
            return;
        }
        $current = self::withMetadata($current);
        [$data, $errors] = self::validatePlace($_POST);
        foreach (['foto_autor_url', 'foto_licencia_url', 'foto_fuente_label'] as $key) {
            if (!array_key_exists($key, $_POST) && array_key_exists($key, $current)) {
                $data[$key] = $current[$key];
            }
        }
        $data['imagen'] = $current['imagen'] ?? '';
        $file = $_FILES['foto'] ?? ['error' => UPLOAD_ERR_NO_FILE];
        // Primero se valida el formulario; ninguna foto se guarda con datos inválidos.
        if ($errors === []) {
            try {
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
                $this->places->save($id, $data);
                $_SESSION['admin_message'] = $id === null ? 'Destino creado correctamente.' : 'Los cambios se guardaron correctamente.';
                $this->redirect('admin');
                return;
            } catch (InvalidArgumentException | RuntimeException $exception) {
                if ($exception instanceof PDOException) { throw $exception; }
                $errors[] = $exception->getMessage();
            }
        }
        $values = array_replace($current, $data, ['id' => $id ?? '']);
        $this->show('form', $id === null ? 'Agregar destino' : 'Editar destino', ['values' => $values, 'errors' => $errors], 422);
    }

    public static function validatePlace(array $post): array
    {
        $data = [];
        $errors = [];
        $fields = [
            'nombre' => [150, 'Nombre del destino', true],
            'provincia' => [100, 'Provincia', true], 'tipo' => [50, 'Tipo de lugar', true],
            'descripcion' => [5000, 'Descripción', true], 'ubicacion' => [255, 'Dirección o referencia', true],
            'imagen_alt' => [255, 'Descripción de la fotografía', false],
            'foto_autor' => [150, 'Autor de la fotografía', false],
            'foto_autor_url' => [500, 'Enlace del autor', false],
            'foto_fuente' => [500, 'Enlace de la fotografía', false],
            'foto_fuente_label' => [100, 'Nombre de la fuente', false],
            'foto_licencia' => [100, 'Licencia o permiso', false],
            'foto_licencia_url' => [500, 'Enlace de la licencia', false],
            'fuente_url' => [500, 'Fuente de la información', false],
            'destacado' => [150, 'Frase para la tarjeta', false], 'region' => [100, 'Región', false],
        ];
        foreach ($fields as $key => [$max, $label, $required]) {
            $raw = $post[$key] ?? '';
            $value = is_string($raw) ? trim($raw) : '';
            if (!is_string($raw) || !mb_check_encoding($value, 'UTF-8') || str_contains($value, "\0")) {
                $errors[] = $label . ': escribe un texto válido.';
                $value = '';
            } elseif ($required && $value === '') {
                $errors[] = $label . ': este campo es obligatorio.';
            } elseif (mb_strlen($value, 'UTF-8') > $max) {
                $errors[] = $label . ': usa como máximo ' . $max . ' caracteres.';
            }
            $data[$key] = $value;
        }
        if (!in_array($data['tipo'], ['Playa', 'Río', 'Parque', 'Montaña', 'Cultural'], true)) {
            $errors[] = 'Selecciona un tipo de lugar disponible.';
        }
        foreach (['foto_autor_url', 'foto_fuente', 'foto_licencia_url', 'fuente_url'] as $key) {
            if ($data[$key] !== '') {
                $parts = parse_url($data[$key]);
                if (!filter_var($data[$key], FILTER_VALIDATE_URL) || $parts === false
                    || !in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)
                    || isset($parts['user']) || isset($parts['pass'])) {
                    $errors[] = $fields[$key][1] . ': usa un enlace http:// o https:// válido.';
                }
            }
        }
        $lat = $post['latitud'] ?? '';
        $lng = $post['longitud'] ?? '';
        $data['latitud'] = $data['longitud'] = null;
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

    private function checkPost(): bool
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->methodError();
            return false;
        }
        if (!$this->auth->verifyToken($_POST['csrf_token'] ?? null)) {
            $this->show('error', 'Vuelve a abrir el formulario', ['errors' => ['El formulario venció o la carga fue demasiado grande. Abre el formulario de nuevo; la foto debe pesar como máximo 5 MB.']], 403);
            return false;
        }
        return true;
    }

    private function methodError(): void
    {
        header('Allow: GET, POST');
        $this->show('error', 'Acción no disponible', [], 405);
    }

    private static function parseId($raw): ?int
    {
        if (!is_string($raw) || !preg_match('/\A[1-9][0-9]{0,9}\z/D', $raw)) { return null; }
        $id = filter_var($raw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 4294967295]]);
        return $id === false ? null : $id;
    }

    public static function withMetadata(array $place): array
    {
        $metadata = destination_meta($place);
        foreach (destination_columns() as $column => $key) {
            $place[$column] = $metadata[$key];
        }
        return $place;
    }

    private static function searchText(string $text): string
    {
        return strtr(mb_strtolower($text, 'UTF-8'), ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u']);
    }

    private function show(string $view, string $title, array $data = [], int $status = 200): void
    {
        http_response_code($status);
        renderAdmin($view, array_replace([
            'title' => $title, 'app' => $this->app, 'currentUser' => $this->auth?->currentUser(),
            'csrfToken' => $this->auth?->token() ?? '', 'canSetup' => $this->auth?->canSetup() ?? false,
            'dbAvailable' => $this->auth !== null, 'errors' => [], 'values' => [], 'message' => null,
            'places' => [], 'stats' => ['total' => 0, 'activos' => 0, 'sin_coordenadas' => 0],
        ], $data));
    }

    private function redirect(string $page): void
    {
        header('Location: ' . url(['vista' => $page]), true, 303);
    }
}
