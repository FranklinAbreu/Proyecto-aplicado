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
