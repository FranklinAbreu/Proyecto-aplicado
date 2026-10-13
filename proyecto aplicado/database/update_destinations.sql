-- Actualización del catálogo inicial: no borra datos ni sustituye filas existentes.
-- Importar sobre la base turismo_rd creada con database/schema.sql.
USE turismo_rd;
SET NAMES utf8mb4;
START TRANSACTION;

-- Desactiva únicamente los ejemplos originales si ID y nombre exacto coinciden.
UPDATE lugares SET activo = 0
WHERE (id = 1 AND BINARY nombre = BINARY 'Playa de ejemplo')
   OR (id = 2 AND BINARY nombre = BINARY 'Río de ejemplo')
   OR (id = 3 AND BINARY nombre = BINARY 'Parque de ejemplo');

-- Los IDs 101–106 vinculan las fichas con sus fotos y fuentes.
-- INSERT IGNORE no sustituye filas del usuario cuando un ID ya existe.
INSERT IGNORE INTO lugares
    (id, nombre, provincia, tipo, descripcion, ubicacion, latitud, longitud, activo)
VALUES
    (101, 'Bahía de las Águilas', 'Pedernales', 'Playa',
     'En el Parque Nacional Jaragua, esta playa del suroeste combina arena clara, agua turquesa y formaciones de roca caliza. Su entorno protegido y abierto invita a contemplar el paisaje costero y descubrir una de las bahías más conocidas de Pedernales.',
     'Parque Nacional Jaragua, costa de Pedernales, al sur de Cabo Rojo.', 17.831639, -71.627748, 1),
    (102, 'Playa Rincón', 'Samaná', 'Playa',
     'Cerca de Las Galeras, una amplia franja de arena blanca bordea la bahía de Rincón entre cocoteros y montañas verdes. El paisaje reúne distintos ambientes de costa y pequeños establecimientos locales donde conocer los sabores de la península de Samaná.',
     'Bahía de Rincón, cerca de Las Galeras, península de Samaná.', 19.2894554, -69.2499448, 1),
    (103, 'Salto El Limón', 'Samaná', 'Río',
     'Esta cascada de la sierra de Samaná aparece entre vegetación tropical y senderos de montaña. El recorrido permite observar palmas y aves antes de llegar a la caída de agua y su poza, en el entorno del distrito municipal El Limón.',
     'Distrito municipal El Limón, municipio de Samaná, sierra de Samaná.', 19.2703106, -69.4462559, 1),
    (104, 'Salto de Jimenoa II', 'La Vega', 'Río',
     'En los alrededores de Jarabacoa, el río Jimenoa desciende entre paredes rocosas y abundante vegetación. Esta segunda cascada es un destino para contemplar el paisaje de montaña y recorrer su entorno natural, distinto del cercano Salto de Jimenoa I.',
     'Entorno de Jarabacoa, provincia La Vega; cascada Jimenoa II.', NULL, NULL, 1),
    (105, 'Parque Nacional Los Haitises', 'Hato Mayor', 'Parque',
     'Mogotes de roca caliza, manglares y cuevas con arte taíno forman el paisaje de este parque junto a la bahía de Samaná. Sus recorridos permiten apreciar cayos y aves; Sabana de la Mar es uno de sus puntos de acceso.',
     'Acceso por Sabana de la Mar, Hato Mayor. El parque también se extiende por Monte Plata y Samaná.', NULL, NULL, 1),
    (106, 'Altos de Chavón', 'La Romana', 'Cultural',
     'Sobre el río Chavón, este conjunto recrea un pueblo mediterráneo con calles de piedra, plazas y vistas al paisaje de La Romana. Su anfiteatro, la iglesia de San Estanislao y el museo arqueológico conectan arquitectura, arte e historia dominicana.',
     'Casa de Campo, La Romana, sobre el río Chavón.', 18.4206062, -68.8908629, 1);

COMMIT;

-- Destinos de Cotuí y Fantino verificados: database/sources.md.
-- Importar después de schema.sql sobre la base turismo_rd.
-- Los IDs 201–203 son estables; no se borran ni sustituyen filas existentes.
USE turismo_rd;
SET NAMES utf8mb4;
START TRANSACTION;

INSERT IGNORE INTO lugares
    (id, nombre, provincia, tipo, descripcion, ubicacion, latitud, longitud, activo)
VALUES
    (201, 'Parque Duarte de Cotuí', 'Sánchez Ramírez', 'Parque',
     'En el centro de Cotuí, el Parque Duarte reúne espacios de encuentro y un busto del patricio Juan Pablo Duarte. Su entorno urbano invita a caminar y conocer la vida cotidiana de la capital de Sánchez Ramírez.',
     'Calle Duarte, centro urbano de Cotuí, provincia Sánchez Ramírez.', 19.05833, -70.15302, 1),
    (202, 'Presa de Hatillo', 'Sánchez Ramírez', 'Río',
     'El embalse de Hatillo reúne paisajes de agua y montañas en Sánchez Ramírez. Su entorno forma parte del Parque Nacional Aniana Vargas; las visitas y actividades recreativas requieren respetar las condiciones locales de acceso y protección.',
     'Referencia: entorno del muro de la presa, Hatillo, Cotuí. Confirma el acceso local; no estacionar sobre el muro.', 19.03387, -70.20184, 1),
    (203, 'Parque Duarte de Fantino', 'Sánchez Ramírez', 'Parque',
     'El Parque Duarte de Fantino es un espacio de encuentro ligado a la vida cultural del municipio. Forma parte de recorridos turísticos organizados por el ayuntamiento y su entorno acoge el carnaval y otras celebraciones de la comunidad.',
     'Parque Central Juan Pablo Duarte, centro urbano de Fantino, provincia Sánchez Ramírez.', 19.12377, -70.30063, 1);

COMMIT;
