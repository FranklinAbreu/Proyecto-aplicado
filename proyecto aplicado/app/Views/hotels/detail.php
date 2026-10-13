<?php
$hotelDetailMeta = hotel_meta($hotel);
$hotelDetailImage = hotel_image_url($hotel);
$hotelDetailMapUrl = hotel_maps_url($hotel);
$hotelDetailBookingUrl = hotel_booking_url($hotel);
$hotelDetailWebsiteUrl = safe_external_url($hotel['sitio_web'] ?? '');
$hotelDetailServices = array_values(array_filter(array_map('trim', explode(',', (string) ($hotel['servicios'] ?? '')))));
$hotelDetailPhone = trim((string) ($hotel['telefono'] ?? ''));
$hotelDetailPhoneDial = preg_replace('/[^0-9+]/', '', $hotelDetailPhone);
$hotelDetailPhoneLink = preg_match('/^\+?[0-9]{6,20}$/', $hotelDetailPhoneDial ?? '') === 1 ? 'tel:' . $hotelDetailPhoneDial : null;
$hotelDetailRelatedPlaces = isset($nearbyPlaces) && is_array($nearbyPlaces) ? $nearbyPlaces : [];
$hotelDetailPhotoCredit = ($hotelDetailMeta['photo_author'] ?? '') !== '' || ($hotelDetailMeta['photo_license'] ?? '') !== '' || ($hotelDetailMeta['photo_source'] ?? '') !== '';
?>
<div class="container hotel-detail-container">
    <nav class="breadcrumb" aria-label="Ruta de navegación"><a href="<?= e(url()) ?>">Inicio</a><span aria-hidden="true">/</span><a href="<?= e(url(['vista' => 'hoteles'])) ?>">Hoteles</a><span aria-hidden="true">/</span><span aria-current="page"><?= e($hotel['nombre']) ?></span></nav>

    <figure class="hotel-detail-figure" id="foto">
        <div class="hotel-detail-cover <?= $hotelDetailImage !== '' ? 'has-photo' : 'hotel-detail-no-photo' ?>">
            <?php if ($hotelDetailImage !== ''): ?><img src="<?= e($hotelDetailImage) ?>" alt="<?= e(($hotelDetailMeta['image_alt'] ?? '') ?: $hotel['nombre']) ?>" fetchpriority="high" decoding="async"><div class="hotel-detail-shade"></div><?php endif; ?>
            <div class="hotel-detail-heading"><span class="tag">Alojamiento</span><h1><?= e($hotel['nombre']) ?></h1><p><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#icon-pin"/></svg><?= e(!empty($hotel['municipio']) ? $hotel['municipio'] . ', ' : '') ?><?= e($hotel['provincia']) ?></p><?php if ($hotelDetailImage === ''): ?><span class="hotel-detail-photo-note">Fotografía pendiente de incorporar al catálogo.</span><?php endif; ?></div>
        </div>
        <?php if ($hotelDetailImage !== '' && $hotelDetailPhotoCredit): ?>
            <figcaption class="photo-credit hotel-photo-credit">
                <?php if (($hotelDetailMeta['photo_author'] ?? '') !== ''): ?>Fotografía: <?php if (($hotelDetailMeta['photo_author_url'] ?? '') !== ''): ?><a href="<?= e($hotelDetailMeta['photo_author_url']) ?>" target="_blank" rel="noopener noreferrer"><?= e($hotelDetailMeta['photo_author']) ?></a><?php else: ?><?= e($hotelDetailMeta['photo_author']) ?><?php endif; ?>. <?php endif; ?>
                <?php if (($hotelDetailMeta['photo_license'] ?? '') !== ''): ?>Licencia: <?php if (($hotelDetailMeta['photo_license_url'] ?? '') !== ''): ?><a href="<?= e($hotelDetailMeta['photo_license_url']) ?>" target="_blank" rel="noopener noreferrer"><?= e($hotelDetailMeta['photo_license']) ?></a><?php else: ?><?= e($hotelDetailMeta['photo_license']) ?><?php endif; ?>. <?php endif; ?>
                <?php if (($hotelDetailMeta['photo_source'] ?? '') !== ''): ?><a href="<?= e($hotelDetailMeta['photo_source']) ?>" target="_blank" rel="noopener noreferrer"><?= e(($hotelDetailMeta['photo_source_label'] ?? '') ?: 'Fuente de la fotografía') ?> ↗</a>. <?php endif; ?>Imagen recortada para su presentación.
            </figcaption>
        <?php endif; ?>
    </figure>

    <div class="hotel-detail-layout">
        <article class="hotel-detail-main">
            <section class="hotel-detail-section"><p class="section-kicker">CONOCE EL ALOJAMIENTO</p><h2>Tu estancia aquí.</h2><p class="hotel-detail-description"><?= e($hotel['descripcion'] ?? '') ?></p></section>
            <?php if ($hotelDetailServices): ?>
                <section class="hotel-detail-section" aria-labelledby="hotel-services-title"><h2 id="hotel-services-title">Servicios registrados</h2><ul class="hotel-detail-services"><?php foreach ($hotelDetailServices as $hotelDetailService): ?><li><span aria-hidden="true">✓</span><?= e($hotelDetailService) ?></li><?php endforeach; ?></ul><p class="hotel-service-help">Consulta con el alojamiento las condiciones de estos servicios.</p></section>
            <?php endif; ?>
            <?php if (($hotelDetailMeta['source_url'] ?? '') !== ''): ?><p class="hotel-information-source">Información del alojamiento: <a href="<?= e($hotelDetailMeta['source_url']) ?>" target="_blank" rel="noopener noreferrer">consultar fuente ↗</a><?php if (!empty($hotel['verificado_en'])): ?> · Consultada: <?= e($hotel['verificado_en']) ?><?php endif; ?>.</p><?php endif; ?>
            <a class="text-link hotel-detail-back" href="<?= e(url(['vista' => 'hoteles'])) ?>"><span aria-hidden="true">←</span> Ver otros alojamientos</a>
        </article>

        <aside class="hotel-detail-aside" aria-labelledby="hotel-contact-title">
            <p class="section-kicker">ORGANIZA TU VISITA</p><h2 id="hotel-contact-title">Contacto y ubicación</h2>
            <dl class="hotel-detail-facts"><dt>Provincia</dt><dd><?= e($hotel['provincia']) ?></dd><?php if (!empty($hotel['municipio'])): ?><dt>Municipio</dt><dd><?= e($hotel['municipio']) ?></dd><?php endif; ?><dt>Dirección</dt><dd><?= e($hotel['ubicacion'] ?? '') ?></dd><?php if ($hotelDetailPhone !== ''): ?><dt>Teléfono</dt><dd><?php if ($hotelDetailPhoneLink !== null): ?><a href="<?= e($hotelDetailPhoneLink) ?>"><?= e($hotelDetailPhone) ?></a><?php else: ?><?= e($hotelDetailPhone) ?><?php endif; ?></dd><?php endif; ?></dl>
            <?php if (!empty($hotelDetailBookingUrl)): ?><a class="button button-primary hotel-contact-button" href="<?= e($hotelDetailBookingUrl) ?>" target="_blank" rel="noopener noreferrer">Consultar disponibilidad <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#icon-arrow-up"/></svg></a><p class="hotel-contact-note">Abre la página externa del alojamiento para consultar disponibilidad y tarifas.</p><?php elseif ($hotelDetailPhoneLink !== null): ?><a class="button button-primary hotel-contact-button" href="<?= e($hotelDetailPhoneLink) ?>">Llamar al alojamiento <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#icon-arrow"/></svg></a><?php endif; ?>
            <?php if ($hotelDetailWebsiteUrl !== null && $hotelDetailWebsiteUrl !== $hotelDetailBookingUrl): ?><a class="hotel-website-link" href="<?= e($hotelDetailWebsiteUrl) ?>" target="_blank" rel="noopener noreferrer">Visitar sitio web <span aria-hidden="true">↗</span></a><?php endif; ?>
            <?php if (!empty($hotelDetailMapUrl)): ?><a class="button button-secondary hotel-map-button" href="<?= e($hotelDetailMapUrl) ?>" target="_blank" rel="noopener noreferrer"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#icon-pin"/></svg> Ver en Google Maps</a><p class="hotel-contact-note">El mapa muestra una referencia del alojamiento. Confirma la dirección antes de salir.</p><?php endif; ?>
        </aside>
    </div>

    <?php if ($hotelDetailRelatedPlaces): ?>
        <section class="hotel-related-destinations" aria-labelledby="hotel-destinations-title"><div class="section-heading"><div><p class="section-kicker">CONTINÚA EXPLORANDO</p><h2 id="hotel-destinations-title">Destinos para tu visita.</h2><p>Lugares relacionados con este alojamiento.</p></div><a class="text-link" href="<?= e(url(['vista' => 'lugares'])) ?>">Todos los destinos <span aria-hidden="true">↗</span></a></div><div class="hotel-destination-links"><?php foreach ($hotelDetailRelatedPlaces as $hotelNearbyPlace): ?><?php $hotelNearbyPlaceImage = image_url($hotelNearbyPlace); ?><a class="hotel-destination-link" href="<?= e(url(['vista' => 'detalle', 'id' => $hotelNearbyPlace['id']])) ?>"><?php if ($hotelNearbyPlaceImage !== ''): ?><img src="<?= e($hotelNearbyPlaceImage) ?>" alt="" loading="lazy" decoding="async"><?php else: ?><span class="hotel-destination-placeholder" aria-hidden="true"><svg class="icon" viewBox="0 0 24 24"><use href="#icon-compass"/></svg></span><?php endif; ?><span><small><?= e($hotelNearbyPlace['tipo']) ?> · <?= e($hotelNearbyPlace['provincia']) ?></small><strong><?= e($hotelNearbyPlace['nombre']) ?></strong></span><span class="hotel-destination-arrow" aria-hidden="true">↗</span></a><?php endforeach; ?></div></section>
    <?php endif; ?>
    <p class="hotels-availability-note"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#icon-compass"/></svg><span>Confirma disponibilidad, tarifas, servicios y condiciones directamente con el alojamiento.</span></p>
</div>
