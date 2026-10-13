<?php
[$coverClass, $symbol] = place_style($place['tipo']);
$placeMeta = destination_meta($place);
$placeImage = image_url($place);
$mapUrl = maps_url($place);
?>
<div class="container detail-container">
    <nav class="breadcrumb" aria-label="Ruta de navegación"><a href="<?= e(url()) ?>">Inicio</a><span aria-hidden="true">/</span><a href="<?= e(url(['vista' => 'lugares'])) ?>">Destinos</a><span aria-hidden="true">/</span><span aria-current="page"><?= e($place['nombre']) ?></span></nav>
    <figure class="detail-figure" id="foto"><div class="detail-cover <?= e($coverClass) ?> <?= $placeImage !== '' ? 'has-photo' : '' ?>">
        <?php if ($placeImage !== ''): ?><img src="<?= e($placeImage) ?>" alt="<?= e($placeMeta['image_alt']) ?>" fetchpriority="high" decoding="async"><?php else: ?><span class="place-symbol" aria-hidden="true"><?= e($symbol) ?></span><?php endif; ?><div class="detail-shade"></div>
        <div class="detail-heading"><span class="tag"><?= e($place['tipo']) ?></span><h1><?= e($place['nombre']) ?></h1><p><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#icon-pin"/></svg><?= e($place['provincia']) ?>, República Dominicana</p></div>
    </div>
        <?php if ($placeImage !== '' && ($placeMeta['photo_source'] !== '' || $placeMeta['photo_author'] !== '' || $placeMeta['photo_license'] !== '')): ?>
            <figcaption class="photo-credit">
                Fotografía: <?php if ($placeMeta['photo_author_url'] !== ''): ?><a href="<?= e($placeMeta['photo_author_url']) ?>" target="_blank" rel="noopener noreferrer"><?= e($placeMeta['photo_author']) ?></a><?php else: ?><?= e($placeMeta['photo_author']) ?><?php endif; ?>
                <?php if ($placeMeta['photo_license'] !== ''): ?> · <?php if ($placeMeta['photo_license_url'] !== ''): ?><a href="<?= e($placeMeta['photo_license_url']) ?>" target="_blank" rel="noopener noreferrer"><?= e($placeMeta['photo_license']) ?></a><?php else: ?><?= e($placeMeta['photo_license']) ?><?php endif; ?><?php endif; ?>
                <?php if ($placeMeta['photo_source'] !== ''): ?> · <a href="<?= e($placeMeta['photo_source']) ?>" target="_blank" rel="noopener noreferrer"><?= e($placeMeta['photo_source_label'] ?: 'Fuente de la fotografía') ?> ↗</a><?php endif; ?> · Imagen recortada para su presentación.
            </figcaption>
        <?php endif; ?>
    </figure>
    <div class="detail-layout"><article class="detail-main"><p class="section-kicker">UNA ESCAPADA PARA RECORDAR</p><section class="detail-section"><h2>Un poco de este lugar.</h2><p><?= e($place['descripcion']) ?></p></section><?php if ($placeMeta['highlight'] !== ''): ?><div class="detail-highlight"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#icon-compass"/></svg><div><span>EL PAISAJE TE ESPERA</span><p><?= e($placeMeta['highlight']) ?></p></div></div><?php endif; ?><?php if ($placeMeta['source_url'] !== ''): ?><p class="destination-source">Conoce más en la <a href="<?= e($placeMeta['source_url']) ?>" target="_blank" rel="noopener noreferrer">fuente oficial del destino ↗</a>.</p><?php endif; ?><a class="text-link detail-back" href="<?= e(url(['vista' => 'lugares'])) ?>"><span aria-hidden="true">←</span> Sigue descubriendo destinos</a></article>
        <aside class="detail-aside" aria-label="Información del destino"><p class="section-kicker">UBICA TU PRÓXIMO PLAN</p><h2>Antes de explorar</h2><dl class="detail-facts"><dt>Provincia</dt><dd><?= e($place['provincia']) ?></dd><dt>Experiencia</dt><dd><?= e($place['tipo']) ?></dd><dt>Ubicación</dt><dd><?= e($place['ubicacion']) ?></dd></dl><?php if ($mapUrl !== null): ?><a class="button button-primary" href="<?= e($mapUrl) ?>" target="_blank" rel="noopener noreferrer"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#icon-pin"/></svg> Ver en Google Maps <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#icon-arrow-up"/></svg></a><p class="map-note">El mapa muestra un punto de referencia aproximado; no indica una entrada exacta.</p><?php else: ?><p class="notice">Consulta la fuente oficial para conocer el acceso al destino.</p><?php endif; ?></aside>
    </div>
    <?php require PROJECT_ROOT . '/app/Views/partials/status.php'; ?>
</div>
