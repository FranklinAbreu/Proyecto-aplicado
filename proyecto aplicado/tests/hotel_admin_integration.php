<?php

declare(strict_types=1);

// CLI-only integration against a separate server and a new, explicitly named test database.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
define('PROJECT_ROOT', dirname(__DIR__));
require PROJECT_ROOT . '/app/Core/helpers.php';
require PROJECT_ROOT . '/app/Core/AdminSchema.php';
require PROJECT_ROOT . '/app/Core/HotelSchema.php';
require PROJECT_ROOT . '/app/Core/AdminAuth.php';
require PROJECT_ROOT . '/app/Models/AdminHotel.php';
require PROJECT_ROOT . '/app/Models/Hotel.php';
require PROJECT_ROOT . '/app/Controllers/AdminHotelController.php';

function hotelExpect(bool $condition, string $description): void
{
    global $hotelChecks;
    if (!$condition) { throw new RuntimeException('Fallo: ' . $description); }
    ++$hotelChecks;
}

function hotelFields(string $name, string $token): array
{
    return [
        'id' => '', 'csrf_token' => $token, 'nombre' => $name, 'provincia' => 'Sánchez Ramírez',
        'municipio' => 'Cotuí', 'descripcion' => 'Alojamiento exclusivo de la prueba aislada.',
        'ubicacion' => 'Cotuí, Sánchez Ramírez', 'latitud' => '19.05833', 'longitud' => '-70.15302',
        'telefono' => '', 'sitio_web' => 'https://example.org/hotel', 'reserva_url' => '',
        'servicios' => 'Wi-Fi, estacionamiento', 'imagen_alt' => 'Imagen de prueba',
        'foto_autor' => 'Fotógrafo de prueba', 'foto_autor_url' => 'https://example.org/autor',
        'foto_fuente' => 'https://example.org/foto', 'foto_fuente_label' => 'Foto de prueba',
        'foto_licencia' => 'CC BY 4.0', 'foto_licencia_url' => 'https://creativecommons.org/licenses/by/4.0/',
        'fuente_url' => 'https://example.org/hotel', 'activo' => '1', 'destino_ids' => ['201', '203'],
    ];
}

final class HotelBrowser
{
    public function __construct(private string $base, private string $cookies) {}
    public function request(string $view, ?array $data = null, array $query = []): array
    {
        $handle = curl_init($this->base . '/index.php?' . http_build_query(['vista' => $view] + $query));
        curl_setopt_array($handle, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_TIMEOUT => 15,
            CURLOPT_FOLLOWLOCATION => false, CURLOPT_COOKIEFILE => $this->cookies, CURLOPT_COOKIEJAR => $this->cookies]);
        if ($data !== null) {
            $hasFile = count(array_filter($data, static fn ($value): bool => $value instanceof CURLFile)) > 0;
            if ($hasFile) {
                // cURL multipart arrays must be serialized to bracketed field names explicitly.
                $flattened = [];
                foreach ($data as $key => $value) {
                    if (is_array($value)) {
                        foreach ($value as $position => $item) { $flattened[$key . '[' . $position . ']'] = $item; }
                    } else { $flattened[$key] = $value; }
                }
                $data = $flattened;
            }
            curl_setopt($handle, CURLOPT_POST, true);
            curl_setopt($handle, CURLOPT_POSTFIELDS, $hasFile ? $data : http_build_query($data));
        }
        $response = curl_exec($handle);
        if ($response === false) { throw new RuntimeException('No se pudo consultar el servidor aislado.'); }
        $headers = (int) curl_getinfo($handle, CURLINFO_HEADER_SIZE);
        $result = ['status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'headers' => substr($response, 0, $headers), 'body' => substr($response, $headers)];
        curl_close($handle);
        return $result;
    }
}

function hotelToken(array $response): string
{
    if (!preg_match('/name="csrf_token"\s+value="([^"]+)"/', $response['body'], $match)) {
        throw new RuntimeException('El formulario de prueba no contiene CSRF.');
    }
    return html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function hotelForm(array $response): array
{
    $document = new DOMDocument();
    @$document->loadHTML('<?xml encoding="UTF-8">' . $response['body']);
    $xpath = new DOMXPath($document);
    $values = [];
    foreach ($xpath->query('//form[contains(@action,"admin_hotel_guardar")]//input[@name] | //form[contains(@action,"admin_hotel_guardar")]//textarea[@name]') as $field) {
        $type = strtolower($field->getAttribute('type'));
        if (in_array($type, ['file', 'submit'], true) || ($type === 'checkbox' && !$field->hasAttribute('checked'))) { continue; }
        $name = $field->getAttribute('name');
        $value = $field->tagName === 'textarea' ? $field->textContent : $field->getAttribute('value');
        if (str_ends_with($name, '[]')) { $values[substr($name, 0, -2)][] = $value; } else { $values[$name] = $value; }
    }
    return $values;
}

$hotelChecks = 0;
$name = getenv('TEST_DB_NAME') ?: '';
$base = rtrim(getenv('TEST_BASE_URL') ?: '', '/');
$parts = parse_url($base);
if (!preg_match('/\Aturismo_rd_test_[a-z0-9_]{6,40}\z/D', $name)
    || !is_array($parts) || !in_array($parts['host'] ?? '', ['127.0.0.1', 'localhost', '[::1]'], true)
    || !in_array($parts['scheme'] ?? '', ['http', 'https'], true) || isset($parts['user']) || isset($parts['pass'])
    || isset($parts['query']) || isset($parts['fragment'])) {
    fwrite(STDERR, "Define TEST_DB_NAME nuevo con prefijo turismo_rd_test_ y TEST_BASE_URL del servidor local aislado.\n");
    exit(1);
}
$config = require PROJECT_ROOT . '/config/database.php';
if (!in_array($config['host'], ['127.0.0.1', 'localhost', '::1'], true)) { fwrite(STDERR, "Solo se permite MySQL local.\n"); exit(1); }
$connection = null; $created = false; $temporaryFiles = []; $uploadFiles = []; $failure = null;
try {
    $connection = new PDO(sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $config['host'], $config['port']), $config['user'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    // Refuse an existing database instead of modifying any prior data.
    $connection->exec('CREATE DATABASE `' . $name . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $created = true;
    $connection->exec('USE `' . $name . '`');
    $connection->exec(str_replace('turismo_rd', $name, file_get_contents(PROJECT_ROOT . '/database/schema.sql')));
    AdminSchema::ensure($connection);
    HotelSchema::ensure($connection);
    HotelSchema::ensure($connection);
    hotelExpect(HotelSchema::isReady($connection), 'el esquema de hoteles admite una instalación repetida');
    $model = new AdminHotel($connection);
    $marker = 'Hotel fixture ' . bin2hex(random_bytes(8));
    [$data, $errors] = AdminHotelController::validateHotel(hotelFields($marker, ''));
    $data['imagen'] = 'images/parque-duarte-cotui.jpg';
    $data['verificado_en'] = '2026-10-13';
    $markerId = $model->save(null, $data, [201]);
    $visitorCookies = tempnam(sys_get_temp_dir(), 'cercard_hotel_visitor_');
    $adminCookies = tempnam(sys_get_temp_dir(), 'cercard_hotel_admin_');
    $temporaryFiles = [$visitorCookies, $adminCookies];
    $visitor = new HotelBrowser($base, $visitorCookies); $admin = new HotelBrowser($base, $adminCookies);
    // Prove that this server reads exactly this test database before sending any POST.
    $response = $visitor->request('hotel', null, ['id' => $markerId]);
    hotelExpect($response['status'] === 200 && str_contains($response['body'], $marker), 'el servidor HTTP utiliza la base temporal correcta');
    $count = static fn (): int => (int) $connection->query('SELECT COUNT(*) FROM hoteles')->fetchColumn();
    $response = $visitor->request('admin_hotel_guardar', hotelFields('Sin autorización', ''));
    hotelExpect(in_array($response['status'], [302, 303, 401, 403], true) && $count() === 1, 'un visitante no puede guardar hoteles');
    $response = $visitor->request('admin_hotel_editar', null, ['id' => $markerId]);
    hotelExpect(in_array($response['status'], [302, 303, 401, 403], true), 'el editor de hoteles requiere autenticación');
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $auth = new AdminAuth($connection);
    $password = 'SoloPruebaHotel_' . bin2hex(random_bytes(12));
    $auth->setup('hotel_prueba_admin', $password);
    session_write_close();
    $response = $admin->request('admin_login');
    $response = $admin->request('admin_login', ['usuario' => 'hotel_prueba_admin', 'clave' => $password, 'csrf_token' => hotelToken($response)]);
    hotelExpect($response['status'] === 303, 'la cuenta temporal accede mediante el login existente');
    $response = $admin->request('admin_hotel_nuevo');
    hotelExpect($response['status'] === 200 && str_contains($response['body'], 'destino_ids[]'), 'el formulario presenta destinos asociados');
    $token = hotelToken($response);
    $valid = hotelFields('Hotel de prueba ' . bin2hex(random_bytes(8)), $token);
    $response = $admin->request('admin_hotel_guardar', array_replace($valid, ['csrf_token' => 'inventado']));
    hotelExpect($response['status'] === 403 && $count() === 1, 'guardar hotel exige el token de la sesión');
    $response = $admin->request('admin_hotel_guardar');
    hotelExpect($response['status'] === 405 && $count() === 1, 'guardar hotel no admite GET');
    foreach ([['municipio' => str_repeat('a', 101)], ['telefono' => str_repeat('1', 51)], ['servicios' => str_repeat('a', 1001)],
        ['latitud' => '', 'longitud' => '-70'], ['latitud' => '91'], ['municipio' => ['Cotuí']],
        ['sitio_web' => 'javascript:alert(1)'], ['reserva_url' => 'https://user:password@example.org/'],
        ['destino_ids' => '201'], ['destino_ids' => ['999999']], ['destino_ids' => [['201']]]] as $invalid) {
        $response = $admin->request('admin_hotel_guardar', array_replace($valid, $invalid));
        hotelExpect($response['status'] === 422 && $count() === 1, 'se rechazan datos inválidos sin guardar: ' . implode(', ', array_keys($invalid)));
    }
    $photo = tempnam(sys_get_temp_dir(), 'cercard_hotel_photo_'); $temporaryFiles[] = $photo;
    $image = imagecreatetruecolor(64, 48); imagefill($image, 0, 0, imagecolorallocate($image, 0, 45, 98)); imagejpeg($image, $photo, 85); imagedestroy($image);
    $response = $admin->request('admin_hotel_guardar', $valid + ['foto' => new CURLFile($photo, 'image/jpeg', 'foto.php.jpg')]);
    hotelExpect($response['status'] === 422 && $count() === 1, 'el hotel también rechaza extensiones PHP en fotografías');
    $creation = $valid + ['foto' => new CURLFile($photo, 'image/jpeg', 'hotel.jpg'), 'verificado_en' => '2099-01-01', 'imagen' => 'images/no-autorizada.jpg'];
    $creation['destino_ids'][] = '203';
    $response = $admin->request('admin_hotel_guardar', $creation);
    hotelExpect($response['status'] === 303 && $count() === 2, 'guardar hotel crea la ficha y su fotografía');
    $statement = $connection->prepare('SELECT id FROM hoteles WHERE nombre = ?'); $statement->execute([$valid['nombre']]);
    $newId = (int) $statement->fetchColumn(); $hotel = $model->find($newId);
    hotelExpect($hotel['destino_ids'] === [201, 203], 'las asociaciones duplicadas se guardan una sola vez');
    hotelExpect($hotel['verificado_en'] === null && preg_match('/\Auploads\/[a-f0-9]{32}\.jpg\z/D', (string) $hotel['imagen']) === 1, 'fecha de verificación e imagen oculta no pueden falsificarse');
    $uploadFiles[] = PROJECT_ROOT . '/public/assets/' . $hotel['imagen'];
    $response = $visitor->request('hotel', null, ['id' => $newId]);
    hotelExpect($response['status'] === 200 && str_contains($response['body'], $hotel['imagen']) && str_contains($response['body'], $valid['foto_autor']), 'la ficha pública presenta la fotografía y sus créditos');
    $response = $visitor->request('hoteles', null, ['destino' => 201]);
    hotelExpect($response['status'] === 200 && str_contains($response['body'], $valid['nombre']), 'el hotel aparece en el catálogo asociado al destino');

    $response = $admin->request('admin_hotel_editar', null, ['id' => $markerId]);
    $edit = hotelForm($response); $edit['nombre'] = "Hotel O'Brien actualizado"; $edit['verificado_en'] = '2099-01-01'; $edit['imagen'] = 'images/falsificada.jpg';
    $response = $admin->request('admin_hotel_guardar', $edit);
    $edited = $model->find($markerId);
    hotelExpect($response['status'] === 303 && $edited['nombre'] === $edit['nombre'], 'el formulario plegable guarda la edición como datos');
    hotelExpect($edited['verificado_en'] === '2026-10-13' && $edited['imagen'] === $data['imagen'], 'editar conserva la fecha inicial y la fotografía existente');
    hotelExpect($edited['foto_autor_url'] === $data['foto_autor_url'] && $edited['foto_licencia_url'] === $data['foto_licencia_url'], 'editar conserva los enlaces de créditos');
    $invalidEdit = $edit; $invalidEdit['destino_ids'] = ['999999']; $invalidEdit['nombre'] = 'No debe guardarse';
    $response = $admin->request('admin_hotel_guardar', $invalidEdit);
    hotelExpect($response['status'] === 422 && $model->find($markerId)['nombre'] === $edit['nombre'] && $model->find($markerId)['destino_ids'] === [201], 'una asociación inválida conserva ficha y relaciones anteriores');
    $connection->exec('UPDATE lugares SET activo = 0 WHERE id = 203');
    $response = $admin->request('admin_hotel_editar', null, ['id' => $newId]);
    hotelExpect(str_contains($response['body'], 'Oculto en el catálogo público') && str_contains($response['body'], 'value="203" checked'), 'el editor conserva asociaciones con destinos ocultos y explica su estado');
    $hide = hotelForm($response); $hide['activo'] = '0'; $hide['destino_ids'] = []; $hide['latitud'] = ''; $hide['longitud'] = '';
    $response = $admin->request('admin_hotel_guardar', $hide); $hidden = $model->find($newId);
    hotelExpect($response['status'] === 303 && $hidden['destino_ids'] === [] && $hidden['latitud'] === null && $hidden['longitud'] === null, 'una edición permite retirar asociaciones y coordenadas');
    $response = $visitor->request('hotel', null, ['id' => $newId]);
    hotelExpect($response['status'] === 404 && (int) $hidden['activo'] === 0, 'desactivar oculta la ficha sin borrar el hotel');
    $response = $visitor->request('hoteles');
    hotelExpect(!str_contains($response['body'], $valid['nombre']), 'el hotel desactivado no aparece en el catálogo público');
    hotelExpect(count($model->all()) === 2 && Hotel::fromDatabase($connection)->find($newId) === null, 'administración conserva los hoteles ocultos y el modelo público los excluye');
} catch (Throwable $exception) { $failure = $exception->getMessage(); }
finally {
    if ($created && $connection instanceof PDO) {
        try {
            foreach ($connection->query('SELECT imagen FROM hoteles WHERE imagen LIKE \'uploads/%\'')->fetchAll(PDO::FETCH_COLUMN) as $path) {
                if (preg_match('/\Auploads\/[a-f0-9]{32}\.(jpg|png|webp)\z/D', (string) $path)) { $uploadFiles[] = PROJECT_ROOT . '/public/assets/' . $path; }
            }
        } catch (Throwable $ignored) {}
        $connection->exec('DROP DATABASE `' . $name . '`');
    }
    foreach (array_unique($uploadFiles) as $file) {
        if (realpath(dirname($file)) === realpath(PROJECT_ROOT . '/public/assets/uploads') && is_file($file)) { unlink($file); }
    }
    foreach ($temporaryFiles as $file) {
        if (is_string($file) && realpath(dirname($file)) === realpath(sys_get_temp_dir()) && is_file($file)) { unlink($file); }
    }
}
if ($failure !== null) { fwrite(STDERR, $failure . "\n"); exit(1); }
echo 'OK: ' . $hotelChecks . " comprobaciones aisladas del panel, asociaciones, fotos y catálogo de hoteles.\n";
