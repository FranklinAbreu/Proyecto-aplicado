<?php
$mapPlaces = isset($mapPlaces) && is_array($mapPlaces) ? $mapPlaces : [];
$mapOrigins = isset($mapOrigins) && is_array($mapOrigins) ? $mapOrigins : [];
$mapPayload = json_encode([
    'places' => array_values($mapPlaces),
    'origins' => array_values($mapOrigins),
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
?>
<section class="map-section" id="mapa" aria-labelledby="map-heading">
    <div class="container">
        <div class="section-heading map-heading">
            <div><span class="eyebrow">TU PRÓXIMA PARADA</span><h2 id="map-heading">Un destino cerca.<br><em>Una ruta para llegar.</em></h2></div>
            <p>Explora el mapa y encuentra los destinos más cercanos por carretera desde tu ubicación, Cotuí o Fantino.</p>
        </div>
        <div class="map-layout" data-route-endpoint="<?= e(api_url('routes.php')) ?>">
            <div class="map-canvas">
                <div class="map-toolbar">
                    <button type="button" class="button button-primary map-location-button" id="map-locate"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#icon-pin"/></svg> Usar mi ubicación</button>
                    <button type="button" class="map-reset" id="map-reset" disabled>Restablecer mapa</button>
                </div>
                <div id="destinations-map" role="region" aria-label="Mapa de destinos en República Dominicana"></div>
                <div class="map-legend"><span><i class="legend-destination" aria-hidden="true"></i> Destinos</span><span><i class="legend-origin" aria-hidden="true"></i> Punto de partida</span><span><i class="legend-route" aria-hidden="true"></i> Ruta seleccionada</span></div>
            </div>
            <aside class="map-panel" aria-labelledby="map-panel-heading">
                <span class="eyebrow">A TU ALCANCE</span>
                <h3 id="map-panel-heading">¿Desde dónde salimos?</h3>
                <p class="map-intro">Permite tu ubicación para calcular rutas o elige un punto de referencia. El punto de partida se comparte con OSRM para calcular el recorrido.</p>
                <div class="map-origin-options" id="map-origin-options" aria-label="Puntos de partida de referencia"></div>
                <div class="map-status" id="map-status" role="status" aria-live="polite">Selecciona un punto de partida para ver hasta tres destinos cercanos.</div>
                <div class="route-list" id="route-list" aria-label="Destinos y rutas cercanas"></div>
                <p class="map-notice">Rutas en vehículo y tiempos aproximados. Los puntos son referencias del lugar; confirma el acceso antes de salir.</p>
                <p class="map-service-note">Mapa: OpenStreetMap · Rutas: OSRM. Requieren conexión a internet.</p>
                <noscript><p class="map-status">Activa JavaScript para explorar el mapa y calcular las rutas.</p></noscript>
            </aside>
        </div>
        <script type="application/json" id="map-destinations-data"><?= $mapPayload !== false ? $mapPayload : '{"places":[],"origins":[]}' ?></script>
    </div>
</section>
