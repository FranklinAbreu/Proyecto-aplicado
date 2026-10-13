<?php
$hotelIndexItems = isset($hotels) && is_array($hotels) ? $hotels : [];
$hotelDestinationFilter = isset($destinationFilter) && is_array($destinationFilter) ? $destinationFilter : null;
$hotelResetParameters = ['vista' => 'hoteles'];
if ($hotelDestinationFilter !== null) {
    $hotelResetParameters['destino'] = $hotelDestinationFilter['id'];
}
$hotelHasSearch = $query !== '' || $province !== '' || $municipio !== '';
$hotelHeroRecord = null;
$hotelHeroImage = '';
foreach ($hotelIndexItems as $hotelHeroCandidate) {
    $hotelHeroCandidateImage = hotel_image_url($hotelHeroCandidate);
    if ($hotelHeroCandidateImage !== '') {
        $hotelHeroRecord = $hotelHeroCandidate;
        $hotelHeroImage = $hotelHeroCandidateImage;
        break;
    }
}
$hotelHeroMeta = $hotelHeroRecord !== null ? hotel_meta($hotelHeroRecord) : [];
$hotelEmptyActionParameters = $hotelResetParameters;
$hotelEmptyActionLabel = 'Limpiar búsqueda';
if (!empty($hotelUnavailable)) {
    $hotelEmptyHeading = 'El catálogo no está disponible.';
    $hotelEmptyDescription = 'Intenta de nuevo más tarde para consultar los hoteles del catálogo.';
    $hotelEmptyActionParameters = ['vista' => 'hoteles'];
    $hotelEmptyActionLabel = 'Intentar de nuevo';
} elseif ($hotelHasSearch) {
    $hotelEmptyHeading = 'No encontramos alojamientos.';
    $hotelEmptyDescription = 'Prueba otra búsqueda o selecciona otra provincia o municipio.';
} elseif ($hotelDestinationFilter !== null) {
    $hotelEmptyHeading = 'Aún no hay alojamientos asociados.';
    $hotelEmptyDescription = 'Por el momento, este destino no tiene alojamientos asociados en el catálogo.';
    $hotelEmptyActionParameters = ['vista' => 'hoteles'];
    $hotelEmptyActionLabel = 'Ver otros alojamientos';
} else {
    $hotelEmptyHeading = 'Aún no hay alojamientos publicados.';
    $hotelEmptyDescription = 'El catálogo no tiene alojamientos disponibles para consultar por el momento.';
    $hotelEmptyActionParameters = ['vista' => 'lugares'];
    $hotelEmptyActionLabel = 'Explorar destinos';
}
?>
<section class="hotels-intro container <?= $hotelHeroRecord !== null ? 'hotels-intro-with-photo' : '' ?>" aria-labelledby="hotels-title">
    <div class="hotels-intro-copy">
        <p class="section-kicker">TU VIAJE, A TU RITMO</p>
        <h1 id="hotels-title">Un lugar para<br><em>quedarte.</em></h1>
        <p>Explora hoteles y alojamientos reales en República Dominicana. Encuentra sus datos de contacto y organiza tu visita.</p>
        <div class="hotels-intro-facts"><span><strong><?= e($hotelTotal ?? count($hotelIndexItems)) ?></strong> alojamientos en el catálogo</span><a href="<?= e(url(['vista' => 'lugares'])) ?>">Explorar destinos <span aria-hidden="true">↗</span></a></div>
    </div>
    <?php if ($hotelHeroRecord !== null): ?>
        <figure class="hotels-intro-photo">
            <a href="<?= e(url(['vista' => 'hotel', 'id' => $hotelHeroRecord['id']])) ?>"><img src="<?= e($hotelHeroImage) ?>" alt="<?= e(($hotelHeroMeta['image_alt'] ?? '') ?: $hotelHeroRecord['nombre']) ?>" fetchpriority="high" decoding="async"><span><strong><?= e($hotelHeroRecord['nombre']) ?></strong><small><?= e($hotelHeroRecord['municipio'] ?? $hotelHeroRecord['provincia']) ?> · Ver alojamiento ↗</small></span></a>
            <figcaption><a href="<?= e(url(['vista' => 'hotel', 'id' => $hotelHeroRecord['id']])) ?>#foto">Fotografía del alojamiento · Créditos en su ficha</a></figcaption>
        </figure>
    <?php endif; ?>
</section>

<div class="container">
    <section class="hotels-catalog" id="alojamientos" aria-labelledby="hotels-catalog-title">
        <?php if ($hotelDestinationFilter !== null): ?>
            <div class="hotels-destination-context"><div><span>PARA TU VISITA A</span><strong><?= e($hotelDestinationFilter['nombre']) ?></strong><p>Alojamientos asociados a este destino.</p></div><a class="text-link" href="<?= e(url(['vista' => 'detalle', 'id' => $hotelDestinationFilter['id']])) ?>">Ver destino <span aria-hidden="true">↗</span></a><a class="hotels-context-clear" href="<?= e(url(['vista' => 'hoteles'])) ?>">Ver todos los alojamientos</a></div>
        <?php endif; ?>

        <form class="hotels-filter-form" action="<?= e(url()) ?>#alojamientos" method="get" role="search">
            <input type="hidden" name="vista" value="hoteles">
            <?php if ($hotelDestinationFilter !== null): ?><input type="hidden" name="destino" value="<?= e($hotelDestinationFilter['id']) ?>"><?php endif; ?>
            <div class="hotels-filter-field hotels-filter-search"><label for="hotel-q"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#icon-search"/></svg> ¿Dónde quieres alojarte?</label><input id="hotel-q" name="q" type="search" value="<?= e($query) ?>" maxlength="100" placeholder="Nombre, municipio o alojamiento…"></div>
            <div class="hotels-filter-field"><label for="hotel-provincia">Provincia</label><select id="hotel-provincia" name="provincia"><option value="">Todas las provincias</option><?php foreach ($provinces as $hotelProvinceOption): ?><option value="<?= e($hotelProvinceOption) ?>" <?= $province === $hotelProvinceOption ? 'selected' : '' ?>><?= e($hotelProvinceOption) ?></option><?php endforeach; ?></select></div>
            <div class="hotels-filter-field"><label for="hotel-municipio">Municipio</label><select id="hotel-municipio" name="municipio"><option value="">Todos los municipios</option><?php foreach ($municipios as $hotelMunicipioOption): ?><option value="<?= e($hotelMunicipioOption) ?>" <?= $municipio === $hotelMunicipioOption ? 'selected' : '' ?>><?= e($hotelMunicipioOption) ?></option><?php endforeach; ?></select></div>
            <button type="submit" class="button button-primary">Buscar <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#icon-arrow"/></svg></button>
        </form>

        <div class="section-heading hotels-catalog-heading"><div><p class="section-kicker">ALOJAMIENTOS PARA TU VISITA</p><h2 id="hotels-catalog-title"><?= $hotelDestinationFilter !== null ? 'Dónde alojarte para tu visita.' : 'Elige dónde descansar.' ?></h2><p>Conoce cada lugar y consulta directamente con el alojamiento.</p></div><span class="results-count"><?= e(count($hotelIndexItems)) ?> <?= count($hotelIndexItems) === 1 ? 'alojamiento encontrado' : 'alojamientos encontrados' ?></span></div>
        <?php if ($hotelHasSearch): ?><div class="hotels-catalog-tools"><a class="clear-filters" href="<?= e(url($hotelResetParameters)) ?>#alojamientos">Limpiar búsqueda <span aria-hidden="true">×</span></a></div><?php endif; ?>

        <?php if (!empty($hotelUnavailable) && $hotelIndexItems): ?><p class="hotels-service-note" role="status">Algunos datos del catálogo no están disponibles en este momento. Confirma la información directamente con el alojamiento.</p><?php endif; ?>
        <?php if ($hotelIndexItems): ?>
            <div class="hotels-grid">
                <?php foreach ($hotelIndexItems as $hotelRecord): ?>
                    <?php require PROJECT_ROOT . '/app/Views/partials/hotel-card.php'; ?>
                <?php endforeach; ?>
            </div>
            <?php if ($hotelDestinationFilter !== null): ?><p class="hotels-distance-note">Las distancias que aparecen son en línea recta desde el punto de referencia del destino. El recorrido por carretera puede ser diferente.</p><?php endif; ?>
        <?php else: ?>
            <div class="empty-state hotels-empty"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#icon-culture"/></svg><h3><?= e($hotelEmptyHeading) ?></h3><p><?= e($hotelEmptyDescription) ?></p><a class="button button-primary" href="<?= e(url($hotelEmptyActionParameters)) ?><?= ($hotelEmptyActionParameters['vista'] ?? '') === 'hoteles' ? '#alojamientos' : '' ?>"><?= e($hotelEmptyActionLabel) ?></a></div>
        <?php endif; ?>
        <p class="hotels-availability-note"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#icon-compass"/></svg><span>La disponibilidad, las tarifas y los servicios se confirman directamente con cada alojamiento.</span></p>
    </section>
</div>
