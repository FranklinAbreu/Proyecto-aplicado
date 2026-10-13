</main>
<footer class="site-footer">
    <div class="container footer-main">
        <div class="footer-brand"><a class="brand" href="<?= e(url()) ?>" aria-label="<?= e($app['nombre']) ?>, inicio"><span class="brand-mark" aria-hidden="true"><svg class="icon" viewBox="0 0 24 24"><use href="#icon-compass"/></svg></span><span class="brand-name"><?= e($app['nombre']) ?><span class="brand-dot">.</span></span></a><p>Lo extraordinario está más cerca<br>de lo que imaginas.</p></div>
        <div class="footer-explore"><span class="footer-label">SIGUE DESCUBRIENDO</span><nav aria-label="Explora desde el pie de página"><a href="<?= e(url(['vista' => 'lugares'])) ?>">Todos los destinos</a><a href="<?= e(url(['vista' => 'lugares', 'tipo' => 'Playa'])) ?>">Un día de playa</a><a href="<?= e(url(['vista' => 'lugares', 'tipo' => 'Parque'])) ?>">Entre naturaleza</a></nav></div>
        <p class="footer-note"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#icon-pin"/></svg> Hecho para descubrir<br><strong>República Dominicana.</strong><br>Créditos fotográficos en cada ficha.</p>
    </div>
    <div class="container footer-bottom"><span>© <?= e(date('Y')) ?> <?= e($app['nombre']) ?> · Proyecto académico</span><a href="<?= e(url(['vista' => 'admin'])) ?>">Administrar destinos</a><span>Un país. Infinitas ganas de explorarlo.</span></div>
</footer>
<?php if (isset($mapPlaces)): ?>
<script src="<?= e(asset_url('vendor/leaflet/leaflet.js')) ?>" defer></script>
<script src="<?= e(asset_url('js/map.js') . '?v=' . $app['version']) ?>" defer></script>
<?php endif; ?>
</body>
</html>
