<?php $hotelSearch = input_text('q'); $hotelResultCount = count($hotels); ?>
<div class="admin-page-heading">
    <div><p class="admin-kicker">Administración del catálogo</p><h1>Hoteles</h1><p>Organiza los alojamientos y vincúlalos con los destinos que pueden visitar sus huéspedes.</p></div>
    <a class="admin-button admin-button-primary" href="<?= e(url(['vista' => 'admin_hotel_nuevo'])) ?>"><span aria-hidden="true">＋</span> Agregar hotel</a>
</div>
<div class="admin-stats" aria-label="Resumen de hoteles">
    <article class="admin-stat"><span class="admin-stat-label">Total de hoteles</span><strong><?= e($stats['total']) ?></strong><span>Publicados y ocultos</span></article>
    <article class="admin-stat"><span class="admin-stat-label">Hoteles publicados</span><strong><?= e($stats['activos']) ?></strong><span>Visibles para los visitantes</span></article>
    <article class="admin-stat"><span class="admin-stat-label">Sin coordenadas</span><strong><?= e($stats['sin_coordenadas']) ?></strong><span>Pendientes de ubicar en el mapa</span></article>
</div>
<section class="admin-catalog" aria-labelledby="hoteles-titulo">
    <div class="admin-catalog-heading"><div><h2 id="hoteles-titulo">Listado de hoteles</h2><p>Actualiza las fotos, los datos de contacto y los destinos asociados.</p></div><span class="admin-result-count"><?= e($hotelResultCount) ?> <?= $hotelResultCount === 1 ? 'hotel' : 'hoteles' ?></span></div>
    <form class="admin-search" method="get" action="<?= e(url()) ?>" role="search">
        <input type="hidden" name="vista" value="admin_hoteles">
        <div class="admin-search-field"><label for="buscar">Buscar hotel</label><input id="buscar" name="q" type="search" value="<?= e($hotelSearch) ?>" maxlength="100" placeholder="Nombre, provincia o municipio"></div>
        <button class="admin-button admin-button-secondary" type="submit">Buscar</button>
        <?php if ($hotelSearch !== ''): ?><a class="admin-clear" href="<?= e(url(['vista' => 'admin_hoteles'])) ?>">Limpiar búsqueda</a><?php endif; ?>
    </form>
    <?php if (!$hotels): ?>
        <div class="admin-empty"><span aria-hidden="true">◇</span><h3><?= $hotelSearch !== '' ? 'No se encontraron hoteles.' : 'Todavía no hay hoteles.' ?></h3><p><?= $hotelSearch !== '' ? 'Prueba con otro nombre, provincia o municipio.' : 'Agrega un alojamiento con información respaldada por sus fuentes.' ?></p><a class="admin-button admin-button-primary" href="<?= e(url(['vista' => $hotelSearch !== '' ? 'admin_hoteles' : 'admin_hotel_nuevo'])) ?>"><?= $hotelSearch !== '' ? 'Ver todos los hoteles' : 'Agregar hotel' ?></a></div>
    <?php else: ?>
        <div class="admin-list-wrap"><table class="admin-list"><thead class="admin-list-head"><tr><th scope="col">Hotel</th><th scope="col">Destinos</th><th scope="col">Publicación</th><th scope="col">Mapa</th><th scope="col">Acciones</th></tr></thead><tbody>
        <?php foreach ($hotels as $hotel): ?>
            <?php $image = (string) ($hotel['imagen'] ?? ''); $active = (int) ($hotel['activo'] ?? 1) === 1; $hasCoordinates = is_numeric($hotel['latitud'] ?? null) && is_numeric($hotel['longitud'] ?? null); ?>
            <tr class="admin-row">
                <td class="admin-row-destination" data-label="Hotel"><div class="admin-row-place"><?php if ($image !== ''): ?><img class="admin-row-photo" src="<?= e(asset_url($image)) ?>" alt="<?= e(($hotel['imagen_alt'] ?? '') ?: $hotel['nombre']) ?>" loading="lazy" decoding="async"><?php else: ?><span class="admin-row-photo admin-row-photo-empty" aria-hidden="true">◇</span><?php endif; ?><div class="admin-row-info"><h3><?= e($hotel['nombre']) ?></h3><p><?= e($hotel['municipio']) ?> · <?= e($hotel['provincia']) ?></p></div></div></td>
                <td class="admin-row-type" data-label="Destinos"><span class="admin-row-type-label"><?= count($hotel['destino_ids'] ?? []) ?> <?= count($hotel['destino_ids'] ?? []) === 1 ? 'destino' : 'destinos' ?></span></td>
                <td class="admin-row-status" data-label="Publicación"><span class="admin-badge <?= $active ? 'admin-badge-active' : 'admin-badge-hidden' ?>"><?= $active ? 'Publicado' : 'Oculto' ?></span></td>
                <td class="admin-row-coordinates" data-label="Mapa"><span class="admin-coordinate-note <?= $hasCoordinates ? '' : 'admin-coordinate-missing' ?>"><?= $hasCoordinates ? 'Con coordenadas' : 'Sin coordenadas' ?></span></td>
                <td class="admin-row-actions" data-label="Acciones"><a class="admin-edit-link admin-row-edit" href="<?= e(url(['vista' => 'admin_hotel_editar', 'id' => $hotel['id']])) ?>" aria-label="Editar <?= e($hotel['nombre']) ?>">Editar</a><?php if ($active): ?><a class="admin-destination-public admin-row-public" href="<?= e(url(['vista' => 'hotel', 'id' => $hotel['id']])) ?>" aria-label="Ver ficha de <?= e($hotel['nombre']) ?>">Ver ficha <span aria-hidden="true">↗</span></a><?php endif; ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody></table></div>
    <?php endif; ?>
</section>
