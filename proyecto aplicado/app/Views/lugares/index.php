<?php
$isHome = $currentPage === 'inicio';
$heroPlace = ['id' => 101];
$heroMeta = destination_meta($heroPlace);
$heroImage = image_url($heroPlace);
$categories = [['', 'Todo', 'compass'], ['Playa', 'Playas', 'sun'], ['Río', 'Ríos', 'waves'], ['Parque', 'Naturaleza', 'leaf'], ['Cultural', 'Cultura', 'culture']];
?>
<?php if ($isHome): ?>
    <section class="hero" aria-labelledby="hero-title">
        <?php if ($heroImage !== ''): ?><img class="hero-image" src="<?= e($heroImage) ?>" alt="<?= e($heroMeta['image_alt']) ?>" fetchpriority="high" decoding="async"><?php endif; ?>
        <div class="hero-shade"></div>
        <div class="container hero-content">
            <p class="eyebrow"><span></span>DESCUBRE REPÚBLICA DOMINICANA</p>
            <h1 id="hero-title">Hay un país entero<br>por <em>descubrir.</em></h1>
            <p class="hero-description">De Cotuí y Fantino a los paisajes de todo el país.<br class="desktop-break"> Descubre lo nuestro y encuentra tu próxima ruta.</p>
            <a class="button button-coral" href="#catalogo">Quiero explorar <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#icon-arrow"/></svg></a>
            <a class="hero-location" href="<?= e(url(['vista' => 'detalle', 'id' => 101])) ?>"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#icon-pin"/></svg><span><strong>Bahía de las Águilas</strong><small>Pedernales · Costa suroeste</small></span><svg class="icon location-arrow" viewBox="0 0 24 24" aria-hidden="true"><use href="#icon-arrow-up"/></svg></a>
            <?php if ($heroImage !== '' && $heroMeta['photo_source'] !== ''): ?><p class="hero-photo-credit">Foto: <?= e($heroMeta['photo_author']) ?> · <a href="<?= e(url(['vista' => 'detalle', 'id' => 101])) ?>#foto">Créditos y licencia ↗</a></p><?php endif; ?>
        </div>
    </section>
<?php else: ?>
    <section class="catalog-intro container" aria-labelledby="explore-title"><p class="section-kicker">UN BUEN PLAN EMPIEZA AQUÍ</p><h1 id="explore-title">Encuentra tu próxima <em>escapada.</em></h1><p>De la costa a las montañas, descubre lugares que merecen una visita.</p></section>
<?php endif; ?>

<div class="container">
    <section id="catalogo" class="catalog-section <?= $isHome ? 'catalog-home' : '' ?>" aria-labelledby="catalog-title">
        <form class="filter-form" action="<?= e(url()) ?>#catalogo" method="get">
            <input type="hidden" name="vista" value="lugares">
            <div class="form-field search-field"><label for="q"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#icon-search"/></svg> ¿Adónde quieres ir?</label><input type="search" name="q" id="q" value="<?= e($query) ?>" placeholder="Un destino, una idea, una escapada…" maxlength="100"></div>
            <div class="form-field"><label for="provincia"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#icon-pin"/></svg> Provincia</label><select name="provincia" id="provincia"><option value="">Todas las provincias</option><?php foreach ($provinces as $option): ?><option value="<?= e($option) ?>" <?= $province === $option ? 'selected' : '' ?>><?= e($option) ?></option><?php endforeach; ?></select></div>
            <div class="form-field"><label for="tipo">Tu experiencia</label><select name="tipo" id="tipo"><option value="">Todos los tipos</option><?php foreach ($types as $option): ?><option value="<?= e($option) ?>" <?= $type === $option ? 'selected' : '' ?>><?= e($option) ?></option><?php endforeach; ?></select></div>
            <button type="submit" class="button button-primary"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#icon-search"/></svg> Buscar</button>
        </form>
        <div class="section-heading"><div><p class="section-kicker">CERCA, PERO FUERA DE LA RUTINA</p><h2 id="catalog-title">Elige tu próximo recuerdo.</h2><p>Lugares reales. Nuevas razones para salir.</p></div><span class="results-count"><?= count($places) ?> <?= count($places) === 1 ? 'destino encontrado' : 'destinos encontrados' ?></span></div>
        <div class="catalog-toolbar">
            <nav class="category-nav" aria-label="Filtrar por experiencia"><?php foreach ($categories as [$categoryValue, $categoryLabel, $categoryIcon]): ?><a class="category-chip <?= $type === $categoryValue ? 'is-selected' : '' ?>" href="<?= e(url(['vista' => 'lugares', 'q' => $query, 'provincia' => $province, 'tipo' => $categoryValue])) ?>#catalogo" <?= $type === $categoryValue ? 'aria-current="true"' : '' ?>><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#icon-<?= e($categoryIcon) ?>"/></svg><?= e($categoryLabel) ?></a><?php endforeach; ?></nav>
            <?php if ($query !== '' || $province !== '' || $type !== ''): ?><a class="clear-filters" href="<?= e(url(['vista' => 'lugares'])) ?>#catalogo">Limpiar filtros <span aria-hidden="true">×</span></a><?php endif; ?>
        </div>
        <div class="local-destinations"><span class="local-label">CERCA DE CASA</span><strong>Cotuí y Fantino también te esperan.</strong><div><a href="<?= e(url(['vista' => 'lugares', 'q' => 'Cotuí'])) ?>#catalogo">Ver Cotuí <span aria-hidden="true">↗</span></a><a href="<?= e(url(['vista' => 'lugares', 'q' => 'Fantino'])) ?>#catalogo">Ver Fantino <span aria-hidden="true">↗</span></a></div></div>
        <?php if ($places): ?><div class="places-grid"><?php foreach ($places as $place): ?><?php require PROJECT_ROOT . '/app/Views/partials/place-card.php'; ?><?php endforeach; ?></div><?php else: ?><div class="empty-state"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#icon-compass"/></svg><h3>Tu escapada sigue por descubrir.</h3><p>No encontramos destinos con esos filtros. Prueba otra palabra o provincia.</p><a class="button button-primary" href="<?= e(url(['vista' => 'lugares'])) ?>#catalogo">Ver todos los destinos</a></div><?php endif; ?>
        <?php require PROJECT_ROOT . '/app/Views/partials/status.php'; ?>
    </section>
    </div>
    <?php require PROJECT_ROOT . '/app/Views/partials/map.php'; ?>
    <div class="container">
    <?php if ($isHome): ?>
        <section class="island-section" id="descubre" aria-labelledby="island-title">
            <div class="island-intro"><p class="section-kicker">ESO QUE NOS HACE ÚNICOS</p><h2 id="island-title">Una isla.<br><em>Muchas formas de vivirla.</em></h2><p>A veces el mejor plan es cambiar de paisaje.<br>Encuentra el tuyo, a tu ritmo.</p><a class="text-link" href="<?= e(url(['vista' => 'lugares'])) ?>">Descubre todos los destinos <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#icon-arrow"/></svg></a></div>
            <div class="island-experiences">
                <a class="experience" href="<?= e(url(['vista' => 'lugares', 'tipo' => 'Playa'])) ?>#catalogo"><span class="experience-icon"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#icon-sun"/></svg></span><div><h3>Donde el azul te encuentra</h3><p>Playas para detener el tiempo y volver con sal en la piel.</p></div><svg class="icon experience-arrow" viewBox="0 0 24 24" aria-hidden="true"><use href="#icon-arrow-up"/></svg></a>
                <a class="experience" href="<?= e(url(['vista' => 'lugares', 'tipo' => 'Parque'])) ?>#catalogo"><span class="experience-icon"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#icon-leaf"/></svg></span><div><h3>Respira un poco más lento</h3><p>Senderos, manglares y paisajes que invitan a reconectar.</p></div><svg class="icon experience-arrow" viewBox="0 0 24 24" aria-hidden="true"><use href="#icon-arrow-up"/></svg></a>
                <a class="experience" href="<?= e(url(['vista' => 'lugares', 'tipo' => 'Cultural'])) ?>#catalogo"><span class="experience-icon"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#icon-culture"/></svg></span><div><h3>Historias en cada rincón</h3><p>Arte, arquitectura y lugares con una identidad propia.</p></div><svg class="icon experience-arrow" viewBox="0 0 24 24" aria-hidden="true"><use href="#icon-arrow-up"/></svg></a>
            </div>
        </section>
    <?php endif; ?>
</div>
