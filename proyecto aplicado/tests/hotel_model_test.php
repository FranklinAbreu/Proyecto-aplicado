<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/Models/Hotel.php';
require dirname(__DIR__) . '/app/Models/AdminHotel.php';
require dirname(__DIR__) . '/app/Core/HotelSchema.php';

function hotelExpect(bool $condition, string $description): void
{
    if (!$condition) { throw new RuntimeException('Fallo: ' . $description); }
}

function hotelInvalid(callable $callback, string $description): void
{
    try { $callback(); } catch (InvalidArgumentException $exception) { return; }
    throw new RuntimeException('Fallo: ' . $description);
}

$db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$db->exec('PRAGMA foreign_keys = ON');
hotelExpect(!HotelSchema::isReady($db), 'la revisión detecta tablas ausentes sin crearlas');
$db->exec('CREATE TABLE lugares (id INTEGER PRIMARY KEY, nombre TEXT, activo INTEGER)');
$db->exec("INSERT INTO lugares VALUES (101, 'Lugar activo', 1), (102, 'Otro lugar activo', 1), (103, 'Lugar inactivo', 0)");
$db->exec('CREATE TABLE hoteles (
    id INTEGER PRIMARY KEY AUTOINCREMENT, nombre TEXT NOT NULL, provincia TEXT NOT NULL,
    municipio TEXT NOT NULL, descripcion TEXT NOT NULL, ubicacion TEXT NOT NULL,
    latitud REAL, longitud REAL, telefono TEXT, sitio_web TEXT, reserva_url TEXT, servicios TEXT,
    imagen TEXT, imagen_alt TEXT, foto_autor TEXT, foto_autor_url TEXT, foto_fuente TEXT,
    foto_fuente_label TEXT, foto_licencia TEXT, foto_licencia_url TEXT, fuente_url TEXT,
    verificado_en TEXT, activo INTEGER NOT NULL DEFAULT 1,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP, updated_at TEXT
)');
$db->exec('CREATE TABLE hotel_lugar (
    hotel_id INTEGER NOT NULL REFERENCES hoteles(id) ON DELETE CASCADE,
    destino_id INTEGER NOT NULL REFERENCES lugares(id) ON DELETE CASCADE,
    PRIMARY KEY (hotel_id, destino_id)
)');
hotelExpect(HotelSchema::isReady($db), 'la revisión confirma las columnas necesarias');
$admin = new AdminHotel($db);
$payload = [
    'nombre' => "Hotel O'Brien ; DROP TABLE hoteles; --", 'provincia' => 'Sánchez Ramírez',
    'municipio' => 'Cotuí', 'descripcion' => 'Habitaciones para visitar la región.', 'ubicacion' => 'Centro urbano.',
    'latitud' => 0, 'longitud' => 1, 'telefono' => '809-000-0000', 'sitio_web' => 'https://example.org',
    'reserva_url' => 'https://example.org/reservar', 'servicios' => 'Wi-Fi, estacionamiento',
    'imagen' => 'images/hotel.jpg', 'imagen_alt' => 'Fachada del hotel', 'foto_autor' => 'Autor de prueba',
    'foto_autor_url' => 'https://example.org/autor', 'foto_fuente' => 'https://example.org/foto',
    'foto_fuente_label' => 'Fuente', 'foto_licencia' => 'Foto propia', 'foto_licencia_url' => '',
    'fuente_url' => 'https://example.org/info', 'verificado_en' => '2026-10-13', 'activo' => 1,
    'id' => 99999, 'created_at' => '1900-01-01', 'destino_ids' => [999],
];
$first = $admin->save(null, $payload, [101, '101', 103]);
hotelExpect($first === 1 && $admin->find($first)['nombre'] === $payload['nombre'], 'ID protegido y SQL guardado como texto');
hotelExpect($admin->find($first)['destino_ids'] === [101, 103], 'asociaciones únicas, inclusive destinos inactivos');
hotelExpect($admin->find($first)['created_at'] !== '1900-01-01', 'la fecha original no se recibe de entrada');
$second = $admin->save(null, array_replace($payload, ['nombre' => 'Hotel cercano', 'longitud' => 0.1]), [101]);
$third = $admin->save(null, array_replace($payload, ['nombre' => 'Hotel sin coordenadas', 'latitud' => null, 'longitud' => null]), [101]);
$hidden = $admin->save(null, array_replace($payload, ['nombre' => 'Hotel oculto', 'activo' => 0]), [101]);
$unlinked = $admin->save(null, array_replace($payload, ['nombre' => 'Hotel sin asociación', 'longitud' => 0]), []);
$public = Hotel::fromDatabase($db);
hotelExpect(count($public->all()) === 4 && $public->find($hidden) === null, 'el catálogo público excluye hoteles inactivos');
hotelExpect($public->find($first)['destino_ids'] === [101], 'el público excluye asociaciones a destinos inactivos');
hotelExpect(count($admin->all()) === 5 && $admin->find($hidden) !== null, 'administración conserva hoteles ocultos');
hotelExpect(count($public->search('cotui', 'Sánchez Ramírez', 'Cotuí')) === 4, 'búsqueda sin tilde y filtros combinados');
hotelExpect($public->search('no existe', '', '') === [], 'búsqueda sin coincidencias');
hotelExpect($public->options('municipio') === ['Cotuí'], 'opciones sin duplicados');
$nearby = $public->forPlace(['id' => 101, 'latitud' => 0, 'longitud' => 0], 10);
hotelExpect(array_column($nearby, 'id') === [$second, $first, $third], 'solo asociaciones explícitas, distancia conocida antes de desconocida');
hotelExpect(abs($nearby[1]['distance_km'] - 111.1949266) < 0.001, 'Haversine usa kilómetros en línea recta');
hotelExpect($nearby[2]['distance_km'] === null, 'no se inventa distancia si faltan coordenadas');
hotelExpect(count($public->forPlace(['id' => 101, 'latitud' => 0, 'longitud' => 0], 1)) === 1, 'se respeta el límite');
hotelExpect($public->forPlace(['id' => 101], 0) === [], 'límite cero devuelve cero hoteles');
hotelExpect($public->forPlace(['id' => 101, 'activo' => 0]) === [], 'no se muestran hoteles para destino inactivo');
$invalidCoordinates = $public->forPlace(['id' => 101, 'latitud' => 91, 'longitud' => 0], 10);
hotelExpect(count(array_filter($invalidCoordinates, static fn (array $hotel): bool => $hotel['distance_km'] !== null)) === 0, 'coordenadas inválidas no generan distancias');
$admin->save($first, ['nombre' => 'Hotel editado', 'activo' => 0], [102]);
$edited = $admin->find($first);
hotelExpect($edited['imagen'] === $payload['imagen'] && $edited['foto_autor_url'] === $payload['foto_autor_url'], 'editar conserva la foto y créditos omitidos');
hotelExpect($edited['destino_ids'] === [102] && (int) $edited['activo'] === 0, 'se sustituyen asociaciones y se desactiva sin borrar');
$admin->save($first, ['activo' => 1], []);
hotelExpect($admin->find($first)['destino_ids'] === [], 'una lista vacía retira asociaciones');
hotelInvalid(fn () => $admin->save($first, ['nombre' => 'No guardar'], [999]), 'se rechaza un destino inexistente');
hotelExpect($admin->find($first)['nombre'] === 'Hotel editado', 'un vínculo inexistente no modifica el hotel');
hotelInvalid(fn () => $admin->save(null, $payload, ['1 OR 1=1']), 'se rechazan IDs con SQL');
hotelInvalid(fn () => $admin->save(null, $payload, [4294967296]), 'se rechazan IDs fuera de rango');
hotelInvalid(fn () => $admin->save(999, ['activo' => 1], []), 'se rechaza hotel inexistente');
hotelInvalid(fn () => $admin->save($first, ['nombre' => []], []), 'se rechazan valores no escalares');
hotelExpect(count($admin->all()) === 5, 'los intentos fallidos no añaden hoteles');

// Un fallo después de guardar la ficha también revierte sus vínculos anteriores.
$admin->save($first, ['activo' => 1], [101]);
$db->exec("CREATE TRIGGER reject_second_link BEFORE INSERT ON hotel_lugar WHEN NEW.destino_id = 102 BEGIN SELECT RAISE(ABORT, 'link failure'); END");
try { $admin->save($first, ['nombre' => 'No debe persistir'], [102]); } catch (PDOException $exception) { }
hotelExpect($admin->find($first)['nombre'] === 'Hotel editado' && $admin->find($first)['destino_ids'] === [101], 'fallo al enlazar revierte ficha y vínculos');
$db->exec('DROP TRIGGER reject_second_link');
$db->beginTransaction();
$admin->save($first, ['nombre' => 'Dentro de otra transacción'], [102]);
hotelExpect($db->inTransaction(), 'no se confirma una transacción iniciada por el llamador');
$db->rollBack();
hotelExpect($admin->find($first)['nombre'] === 'Hotel editado' && $admin->find($first)['destino_ids'] === [101], 'la transacción externa puede revertir la edición');
$db->beginTransaction();
hotelInvalid(fn () => $admin->save($first, ['nombre' => 'No guardar'], [999]), 'error dentro de una transacción externa');
hotelExpect($db->inTransaction(), 'el error revierte solo el savepoint de la operación');
$db->rollBack();

$fallback = new Hotel(array_merge($public->all(), [$admin->find($hidden)]), true);
hotelExpect($fallback->isDemo() && $fallback->find($hidden) === null, 'el catálogo alternativo también excluye hoteles inactivos');
echo "OK: hoteles públicos, búsquedas, asociaciones, distancias y guardado atómico.\n";
