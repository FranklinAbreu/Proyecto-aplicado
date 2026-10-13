<?php

declare(strict_types=1);

// CLI only. Use a fresh test database and a separate PHP server configured with the same DB_NAME.
// Example env: TEST_DB_NAME=turismo_rd_test_<unique>, TEST_BASE_URL=http://127.0.0.1:8094.
// The real turismo_rd database is never selected. The fresh test database is dropped in finally.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

define('PROJECT_ROOT', dirname(__DIR__));
require PROJECT_ROOT . '/app/Core/helpers.php';
require PROJECT_ROOT . '/app/Core/AdminSchema.php';
require PROJECT_ROOT . '/app/Core/AdminAuth.php';
require PROJECT_ROOT . '/app/Models/AdminLugar.php';
require PROJECT_ROOT . '/app/Models/Lugar.php';
require PROJECT_ROOT . '/app/Controllers/AdminController.php';

function integrationExpect(bool $condition, string $description): void
{
    global $integrationChecks;
    if (!$condition) {
        throw new RuntimeException('Fallo: ' . $description);
    }
    ++$integrationChecks;
}

function integrationInvalid(callable $callback, string $description): void
{
    try {
        $callback();
    } catch (InvalidArgumentException $exception) {
        integrationExpect(true, $description);
        return;
    }
    throw new RuntimeException('Fallo: ' . $description);
}

final class IntegrationBrowser
{
    public function __construct(private string $base, private string $cookies) {}

    public function request(string $page, ?array $data = null, array $query = []): array
    {
        $handle = curl_init($this->base . '/index.php?' . http_build_query(['vista' => $page] + $query));
        curl_setopt_array($handle, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true,
            CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 15,
            CURLOPT_COOKIEFILE => $this->cookies, CURLOPT_COOKIEJAR => $this->cookies,
        ]);
        if ($data !== null) {
            $multipart = count(array_filter($data, static fn ($value): bool => $value instanceof CURLFile)) > 0;
            curl_setopt($handle, CURLOPT_POST, true);
            curl_setopt($handle, CURLOPT_POSTFIELDS, $multipart ? $data : http_build_query($data));
        }
        $response = curl_exec($handle);
        if ($response === false) {
            $message = curl_error($handle);
            curl_close($handle);
            throw new RuntimeException('No se pudo consultar el servidor aislado: ' . $message);
        }
        $headerLength = (int) curl_getinfo($handle, CURLINFO_HEADER_SIZE);
        $result = [
            'status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE),
            'headers' => substr($response, 0, $headerLength),
            'body' => substr($response, $headerLength),
        ];
        curl_close($handle);
        return $result;
    }

    public function sessionId(): string
    {
        foreach (file($this->cookies, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
            $columns = explode("\t", $line);
            if (count($columns) === 7 && $columns[5] === 'cercard_admin') {
                return $columns[6];
            }
        }
        return '';
    }
}

function integrationToken(array $response): string
{
    if (!preg_match('/name="csrf_token"\s+value="([^"]+)"/', $response['body'], $match)) {
        throw new RuntimeException('La página de prueba no contiene un formulario con token.');
    }
    return html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function integrationPayload(string $name, string $token): array
{
    return [
        'id' => '', 'csrf_token' => $token, 'nombre' => $name,
        'provincia' => 'Sánchez Ramírez', 'tipo' => 'Parque',
        'descripcion' => 'Descripción exclusiva de la prueba aislada.',
        'ubicacion' => 'Cotuí, Sánchez Ramírez', 'latitud' => '19.05833', 'longitud' => '-70.15302',
        'activo' => '1', 'region' => 'Cibao', 'destacado' => 'Destino de prueba',
        'fuente_url' => 'https://example.org/destino', 'imagen_alt' => 'Fotografía de prueba',
        'foto_autor' => 'Autor de prueba', 'foto_licencia' => 'Foto propia',
        'foto_fuente' => 'https://example.org/foto',
    ];
}

function integrationFormData(array $response): array
{
    $document = new DOMDocument();
    @$document->loadHTML('<?xml encoding="UTF-8">' . $response['body']);
    $xpath = new DOMXPath($document);
    $values = [];
    foreach ($xpath->query('//form//input[@name] | //form//textarea[@name] | //form//select[@name]') as $element) {
        $name = $element->getAttribute('name');
        $type = strtolower($element->getAttribute('type'));
        if (in_array($type, ['file', 'submit', 'button'], true)
            || (in_array($type, ['checkbox', 'radio'], true) && !$element->hasAttribute('checked'))) {
            continue;
        }
        if ($element->tagName === 'select') {
            $selected = $xpath->query('option[@selected]', $element)->item(0) ?? $xpath->query('option', $element)->item(0);
            $values[$name] = $selected?->getAttribute('value') ?? '';
        } else {
            $values[$name] = $element->tagName === 'textarea' ? $element->textContent : $element->getAttribute('value');
        }
    }
    return $values;
}

$integrationChecks = 0;
$databaseName = getenv('TEST_DB_NAME') ?: '';
if (!preg_match('/\Aturismo_rd_test_[a-z0-9_]{6,40}\z/D', $databaseName)) {
    fwrite(STDERR, "Define TEST_DB_NAME con un nombre nuevo que empiece por turismo_rd_test_.\n");
    exit(1);
}
$config = require PROJECT_ROOT . '/config/database.php';
if (!in_array($config['host'], ['127.0.0.1', 'localhost', '::1'], true)) {
    fwrite(STDERR, "Las pruebas solo pueden usar MySQL local.\n");
    exit(1);
}
$baseUrl = rtrim(getenv('TEST_BASE_URL') ?: '', '/');
if ($baseUrl !== '') {
    $parts = parse_url($baseUrl);
    if (!is_array($parts) || !in_array($parts['host'] ?? '', ['127.0.0.1', 'localhost', '[::1]'], true)
        || !in_array($parts['scheme'] ?? '', ['http', 'https'], true)
        || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])) {
        fwrite(STDERR, "TEST_BASE_URL debe apuntar al servidor local aislado.\n");
        exit(1);
    }
}

$server = null;
$databaseCreated = false;
$temporaryFiles = [];
$uploadedFiles = [];
$failure = null;
try {
    $server = new PDO(sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $config['host'], $config['port']),
        $config['user'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    // Intentionally no IF NOT EXISTS: an existing database cannot be overwritten or cleaned up.
    $server->exec('CREATE DATABASE `' . $databaseName . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $databaseCreated = true;
    $server->exec('USE `' . $databaseName . '`');
    $schema = str_replace('turismo_rd', $databaseName, file_get_contents(PROJECT_ROOT . '/database/schema.sql'));
    $server->exec($schema);
    $originalCount = (int) $server->query('SELECT COUNT(*) FROM lugares')->fetchColumn();
    integrationExpect(!AdminSchema::isReady($server), 'el catálogo anterior necesita las columnas administrativas');
    AdminSchema::ensure($server);
    AdminSchema::ensure($server);
    integrationExpect(AdminSchema::isReady($server), 'la migración es aditiva y puede repetirse');
    integrationExpect((int) $server->query('SELECT COUNT(*) FROM lugares')->fetchColumn() === $originalCount, 'migrar conserva los destinos existentes');
    integrationExpect((int) $server->query('SELECT COUNT(*) FROM administradores')->fetchColumn() === 0, 'no se crea una cuenta ni una contraseña por defecto');

    $_SERVER['REMOTE_ADDR'] = '203.0.113.10';
    $auth = new AdminAuth($server);
    integrationExpect(!$auth->canSetup(), 'la configuración inicial rechaza direcciones remotas');
    integrationInvalid(fn () => $auth->setup('cuenta_prueba', 'ClaveSoloDePrueba'), 'una dirección remota no puede crear el administrador');
    foreach (['127.0.0.1', '::1'] as $localAddress) {
        $_SERVER['REMOTE_ADDR'] = $localAddress;
        integrationExpect($auth->canSetup(), 'la configuración inicial acepta ' . $localAddress);
    }
    integrationExpect(!$auth->verifyToken([]) && !$auth->verifyToken('inventado'), 'CSRF rechaza tokens múltiples e inventados');
    $token = $auth->token();
    integrationExpect(strlen($token) === 64 && $auth->verifyToken($token), 'el token pertenece a la sesión');
    integrationInvalid(fn () => $auth->setup(str_repeat('a', 41), 'ClaveSoloDePrueba'), 'se rechazan usuarios demasiado largos');
    integrationInvalid(fn () => $auth->setup('cuenta_prueba', 'corta'), 'se rechazan contraseñas cortas');
    integrationInvalid(fn () => $auth->setup('cuenta_prueba', str_repeat('á', 37)), 'el límite de contraseña considera bytes antes de bcrypt');
    session_write_close();

    [$values, $errors] = AdminController::validatePlace(array_replace(integrationPayload('Coordenadas vacías', ''), ['latitud' => '', 'longitud' => '']));
    integrationExpect($errors === [] && $values['latitud'] === null && $values['longitud'] === null, 'dos coordenadas vacías se guardan como NULL');
    [$values, $errors] = AdminController::validatePlace(array_replace(integrationPayload('Coordenadas cero', ''), ['latitud' => '0', 'longitud' => '0']));
    integrationExpect($errors === [] && $values['latitud'] === 0.0 && $values['longitud'] === 0.0, 'cero es una coordenada válida y no equivale a vacío');

    if ($baseUrl !== '') {
        $marker = 'Fixture aislada ' . bin2hex(random_bytes(10));
        $adminModel = new AdminLugar($server);
        $markerId = $adminModel->save(null, array_diff_key(integrationPayload($marker, ''), array_flip(['id', 'csrf_token'])));
        $cookieVisitor = tempnam(sys_get_temp_dir(), 'cercard_test_cookie_');
        $cookieAdmin = tempnam(sys_get_temp_dir(), 'cercard_test_cookie_');
        $temporaryFiles = [$cookieVisitor, $cookieAdmin];
        $visitor = new IntegrationBrowser($baseUrl, $cookieVisitor);
        $browser = new IntegrationBrowser($baseUrl, $cookieAdmin);
        // Safety check before all POSTs: a mismatched server cannot receive test mutations.
        $fixturePage = $visitor->request('detalle', null, ['id' => $markerId]);
        integrationExpect($fixturePage['status'] === 200 && str_contains($fixturePage['body'], $marker), 'el servidor HTTP lee exactamente la base aislada');

        $countPlaces = static fn (): int => (int) $server->query('SELECT COUNT(*) FROM lugares')->fetchColumn();
        $countAdmins = static fn (): int => (int) $server->query('SELECT COUNT(*) FROM administradores')->fetchColumn();
        $count = $countPlaces();
        $response = $visitor->request('admin_guardar', integrationPayload('No autorizado', ''));
        integrationExpect(in_array($response['status'], [302, 303, 401, 403], true) && $countPlaces() === $count, 'un visitante no puede guardar destinos');
        $response = $visitor->request('admin_editar', null, ['id' => 201]);
        integrationExpect(in_array($response['status'], [302, 303, 401, 403], true), 'un visitante no puede abrir el editor');

        $response = $browser->request('admin_setup');
        $token = integrationToken($response);
        $setup = ['usuario' => 'integracion_admin', 'clave' => 'SoloPrueba_' . bin2hex(random_bytes(16))];
        $setup['confirmar_clave'] = $setup['clave'];
        $response = $browser->request('admin_setup', $setup);
        integrationExpect(in_array($response['status'], [400, 403], true) && $countAdmins() === 0, 'crear la cuenta necesita CSRF');
        $beforeSetupSession = $browser->sessionId();
        $response = $browser->request('admin_setup', $setup + ['csrf_token' => $token]);
        integrationExpect(in_array($response['status'], [302, 303], true) && $countAdmins() === 1, 'la cuenta inicial se crea desde loopback');
        integrationExpect($browser->sessionId() !== '' && $browser->sessionId() !== $beforeSetupSession, 'crear la cuenta cambia el identificador de sesión');
        $stored = $server->query('SELECT usuario, clave_hash FROM administradores WHERE id = 1')->fetch();
        integrationExpect($stored['clave_hash'] !== $setup['clave'] && password_verify($setup['clave'], $stored['clave_hash']), 'la base guarda el hash y verifica la contraseña elegida');

        $response = $browser->request('admin_nuevo');
        $token = integrationToken($response);
        $response = $browser->request('admin_setup', ['usuario' => 'otra_cuenta', 'clave' => 'OtraClavePrueba', 'confirmar_clave' => 'OtraClavePrueba', 'csrf_token' => $token]);
        integrationExpect($countAdmins() === 1 && $server->query('SELECT usuario FROM administradores WHERE id = 1')->fetchColumn() === $setup['usuario'], 'la configuración no sustituye ni añade otra cuenta');
        $response = $browser->request('admin_guardar', integrationPayload('Sin CSRF', 'invalido'));
        integrationExpect(in_array($response['status'], [400, 403], true) && $countPlaces() === $count, 'una sesión válida no puede guardar sin su token');
        $response = $browser->request('admin_guardar');
        integrationExpect($response['status'] === 405 && $countPlaces() === $count, 'guardar destinos no acepta GET');

        $valid = integrationPayload('Nuevo destino ' . bin2hex(random_bytes(8)), $token);
        foreach ([
            ['nombre' => str_repeat('a', 151)], ['provincia' => str_repeat('a', 101)],
            ['descripcion' => str_repeat('a', 5001)], ['ubicacion' => str_repeat('a', 256)],
            ['tipo' => 'Tipo inexistente'], ['latitud' => '', 'longitud' => '-70'],
            ['latitud' => '91'], ['longitud' => '-181'], ['latitud' => ['19']],
            ['fuente_url' => 'javascript:alert(1)'], ['foto_fuente' => 'https://usuario:clave@example.org/foto'],
        ] as $invalidFields) {
            $response = $browser->request('admin_guardar', array_replace($valid, $invalidFields));
            integrationExpect($response['status'] === 422 && $countPlaces() === $count, 'el formulario rechaza datos inválidos sin guardar: ' . implode(', ', array_keys($invalidFields)));
        }

        $photo = tempnam(sys_get_temp_dir(), 'cercard_test_photo_');
        $fake = tempnam(sys_get_temp_dir(), 'cercard_test_fake_');
        $large = tempnam(sys_get_temp_dir(), 'cercard_test_large_');
        array_push($temporaryFiles, $photo, $fake, $large);
        $image = imagecreatetruecolor(64, 48);
        imagefill($image, 0, 0, imagecolorallocate($image, 0, 45, 98));
        imagejpeg($image, $photo, 85);
        imagedestroy($image);
        file_put_contents($fake, '<?php echo "no ejecutar";');
        file_put_contents($large, str_repeat('A', 5 * 1024 * 1024 + 1));
        foreach ([
            new CURLFile($fake, 'image/jpeg', 'foto.jpg'),
            new CURLFile($photo, 'image/jpeg', 'foto.php.jpg'),
            new CURLFile($photo, 'image/png', 'foto.png'),
            new CURLFile($large, 'image/jpeg', 'foto.jpg'),
        ] as $invalidPhoto) {
            $response = $browser->request('admin_guardar', $valid + ['foto' => $invalidPhoto]);
            integrationExpect(in_array($response['status'], [413, 422], true) && $countPlaces() === $count, 'se rechaza foto falsa, PHP, extensión incompatible o más de 5 MB');
        }

        $response = $browser->request('admin_guardar', $valid + ['foto' => new CURLFile($photo, 'image/jpeg', 'mi-foto.jpg')]);
        integrationExpect(in_array($response['status'], [302, 303], true) && $countPlaces() === $count + 1, 'crear destino guarda su fotografía real');
        $statement = $server->prepare('SELECT * FROM lugares WHERE nombre = ?');
        $statement->execute([$valid['nombre']]);
        $newPlace = $statement->fetch();
        integrationExpect(is_array($newPlace) && preg_match('/\Auploads\/[a-f0-9]{32}\.jpg\z/D', (string) $newPlace['imagen']) === 1, 'la fotografía usa un nombre aleatorio de imagen');
        $uploadedFiles[] = PROJECT_ROOT . '/public/assets/' . $newPlace['imagen'];
        integrationExpect(is_file(end($uploadedFiles)), 'la fotografía guardada existe en disco');
        $response = $visitor->request('detalle', null, ['id' => $newPlace['id']]);
        integrationExpect($response['status'] === 200 && str_contains($response['body'], $newPlace['imagen']) && str_contains($response['body'], $valid['foto_autor']), 'la ficha pública muestra la fotografía y su autor');
        $response = $visitor->request('lugares');
        integrationExpect(str_contains($response['body'], $valid['nombre']) && str_contains($response['body'], $newPlace['imagen']), 'el catálogo público incorpora el nuevo destino y su foto');

        $response = $browser->request('admin_editar', null, ['id' => 201]);
        $seed = $adminModel->find(201);
        $seedBefore = destination_meta($seed);
        $seedData = integrationFormData($response);
        $seedData['nombre'] = 'Cotuí con nombre actualizado';
        $response = $browser->request('admin_guardar', $seedData);
        integrationExpect(in_array($response['status'], [302, 303], true), 'un destino inicial se puede renombrar sin subir otra foto');
        $response = $visitor->request('detalle', null, ['id' => 201]);
        integrationExpect(str_contains($response['body'], $seedBefore['image']) && str_contains($response['body'], $seedBefore['photo_author']) && str_contains($response['body'], e($seedBefore['photo_source'])), 'renombrar el destino inicial conserva foto y créditos en la ficha');
        integrationExpect(str_contains($response['body'], e($seedBefore['photo_author_url'])), 'renombrar conserva el enlace del autor');

        $response = $browser->request('admin_editar', null, ['id' => 101]);
        $licensedBefore = destination_meta($adminModel->find(101));
        $licensedData = integrationFormData($response);
        $licensedData['nombre'] = 'Bahía con nombre actualizado';
        $response = $browser->request('admin_guardar', $licensedData);
        $response = $visitor->request('detalle', null, ['id' => 101]);
        integrationExpect($response['status'] === 200 && str_contains($response['body'], $licensedBefore['image'])
            && str_contains($response['body'], e($licensedBefore['photo_license_url']))
            && str_contains($response['body'], $licensedBefore['photo_license']), 'editar un destino conserva la licencia y su enlace');

        $response = $browser->request('admin_editar', null, ['id' => $newPlace['id']]);
        $disable = array_replace($valid, ['id' => (string) $newPlace['id'], 'csrf_token' => integrationToken($response), 'activo' => '0']);
        $response = $browser->request('admin_guardar', $disable);
        integrationExpect(in_array($response['status'], [302, 303], true) && (int) $adminModel->find((int) $newPlace['id'])['activo'] === 0, 'se desactiva sin borrar el registro');
        $response = $visitor->request('detalle', null, ['id' => $newPlace['id']]);
        integrationExpect($response['status'] === 404, 'un destino desactivado no tiene ficha pública');
        $response = $visitor->request('lugares');
        integrationExpect(!str_contains($response['body'], $valid['nombre']), 'el destino desactivado desaparece del catálogo y los datos del mapa');

        $beforeLogoutSession = $browser->sessionId();
        $response = $browser->request('admin_logout');
        integrationExpect($response['status'] === 405, 'cerrar la sesión no acepta GET');
        $response = $browser->request('admin_logout', ['csrf_token' => 'invalido']);
        integrationExpect(in_array($response['status'], [400, 403], true), 'cerrar la sesión requiere CSRF');
        $response = $browser->request('admin');
        integrationExpect($response['status'] === 200, 'un token inválido no cierra la sesión');
        $logoutToken = integrationToken($response);
        $response = $browser->request('admin_logout', ['csrf_token' => $logoutToken]);
        integrationExpect(in_array($response['status'], [302, 303], true) && $browser->sessionId() !== $beforeLogoutSession, 'cerrar la sesión cambia su identificador');
        $response = $browser->request('admin_nuevo');
        integrationExpect(in_array($response['status'], [302, 303, 401, 403], true), 'la sesión cerrada pierde el acceso');
        $response = $browser->request('admin_login');
        $loginToken = integrationToken($response);
        $response = $browser->request('admin_login', ['usuario' => $setup['usuario'], 'clave' => $setup['clave']]);
        integrationExpect(in_array($response['status'], [400, 403], true), 'iniciar sesión necesita CSRF');
        $response = $browser->request('admin_login', ['usuario' => $setup['usuario'], 'clave' => 'IncorrectaSoloPrueba', 'csrf_token' => $loginToken]);
        integrationExpect(in_array($response['status'], [401, 422], true), 'una contraseña incorrecta no autentica');
        $beforeLoginSession = $browser->sessionId();
        $response = $browser->request('admin_login', ['usuario' => $setup['usuario'], 'clave' => $setup['clave'], 'csrf_token' => $loginToken]);
        integrationExpect(in_array($response['status'], [302, 303], true) && $browser->sessionId() !== $beforeLoginSession, 'iniciar sesión verifica la contraseña y cambia el identificador');
    }
} catch (Throwable $exception) {
    $failure = $exception->getMessage();
} finally {
    if ($databaseCreated && $server instanceof PDO && preg_match('/\Aturismo_rd_test_[a-z0-9_]{6,40}\z/D', $databaseName)) {
        // Also capture successfully saved uploads if a later assertion failed.
        try {
            foreach ($server->query('SELECT imagen FROM lugares WHERE imagen LIKE \'uploads/%\'')->fetchAll(PDO::FETCH_COLUMN) as $path) {
                if (preg_match('/\Auploads\/[a-f0-9]{32}\.(jpg|png|webp)\z/D', (string) $path)) {
                    $uploadedFiles[] = PROJECT_ROOT . '/public/assets/' . $path;
                }
            }
        } catch (Throwable $ignored) {}
        $server->exec('DROP DATABASE `' . $databaseName . '`');
    }
    foreach (array_unique($uploadedFiles) as $file) {
        if (realpath(dirname($file)) === realpath(PROJECT_ROOT . '/public/assets/uploads') && is_file($file)) {
            unlink($file);
        }
    }
    foreach ($temporaryFiles as $file) {
        // Windows tempnam() shortens prefixes to three letters; ownership is the recorded path.
        if (is_string($file) && realpath(dirname($file)) === realpath(sys_get_temp_dir()) && is_file($file)) {
            unlink($file);
        }
    }
}
if ($failure !== null) {
    fwrite(STDERR, $failure . "\n");
    exit(1);
}
echo 'OK: ' . $integrationChecks . ' comprobaciones aisladas' . ($baseUrl !== '' ? ' de integración HTTP, base, sesiones y fotos' : ' de esquema y autenticación') . ".\n";
