<?php
[$coverClass, $symbol] = place_style($place['tipo']);
$placeMeta = destination_meta($place);
$placeImage = image_url($place);
$detailUrl = url(['vista' => 'detalle', 'id' => $place['id']]);
?>
<article class="place-card">
    <a class="place-cover <?= e($coverClass) ?> <?= $placeImage !== '' ? 'has-photo' : '' ?>" href="<?= e($detailUrl) ?>" aria-label="Conocer <?= e($place['nombre']) ?>">
        <?php if ($placeImage !== ''): ?><img src="<?= e($placeImage) ?>" alt="<?= e($placeMeta['image_alt']) ?>" loading="lazy" decoding="async"><?php else: ?><span class="place-symbol" aria-hidden="true"><?= e($symbol) ?></span><?php endif; ?>
        <span class="cover-label"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#icon-pin"/></svg><?= e($place['provincia']) ?></span><span class="cover-arrow" aria-hidden="true"><svg class="icon" viewBox="0 0 24 24"><use href="#icon-arrow-up"/></svg></span>
    </a>
    <div class="place-body"><div class="place-meta"><span class="tag"><?= e($place['tipo']) ?></span><?php if ($placeMeta['region'] !== ''): ?><span><?= e($placeMeta['region']) ?></span><?php endif; ?></div><h3 class="place-title"><a href="<?= e($detailUrl) ?>"><?= e($place['nombre']) ?></a></h3><p class="place-description"><?= e($place['descripcion']) ?></p><div class="place-footer"><span><?= e($placeMeta['highlight']) ?></span><a class="text-link" href="<?= e($detailUrl) ?>" aria-label="Descubrir <?= e($place['nombre']) ?>">Descubrir <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#icon-arrow"/></svg></a></div></div>
</article>
