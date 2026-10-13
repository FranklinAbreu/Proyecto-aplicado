<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Descubre playas, ríos, naturaleza y cultura en destinos reales de República Dominicana.">
    <meta name="theme-color" content="#002d62">
    <title><?= e($title) ?> | <?= e($app['nombre']) ?></title>
    <?php if (isset($mapPlaces)): ?><link rel="stylesheet" href="<?= e(asset_url('vendor/leaflet/leaflet.css')) ?>"><?php endif; ?>
    <link rel="stylesheet" href="<?= e(asset_url('css/style.css') . '?v=' . $app['version']) ?>">
</head>
<body>
<a class="skip-link" href="#contenido">Saltar al contenido</a>
<svg class="icon-symbols" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
    <symbol id="icon-compass" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m16 8-2.5 5.5L8 16l2.5-5.5L16 8Z"/></symbol>
    <symbol id="icon-arrow" viewBox="0 0 24 24"><path d="M5 12h14m-6-6 6 6-6 6"/></symbol>
    <symbol id="icon-arrow-up" viewBox="0 0 24 24"><path d="M6 18 18 6M6 6h12v12"/></symbol>
    <symbol id="icon-search" viewBox="0 0 24 24"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 4.5 4.5"/></symbol>
    <symbol id="icon-pin" viewBox="0 0 24 24"><path d="M19 10c0 5-7 11-7 11S5 15 5 10a7 7 0 1 1 14 0Z"/><circle cx="12" cy="10" r="2.5"/></symbol>
    <symbol id="icon-sun" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M2 12h2m16 0h2M5 5l1.5 1.5m11 11L19 19M5 19l1.5-1.5m11-11L19 5"/></symbol>
    <symbol id="icon-waves" viewBox="0 0 24 24"><path d="M2 7c3-4 5 4 8 0s5 4 8 0 3 0 4 0M2 12c3-4 5 4 8 0s5 4 8 0 3 0 4 0M2 17c3-4 5 4 8 0s5 4 8 0 3 0 4 0"/></symbol>
    <symbol id="icon-leaf" viewBox="0 0 24 24"><path d="M20 3C8 2 2 8 5 15s15 7 15-12ZM4 21 15 10M9 16h6M9 16v-6"/></symbol>
    <symbol id="icon-culture" viewBox="0 0 24 24"><path d="m3 8 9-5 9 5H3Zm0 13h18M5 11v7m7-7v7m7-7v7M3 18h18"/></symbol>
</svg>
<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="<?= e(url()) ?>" aria-label="<?= e($app['nombre']) ?>, inicio"><span class="brand-mark" aria-hidden="true"><svg class="icon" viewBox="0 0 24 24"><use href="#icon-compass"/></svg></span><span class="brand-name"><?= e($app['nombre']) ?><span class="brand-dot">.</span></span></a>
        <nav class="main-nav" aria-label="Navegación principal">
            <a class="<?= $currentPage === 'inicio' ? 'is-active' : '' ?>" href="<?= e(url()) ?>" <?= $currentPage === 'inicio' ? 'aria-current="page"' : '' ?>>Inicio</a>
            <a class="<?= in_array($currentPage, ['lugares', 'detalle'], true) ? 'is-active' : '' ?>" href="<?= e(url(['vista' => 'lugares'])) ?>" <?= $currentPage === 'lugares' ? 'aria-current="page"' : '' ?>>Explorar</a>
            <a href="<?= e(url()) ?>#mapa">Rutas</a>
            <a class="island-link" href="<?= e(url()) ?>#descubre">Nuestra isla</a>
        </nav>
        <a class="header-cta" href="<?= e(url()) ?>#mapa">Encuentra tu ruta <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#icon-arrow-up"/></svg></a>
    </div>
</header>
<main id="contenido">
