<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/Models/AdminLugar.php';
require dirname(__DIR__) . '/app/Core/PhotoUpload.php';

function expect(bool $condition, string $description): void
{
    if (!$condition) {
        throw new RuntimeException('Fallo: ' . $description);
    }
}

function expectInvalid(callable $callback, string $description): void
{
    try {
        $callback();
    } catch (InvalidArgumentException $exception) {
        return;
    }
    throw new RuntimeException('Fallo: ' . $description);
}

$connection = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$connection->exec('CREATE TABLE lugares (
    id INTEGER PRIMARY KEY AUTOINCREMENT, nombre TEXT NOT NULL, provincia TEXT NOT NULL,
    tipo TEXT NOT NULL, descripcion TEXT NOT NULL, ubicacion TEXT NOT NULL,
    latitud REAL, longitud REAL, activo INTEGER DEFAULT 1, imagen TEXT, imagen_alt TEXT,
    foto_autor TEXT, foto_autor_url TEXT, foto_fuente TEXT, foto_fuente_label TEXT,
    foto_licencia TEXT, foto_licencia_url TEXT, fuente_url TEXT,
    destacado TEXT, region TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP, updated_at TEXT
)');
$model = new AdminLugar($connection);
$payload = [
    'nombre' => "Destino O'Brien :nombre ; DROP TABLE lugares; --",
    'provincia' => 'Sánchez Ramírez', 'tipo' => 'Parque',
    'descripcion' => 'Descripción de prueba.', 'ubicacion' => 'Cotuí',
    'latitud' => 19.05833, 'longitud' => -70.15302, 'activo' => 1,
    'imagen' => 'images/cotui.jpg', 'imagen_alt' => 'Parque Duarte de Cotuí',
    'foto_autor' => 'Fotógrafo', 'foto_autor_url' => 'https://example.org/autor',
    'foto_fuente' => 'https://example.org/foto', 'foto_fuente_label' => 'Archivo de la imagen',
    'foto_licencia' => 'CC BY 4.0', 'foto_licencia_url' => 'https://example.org/licencia',
    'fuente_url' => 'https://example.org/destino',
    'destacado' => 'Un paseo por Cotuí', 'region' => 'Cibao',
    'id' => 9999, 'created_at' => '1900-01-01', 'untrusted_column' => 'ignorar',
];
$id = $model->save(null, $payload);
$place = $model->find($id);
expect($id === 1, 'las claves externas no deben elegir el ID');
expect($place !== null && $place['nombre'] === $payload['nombre'], 'valores con caracteres SQL se guardan como datos');
expect($place['created_at'] !== '1900-01-01', 'no se altera la fecha de creación');
expect($place['foto_autor'] === 'Fotógrafo' && $place['updated_at'] !== null, 'se guarda la metadata de la ficha');
$model->save($id, ['nombre' => 'Nombre actualizado', 'activo' => 0]);
$place = $model->find($id);
expect($place !== null && (int) $place['activo'] === 0, 'se desactiva sin borrar el destino');
expect($place['imagen'] === 'images/cotui.jpg' && $place['latitud'] == 19.05833, 'una actualización parcial conserva foto y coordenadas');
expect($place['foto_autor_url'] === $payload['foto_autor_url'] && $place['foto_licencia_url'] === $payload['foto_licencia_url'], 'cambiar el nombre conserva los enlaces de créditos');
expect(count($model->all()) === 1, 'el catálogo administrativo incluye destinos inactivos');
$model->save($id, ['imagen' => null, 'latitud' => null, 'longitud' => null]);
expect($model->find($id)['imagen'] === null && $model->find($id)['latitud'] === null, 'se puede retirar foto y coordenadas con NULL');
expect($model->find(999) === null, 'ID desconocido devuelve NULL');
expectInvalid(fn () => $model->save(999, ['activo' => 0]), 'editar un ID inexistente debe avisar');
expectInvalid(fn () => $model->save(0, ['activo' => 0]), 'el ID debe ser positivo');
expectInvalid(fn () => $model->save($id, ['id' => 2]), 'se rechaza guardar solo claves no permitidas');
expectInvalid(fn () => $model->save($id, ['nombre' => ['valor']]), 'se rechazan valores no escalares');

expect(PhotoUpload::store(['error' => UPLOAD_ERR_NO_FILE]) === null, 'sin selección de foto se conserva la anterior');
expectInvalid(fn () => PhotoUpload::store(['error' => [UPLOAD_ERR_OK]]), 'se rechazan cargas con estructura múltiple');
expectInvalid(fn () => PhotoUpload::store(['error' => UPLOAD_ERR_PARTIAL]), 'se rechazan cargas incompletas');
expectInvalid(fn () => PhotoUpload::store(['error' => UPLOAD_ERR_INI_SIZE]), 'se rechazan cargas superiores al límite del servidor');
$upload = ['error' => UPLOAD_ERR_OK, 'name' => 'foto.jpg', 'tmp_name' => __FILE__, 'size' => 42];
expectInvalid(fn () => PhotoUpload::store(array_replace($upload, ['name' => 'foto.php.jpg'])), 'se rechaza una doble extensión PHP');
expectInvalid(fn () => PhotoUpload::store(array_replace($upload, ['name' => 'foto.PHtMl.PNG'])), 'se rechaza una extensión ejecutable en mayúsculas');
expectInvalid(fn () => PhotoUpload::store(array_replace($upload, ['name' => 'foto.svg'])), 'se rechaza SVG y otros formatos');
expectInvalid(fn () => PhotoUpload::store(array_replace($upload, ['size' => 5 * 1024 * 1024 + 1])), 'se rechazan más de 5 MB');
expectInvalid(fn () => PhotoUpload::store(array_replace($upload, ['size' => 0])), 'se rechazan archivos vacíos');
expectInvalid(fn () => PhotoUpload::store($upload), 'no se pueden mover archivos locales como si fueran cargas HTTP');

echo "OK: creación, edición, desactivación, metadata y validación de cargas.\n";
