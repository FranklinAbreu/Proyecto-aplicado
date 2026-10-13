# Base de datos de CercaRD

1. Inicia **Apache** y **MySQL** en el panel de XAMPP.
2. Abre [phpMyAdmin](http://localhost/phpmyadmin/).
3. En **Importar**, selecciona `database/schema.sql` y ejecuta la importación.
4. Importa `database/admin.sql` para habilitar el panel de administración.

Después entra en `http://localhost/proyecto%20aplicado/index.php?vista=admin` y crea tu cuenta inicial. No se incluye un usuario ni una contraseña predeterminados. La migración puede repetirse y conserva los destinos existentes. Como alternativa a `admin.sql`, ejecuta `C:\xampp\php\php.exe database/install_admin.php` desde la carpeta del proyecto.

El archivo crea la base `turismo_rd`, la tabla `lugares` y nueve destinos reales. Es compatible con MariaDB 10.4 de XAMPP y utiliza UTF-8 para nombres como Samaná y Chavón. Puede volver a importarse sin borrar tablas ni sustituir filas que ya existan.

Si ya importaste la primera versión, también puedes seleccionar la base `turismo_rd` e importar `database/update_destinations.sql`. Esa actualización agrega los nueve destinos y desactiva exclusivamente las filas originales 1, 2 y 3 cuando sus nombres siguen siendo exactamente `Playa de ejemplo`, `Río de ejemplo` y `Parque de ejemplo`. Los registros modificados por ti se conservan.

Los destinos utilizan los IDs 101 a 106 y 201 a 203. Si uno de esos IDs ya pertenece a un registro tuyo, el archivo conserva ese registro y omite el destino correspondiente; revisa esa colisión antes de asignar otro ID. Las fotografías y fuentes del sitio se asocian al ID y al nombre del destino.

`demo.php` devuelve los mismos nueve destinos como un arreglo de PHP para explorar el sitio cuando MySQL no está disponible. No importa datos ni modifica la base. Las categorías del catálogo inicial son Playa, Río, Parque y Cultural; el filtro también admite Montaña.

`local_destinations.sql` incorpora únicamente Parque Duarte de Cotuí, Presa de Hatillo y Parque Duarte de Fantino a una instalación anterior. El mapa compara rutas por carretera hacia los lugares con coordenadas disponibles.

La información y los puntos de ubicación publicados están documentados en [sources.md](sources.md). Los Haitises se agrupa bajo Hato Mayor por el acceso de Sabana de la Mar, aunque el parque también abarca otras provincias. Sus coordenadas y las de Jimenoa II permanecen en `NULL` para evitar presentar una entrada o una cascada equivocada. Los otros cuatro puntos son referencias del sitio oficial de Turismo y no sustituyen la verificación del acceso.
