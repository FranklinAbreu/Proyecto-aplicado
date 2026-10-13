<?php
$relatedHotelItems = isset($relatedHotels) && is_array($relatedHotels) ? $relatedHotels : [];
$relatedHotelCatalogParameters = ['vista' => 'hoteles'];
if (!empty($place['id'])) {
    $relatedHotelCatalogParameters['destino'] = $place['id'];
}
?>
<section class="related-hotels" aria-labelledby="related-hotels-title">
    <div class="section-heading">
        <div><p class="section-kicker">ALOJAMIENTOS PARA TU VISITA</p><h2 id="related-hotels-title">Dónde alojarte</h2><p>Opciones de alojamiento relacionadas con este destino.</p></div>
        <?php if ($relatedHotelItems): ?><a class="text-link" href="<?= e(url($relatedHotelCatalogParameters)) ?>">Ver alojamientos <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#icon-arrow"/></svg></a><?php endif; ?>
    </div>
    <?php if ($relatedHotelItems): ?>
        <div class="hotels-grid">
            <?php foreach (array_slice($relatedHotelItems, 0, 3) as $hotelRecord): ?>
                <?php require PROJECT_ROOT . '/app/Views/partials/hotel-card.php'; ?>
            <?php endforeach; ?>
        </div>
        <p class="hotels-distance-note">Las distancias que aparecen son en línea recta entre puntos de referencia. Consulta el mapa para planificar tu recorrido.</p>
    <?php else: ?>
        <div class="hotels-related-empty">
            <p><?= !empty($hotelUnavailable) ? 'Los alojamientos de este destino no están disponibles en este momento.' : 'Aún no hay alojamientos asociados a este destino en el catálogo.' ?></p>
            <a class="text-link" href="<?= e(url(['vista' => 'hoteles'])) ?>">Explorar otros alojamientos <span aria-hidden="true">→</span></a>
        </div>
    <?php endif; ?>
</section>
