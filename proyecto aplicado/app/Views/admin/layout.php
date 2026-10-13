<?php
$adminAuthenticated = !empty($currentUser);
$adminTemplates = ['setup' => 'setup', 'login' => 'login', 'index' => 'index', 'form' => 'form', 'error' => 'error', 'hotel_index' => 'hotels/index', 'hotel_form' => 'hotels/form'];
$adminView = array_key_exists($view, $adminTemplates) ? $view : 'error';
$adminHotelView = in_array($adminView, ['hotel_index', 'hotel_form'], true);
$adminIsNew = in_array($adminView, ['form', 'hotel_form'], true) && empty($values['id']);
$adminSection = $adminHotelView ? ($adminView === 'hotel_index' ? 'Hoteles' : ($adminIsNew ? 'Agregar hotel' : 'Editar hotel')) : ($adminView === 'index' ? 'Destinos' : ($adminView === 'form' ? ($adminIsNew ? 'Agregar destino' : 'Editar destino') : $title));
$adminIcon = static fn (string $name): string => '<svg class="admin-icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#admin-icon-' . $name . '"/></svg>';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#002d62">
    <title><?= e($title) ?> | <?= e($app['nombre']) ?></title>
    <link rel="stylesheet" href="<?= e(asset_url('css/admin.css') . '?v=' . $app['version']) ?>">
    <?php if (in_array($adminView, ['form', 'hotel_form'], true)): ?>
        <script src="<?= e(asset_url('js/admin.js') . '?v=' . $app['version']) ?>" defer></script>
    <?php endif; ?>
</head>
<body class="admin-body <?= $adminAuthenticated ? 'admin-authenticated' : 'admin-guest' ?>">
<a class="admin-skip" href="#contenido">Saltar al contenido</a>
<svg class="admin-symbols" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
    <symbol id="admin-icon-compass" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m16 8-2.5 5.5L8 16l2.5-5.5L16 8Z"/></symbol>
    <symbol id="admin-icon-grid" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></symbol>
    <symbol id="admin-icon-plus" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></symbol>
    <symbol id="admin-icon-external" viewBox="0 0 24 24"><path d="M14 3h7v7m0-7L10 14M10 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-5"/></symbol>
    <symbol id="admin-icon-pin" viewBox="0 0 24 24"><path d="M19 10c0 5-7 11-7 11S5 15 5 10a7 7 0 1 1 14 0Z"/><circle cx="12" cy="10" r="2.5"/></symbol>
    <symbol id="admin-icon-photo" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8" cy="8" r="1.5"/><path d="m3 17 5-5 4 4 4-7 5 8"/></symbol>
    <symbol id="admin-icon-bed" viewBox="0 0 24 24"><path d="M3 18v3m18-3v3M3 14V5m18 13v-6a2 2 0 0 0-2-2H9v8M3 18h18M3 10h6v8M5 7h1"/></symbol>
</svg>
<div class="admin-shell">
    <?php if ($adminAuthenticated): ?>
        <aside class="admin-sidebar" aria-label="Menú de administración">
            <a class="admin-brand" href="<?= e(url(['vista' => 'admin'])) ?>" aria-label="<?= e($app['nombre']) ?>, administración">
                <span class="admin-brand-mark"><?= $adminIcon('compass') ?></span>
                <span><span class="admin-brand-name"><?= e($app['nombre']) ?><span>.</span></span><span class="admin-brand-label">Panel de administración</span></span>
            </a>
            <p class="admin-nav-label">Gestión del catálogo</p>
            <nav class="admin-navigation" aria-label="Navegación del panel">
                <a class="admin-nav-link <?= !$adminHotelView && !$adminIsNew ? 'is-active' : '' ?>" href="<?= e(url(['vista' => 'admin'])) ?>" <?= $adminView === 'index' ? 'aria-current="page"' : '' ?>><?= $adminIcon('grid') ?><span>Destinos</span></a>
                <a class="admin-nav-link <?= $adminHotelView && !$adminIsNew ? 'is-active' : '' ?>" href="<?= e(url(['vista' => 'admin_hoteles'])) ?>" <?= $adminView === 'hotel_index' ? 'aria-current="page"' : '' ?>><?= $adminIcon('bed') ?><span>Hoteles</span></a>
                <a class="admin-nav-link <?= $adminIsNew ? 'is-active' : '' ?>" href="<?= e(url(['vista' => $adminHotelView ? 'admin_hotel_nuevo' : 'admin_nuevo'])) ?>" <?= $adminIsNew ? 'aria-current="page"' : '' ?>><?= $adminIcon('plus') ?><span><?= $adminHotelView ? 'Agregar hotel' : 'Agregar destino' ?></span></a>
            </nav>
            <div class="admin-sidebar-bottom">
                <a class="admin-nav-link" href="<?= e(url()) ?>"><?= $adminIcon('external') ?><span>Ver página pública</span></a>
                <div class="admin-sidebar-note"><span class="admin-flag" aria-hidden="true"></span><span>República Dominicana<br><small>Destinos para descubrir</small></span></div>
            </div>
        </aside>
    <?php endif; ?>
    <div class="admin-workspace">
        <header class="admin-header">
            <div class="admin-header-inner">
                <?php if ($adminAuthenticated): ?>
                    <div class="admin-breadcrumb"><span>Administración</span><span aria-hidden="true">/</span><strong><?= e($adminSection) ?></strong></div>
                <?php else: ?>
                    <a class="admin-brand" href="<?= e(url(['vista' => 'admin'])) ?>" aria-label="<?= e($app['nombre']) ?>, administración"><span class="admin-brand-mark"><?= $adminIcon('compass') ?></span><span class="admin-brand-name"><?= e($app['nombre']) ?><span>.</span></span></a>
                <?php endif; ?>
                <div class="admin-header-actions">
                    <a class="admin-public-link" href="<?= e(url()) ?>">Ver página <?= $adminIcon('external') ?></a>
                    <?php if ($adminAuthenticated): ?>
                        <div class="admin-account"><span class="admin-avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($currentUser['usuario'], 0, 1, 'UTF-8'), 'UTF-8')) ?></span><span><?= e($currentUser['usuario']) ?><small>Administrador</small></span></div>
                        <form method="post" action="<?= e(url(['vista' => 'admin_logout'])) ?>">
                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                            <button class="admin-logout" type="submit">Salir</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </header>
        <main id="contenido" class="admin-main <?= $adminAuthenticated ? '' : 'admin-auth-main' ?>">
            <?php if (!$adminAuthenticated): ?>
                <section class="admin-auth-introduction" aria-labelledby="admin-intro-title">
                    <p class="admin-kicker">Gestión de destinos</p>
                    <h2 id="admin-intro-title">Tu catálogo,<br>en un solo lugar.</h2>
                    <p>Administra los destinos y alojamientos que los visitantes descubrirán en CercaRD.</p>
                    <ul class="admin-auth-features">
                        <li><?= $adminIcon('grid') ?><span><strong>Destinos organizados</strong><small>Edita la información y decide qué publicar.</small></span></li>
                        <li><?= $adminIcon('photo') ?><span><strong>Fotos de cada lugar</strong><small>Actualiza las imágenes y sus créditos.</small></span></li>
                        <li><?= $adminIcon('pin') ?><span><strong>Ubicación y rutas</strong><small>Completa las coordenadas para el mapa.</small></span></li>
                    </ul>
                    <div class="admin-auth-island"><span class="admin-flag" aria-hidden="true"></span>Hecho para descubrir República Dominicana.</div>
                </section>
            <?php endif; ?>
            <div class="admin-view-content">
                <?php if (!empty($message)): ?><div class="admin-alert admin-alert-success" role="status"><?= e($message) ?></div><?php endif; ?>
                <?php if (!empty($errors)): ?>
                    <div class="admin-alert admin-alert-error" role="alert" tabindex="-1">
                        <strong>Revisa estos detalles:</strong>
                        <ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
                    </div>
                <?php endif; ?>
                <?php require PROJECT_ROOT . '/app/Views/admin/' . $adminTemplates[$adminView] . '.php'; ?>
            </div>
        </main>
        <footer class="admin-footer"><span><?= e($app['nombre']) ?> · Administración de destinos</span><span class="admin-flag" aria-hidden="true"></span></footer>
    </div>
</div>
</body>
</html>
