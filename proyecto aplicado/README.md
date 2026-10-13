# CercaRD - descubre República Dominicana

Proyecto académico para consultar lugares turísticos de República Dominicana, preparado en **PHP 8.2 y MySQL/MariaDB de XAMPP**. XAMPP incluye el servidor y la base de datos; la base de este proyecto se llama `turismo_rd`.

## Cómo abrirlo

1. Abre XAMPP Control Panel y enciende **Apache**.
2. Entra en [la página del proyecto](http://localhost/proyecto%20aplicado/).
3. Puedes consultar nueve destinos reales, todos con fotografías, buscar por nombre, filtrar por provincia o categoría y abrir sus fichas.

## Cómo conectar la base de datos

1. Enciende **MySQL** en XAMPP.
2. Abre [phpMyAdmin](http://localhost/phpmyadmin/).
3. En **Importar**, selecciona `database/schema.sql` y ejecuta la importación. El archivo crea `turismo_rd`, su tabla `lugares` y los nueve destinos.
4. Recarga la página. Cuando la conexión y la tabla estén disponibles, el catálogo leerá MySQL automáticamente.

Si ya importaste la primera versión con lugares ficticios, importa `database/update_destinations.sql`. Esa actualización desactiva únicamente los tres ejemplos originales cuando coinciden su ID y su nombre. Añade los destinos reales sin sobrescribir otros registros.

Para añadir Cotuí y Fantino a una instalación anterior, importa `database/local_destinations.sql`. La instalación local de este proyecto ya contiene los nueve destinos.

La conexión está en `config/database.php`: servidor `127.0.0.1`, puerto `3306`, usuario `root` y contraseña vacía. Si tu instalación tiene otros valores, crea `config/database.local.php`:

```php
<?php
return [
    'host' => '127.0.0.1',
    'port' => '3306',
    'name' => 'turismo_rd',
    'user' => 'root',
    'password' => 'tu_clave_local',
];
```

Ese archivo está excluido de Git. También se admiten las variables `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` y `DB_PASSWORD`.

## Organización

```text
proyecto aplicado/
├── index.php                  Entrada para XAMPP
├── app/
│   ├── bootstrap.php          Carga la aplicación
│   ├── Controllers/           Decide qué página mostrar
│   ├── Core/                  Conexión y funciones compartidas
│   ├── Models/                Consulta y filtra los lugares
│   └── Views/                 Inicio, catálogo, fichas y diseño común
├── config/                    Configuración de MySQL y datos de las fotografías
├── database/
│   ├── schema.sql             Creación de base, tabla y destinos reales
│   ├── update_destinations.sql Actualización de la primera versión
│   ├── sources.md             Fuentes y puntos de referencia de los destinos
│   └── demo.php               Catálogo inicial disponible sin MySQL
└── public/
    ├── index.php              Entrada pública
    └── assets/                Estilos y fotografías locales
```

Se utiliza una estructura sencilla de modelo, vista y controlador, sin framework. Leaflet 1.9.4 está incluido localmente para el mapa, con su licencia. Apache bloquea el acceso web a las carpetas internas mediante `.htaccess`.

## Qué funciona en esta etapa

- Portada fotográfica, catálogo y fichas con diseño adaptable.
- Búsqueda por texto y filtros por provincia y tipo.
- Ficha individual y página 404 para rutas o identificadores inválidos.
- Lectura de MySQL mediante PDO, con catálogo local visible si MySQL o la tabla no están disponibles.
- Campos de nombre, provincia, tipo, descripción, ubicación y coordenadas en la base.
- Diseño en azul, rojo y blanco inspirado en la bandera dominicana.
- Mapa de destinos y hasta tres rutas cercanas por carretera, con distancia y tiempo aproximado.
- Puntos de partida de Cotuí/Fantino y ubicación del dispositivo únicamente al pulsar el botón correspondiente.

El catálogo incluye Bahía de las Águilas, Playa Rincón, Salto El Limón, Salto de Jimenoa II, Parque Nacional Los Haitises, Altos de Chavón, Parque Duarte de Cotuí, Presa de Hatillo y Parque Duarte de Fantino. Todos tienen fotografías guardadas en el proyecto. Las fichas enlazan sus fuentes y créditos. Las imágenes municipales de Cotuí y Fantino documentan actividades en esos parques; sus publicaciones no indican una licencia Creative Commons.

Las coordenadas disponibles son puntos de referencia aproximados publicados por las fuentes, no indicaciones exactas de entrada a senderos. Los destinos sin un punto verificable conservan coordenadas vacías. Las fuentes y el criterio de ubicación están documentados en `database/sources.md`; los créditos de cada foto están en `config/destinations.php` y `config/local_destinations.php`.

## Administrar destinos

### Próxima etapa: hoteles y alojamientos

El proyecto incluirá hoteles para que los visitantes puedan alojarse durante su visita. Este requisito queda registrado para el siguiente módulo; todavía no está implementado.

- Una sección **Hoteles** en la página pública y opciones de alojamiento relacionadas con cada destino, incluyendo los de Cotuí y Fantino cuando haya establecimientos verificados.
- Fichas con nombre del hotel, fotos reales, descripción, ubicación, coordenadas y contacto o enlace oficial para consultar una reserva.
- Una sección **Hoteles** en el panel para agregar, editar, publicar u ocultar alojamientos y vincularlos con uno o varios destinos.
- Información y fotografías verificadas antes de publicar. Mostrar distancia solo cuando pueda calcularse y aclarar si es por carretera o en línea recta.

Las reservas internas, pagos y disponibilidad en tiempo real quedan pendientes de definir. Un enlace al establecimiento permite consultar alojamiento sin afirmar que existe una reserva confirmada.

### Panel disponible

El enlace **Administrar destinos** del pie de página abre el panel. En la primera visita desde `localhost`, crea tu propio usuario y contraseña. No existe una contraseña predeterminada. La configuración inicial se cierra después de crear la cuenta; los siguientes accesos requieren esas credenciales.

El panel permite agregar y editar nombre, provincia, tipo, descripción, ubicación, coordenadas, fotografía y créditos. La casilla **Visible en la página** publica u oculta el destino sin borrarlo. Los cambios aparecen en el catálogo, la ficha y el mapa al volver a abrirlos. Para aparecer en el mapa, el destino necesita ambas coordenadas.

Las fotografías admitidas son JPG, PNG y WebP, hasta 5 MB, 8000 píxeles por lado y 20 megapíxeles. La foto anterior se conserva si no seleccionas otra; revisa los créditos al reemplazar una imagen. Las fotografías del catálogo inicial también se conservan al editar o cambiar el nombre de un destino.

En una instalación nueva, después de importar `database/schema.sql`, importa `database/admin.sql` desde phpMyAdmin. También puedes preparar el panel con:

```powershell
& C:\xampp\php\php.exe database/install_admin.php
```

El instalador agrega los campos necesarios sin borrar el catálogo ni crear cuentas. Las contraseñas se guardan como hashes; las acciones requieren sesión y comprobación del formulario. La sesión vence después de dos horas sin actividad. El panel requiere MySQL y no escribe en el catálogo de demostración.

## Cómo funciona el mapa

En la sección Rutas pulsa **Desde Cotuí**, **Desde Fantino** o **Usar mi ubicación**. La primera alternativa utiliza el parque municipal de cada ciudad como punto público de referencia. El botón de ubicación solicita permiso al navegador; no se obtiene la ubicación al abrir la página.

Las teselas son de OpenStreetMap y los trayectos en vehículo se consultan mediante el servidor público de OSRM. Ambos requieren internet. El punto de partida se envía a OSRM para calcular el recorrido. OSRM propone rutas rápidas; CercaRD ordena los destinos por la distancia de esos trayectos, sin afirmar que son el camino matemáticamente más corto ni incorporar tráfico en vivo.

Se omiten destinos sin coordenadas o con un ajuste a la carretera superior a un kilómetro. Si OSRM no responde, la página indica que sólo puede mostrar cercanía en línea recta y no inventa trazados. Las rutas son referencias del acceso vial; la ficha de Hatillo recuerda que no se debe estacionar sobre el muro.

El servicio de rutas está en `app/Core/RouteService.php`, su API en `public/api/routes.php` y el mapa en `public/assets/js/map.js`.

Para una vista alternativa con el servidor de PHP, sirve únicamente la carpeta pública:

```powershell
& C:\xampp\php\php.exe -S 127.0.0.1:8080 -t public
```

Luego entra en `http://127.0.0.1:8080/`. En XAMPP usa el enlace de la carpeta del proyecto indicado arriba.
