<?php
$hotelCardMeta = hotel_meta($hotelRecord);
$hotelCardImage = hotel_image_url($hotelRecord);
$hotelCardDetailUrl = url(['vista' => 'hotel', 'id' => $hotelRecord['id']]);
$hotelCardServices = array_values(array_filter(array_map('trim', explode(',', (string) ($hotelRecord['servicios'] ?? '')))));
$hotelCardDistance = $hotelRecord['distance_km'] ?? null;
$hotelCardHasDistance = is_numeric($hotelCardDistance) && is_finite((float) $hotelCardDistance) && (float) $hotelCardDistance >= 0;
?>
<article class="hotel-card">
    <a class="hotel-card-cover <?= $hotelCardImage !== '' ? 'has-photo' : '' ?>" href="<?= e($hotelCardDetailUrl) ?>" aria-label="Conocer <?= e($hotelRecord['nombre']) ?>">
        <?php if ($hotelCardImage !== ''): ?>
            <img src="<?= e($hotelCardImage) ?>" alt="<?= e(($hotelCardMeta['image_alt'] ?? '') ?: $hotelRecord['nombre']) ?>" loading="lazy" decoding="async">
        <?php else: ?>
            <span class="hotel-photo-pending"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#icon-culture"/></svg>Fotografía pendiente</span>
        <?php endif; ?>
        <span class="hotel-card-location"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#icon-pin"/></svg><?= e(($hotelRecord['municipio'] ?? '') ?: $hotelRecord['provincia']) ?></span>
        <span class="hotel-card-arrow" aria-hidden="true">↗</span>
    </a>
    <div class="hotel-card-body">
        <div class="hotel-card-meta"><span>Alojamiento</span><span><?= e($hotelRecord['provincia']) ?></span></div>
        <h3><a href="<?= e($hotelCardDetailUrl) ?>"><?= e($hotelRecord['nombre']) ?></a></h3>
        <p class="hotel-card-description"><?= e($hotelRecord['descripcion'] ?? '') ?></p>
        <?php if ($hotelCardServices): ?>
            <ul class="hotel-card-services" aria-label="Servicios registrados">
                <?php foreach (array_slice($hotelCardServices, 0, 3) as $hotelCardService): ?>
                    <li><?= e($hotelCardService) ?></li>
                <?php endforeach; ?>
                <?php if (count($hotelCardServices) > 3): ?><li>+<?= e(count($hotelCardServices) - 3) ?></li><?php endif; ?>
            </ul>
        <?php endif; ?>
        <?php if ($hotelCardHasDistance): ?>
            <p class="hotel-card-distance"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#icon-pin"/></svg><span><?= e(number_format((float) $hotelCardDistance, 1, ',', '.')) ?> km en línea recta desde el punto de referencia</span></p>
        <?php endif; ?>
        <div class="hotel-card-footer"><span><?= !empty($hotelRecord['telefono']) ? 'Contacto disponible' : 'Consulta la ficha' ?></span><a class="text-link" href="<?= e($hotelCardDetailUrl) ?>" aria-label="Ver ficha de <?= e($hotelRecord['nombre']) ?>">Ver alojamiento <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#icon-arrow"/></svg></a></div>
    </div>
</article>
