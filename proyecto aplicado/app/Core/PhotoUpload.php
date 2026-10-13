<?php

declare(strict_types=1);

final class PhotoUpload
{
    private const MAX_BYTES = 5 * 1024 * 1024;
    private const MAX_DIMENSION = 8000;
    private const MAX_PIXELS = 20000000;
    private const MIME_EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public static function store(array $file): ?string
    {
        $error = $file['error'] ?? null;
        if ($error === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if (!is_int($error)) {
            throw new InvalidArgumentException('Selecciona una fotografía válida.');
        }
        if ($error !== UPLOAD_ERR_OK) {
            if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
                throw new InvalidArgumentException('La fotografía debe pesar como máximo 5 MB.');
            }
            if ($error === UPLOAD_ERR_PARTIAL) {
                throw new InvalidArgumentException('La fotografía no se subió completa. Vuelve a seleccionarla.');
            }
            if (in_array($error, [UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE, UPLOAD_ERR_EXTENSION], true)) {
                throw new RuntimeException('No se pudo recibir la fotografía. Inténtalo de nuevo.');
            }
            throw new InvalidArgumentException('No se pudo subir la fotografía. Vuelve a seleccionarla.');
        }

        $name = $file['name'] ?? null;
        $temporaryPath = $file['tmp_name'] ?? null;
        $size = $file['size'] ?? null;
        if (!is_string($name) || !is_string($temporaryPath) || !is_int($size)
            || $name === '' || str_contains($name, "\0")) {
            throw new InvalidArgumentException('Selecciona una fotografía válida.');
        }

        // El nombre original nunca se usa en disco; rechazamos también extensiones ejecutables intermedias.
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)
            || preg_match('/(?:^|\.)(?:php\d*|phtml|pht|phar|cgi|pl|py|rb|sh|asp|aspx)(?:\.|$)/i', $name)) {
            throw new InvalidArgumentException('Solo se permiten fotografías JPG, PNG o WebP.');
        }
        if ($size < 1 || $size > self::MAX_BYTES) {
            throw new InvalidArgumentException('La fotografía debe pesar entre 1 byte y 5 MB.');
        }
        if (!is_uploaded_file($temporaryPath)) {
            throw new InvalidArgumentException('La fotografía no llegó correctamente. Vuelve a seleccionarla.');
        }
        $actualSize = filesize($temporaryPath);
        if ($actualSize === false || $actualSize < 1 || $actualSize > self::MAX_BYTES || $actualSize !== $size) {
            throw new InvalidArgumentException('La fotografía debe pesar como máximo 5 MB y subirse completa.');
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($temporaryPath);
        $image = @getimagesize($temporaryPath);
        if ($image === false || !is_string($mime) || !isset(self::MIME_EXTENSIONS[$mime])
            || ($image['mime'] ?? null) !== $mime) {
            throw new InvalidArgumentException('El archivo debe contener una fotografía JPG, PNG o WebP real.');
        }
        $expectedExtension = self::MIME_EXTENSIONS[$mime];
        if (($extension === 'jpeg' ? 'jpg' : $extension) !== $expectedExtension) {
            throw new InvalidArgumentException('La extensión de la fotografía no coincide con su formato.');
        }
        $width = (int) $image[0];
        $height = (int) $image[1];
        if ($width < 1 || $height < 1 || $width > self::MAX_DIMENSION || $height > self::MAX_DIMENSION
            || $width * $height > self::MAX_PIXELS) {
            throw new InvalidArgumentException('La fotografía debe tener como máximo 8000 píxeles por lado y 20 megapíxeles.');
        }

        $directory = dirname(__DIR__, 2) . '/public/assets/uploads';
        self::prepareDirectory($directory);
        $filename = bin2hex(random_bytes(16)) . '.' . $expectedExtension;
        if (!move_uploaded_file($temporaryPath, $directory . '/' . $filename)) {
            throw new RuntimeException('No se pudo guardar la fotografía. Inténtalo de nuevo.');
        }

        // Conservar las fotos anteriores evita borrar imágenes compartidas por otras fichas.
        return 'uploads/' . $filename;
    }

    private static function prepareDirectory(string $directory): void
    {
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException('No se pudo preparar la carpeta de fotografías.');
        }

        $rules = <<<'HTACCESS'
Options -Indexes -ExecCGI
SetHandler none
RemoveHandler .php .php3 .php4 .php5 .php7 .php8 .phtml .pht .phar .cgi .pl .py .rb .sh .asp .aspx
RemoveType .php .php3 .php4 .php5 .php7 .php8 .phtml .pht .phar
<IfModule php_module>
    php_flag engine off
</IfModule>
<FilesMatch "^(?![a-f0-9]{32}\.(?:jpg|png|webp)$)">
    Require all denied
</FilesMatch>
HTACCESS;
        $rules .= "\n";
        $policyPath = $directory . '/.htaccess';
        if (!is_file($policyPath) || file_get_contents($policyPath) !== $rules) {
            if (file_put_contents($policyPath, $rules, LOCK_EX) !== strlen($rules)) {
                throw new RuntimeException('No se pudo proteger la carpeta de fotografías.');
            }
        }
    }
}
