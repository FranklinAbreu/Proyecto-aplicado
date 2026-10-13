<?php
$adminSearch = input_text('q');
$adminResultCount = count($places);
?>
<div class="admin-page-heading">
    <div>
        <p class="admin-kicker">Administración del catálogo</p>
        <h1>Destinos</h1>
        <p>Organiza y actualiza los lugares, las fotografías y la información del catálogo.</p>
    </div>
    <a class="admin-button admin-button-primary" href="<?= e(url(['vista' => 'admin_nuevo'])) ?>">
        <span aria-hidden="true">＋</span>
        Agregar destino
    </a>
</div>

<div class="admin-stats" aria-label="Resumen del catálogo">
    <article class="admin-stat">
        <span class="admin-stat-label">Total de destinos</span>
        <strong><?= e($stats['total']) ?></strong>
        <span>Publicados y ocultos</span>
    </article>
    <article class="admin-stat">
        <span class="admin-stat-label">Destinos publicados</span>
        <strong><?= e($stats['activos']) ?></strong>
        <span>Visibles para los visitantes</span>
    </article>
    <article class="admin-stat">
        <span class="admin-stat-label">Sin coordenadas</span>
        <strong><?= e($stats['sin_coordenadas']) ?></strong>
        <span>Pendientes de ubicar en el mapa</span>
    </article>
</div>

<section class="admin-catalog" aria-labelledby="catalogo-titulo">
    <div class="admin-catalog-heading">
        <div>
            <h2 id="catalogo-titulo">Listado de destinos</h2>
            <p>Consulta el estado de cada lugar y edita sus datos.</p>
        </div>
        <span class="admin-result-count">
            <?= e($adminResultCount) ?> <?= $adminResultCount === 1 ? 'destino' : 'destinos' ?>
        </span>
    </div>

    <form class="admin-search" method="get" action="<?= e(url()) ?>" role="search">
        <input type="hidden" name="vista" value="admin">
        <div class="admin-search-field">
            <label for="buscar">Buscar destino</label>
            <input id="buscar" name="q" type="search" value="<?= e($adminSearch) ?>" maxlength="100" placeholder="Nombre, provincia o tipo de lugar">
        </div>
        <button class="admin-button admin-button-secondary" type="submit">Buscar</button>
        <?php if ($adminSearch !== ''): ?>
            <a class="admin-clear" href="<?= e(url(['vista' => 'admin'])) ?>">Limpiar búsqueda</a>
        <?php endif; ?>
    </form>

    <?php if (!$places): ?>
        <div class="admin-empty">
            <span aria-hidden="true">◇</span>
            <h3><?= $adminSearch !== '' ? 'No se encontraron destinos.' : 'Todavía no hay destinos.' ?></h3>
            <p><?= $adminSearch !== '' ? 'Prueba con otro nombre, provincia o tipo de lugar.' : 'Agrega un lugar para comenzar a organizar el catálogo.' ?></p>
            <a class="admin-button admin-button-primary" href="<?= e(url(['vista' => $adminSearch !== '' ? 'admin' : 'admin_nuevo'])) ?>">
                <?= $adminSearch !== '' ? 'Ver todos los destinos' : 'Agregar destino' ?>
            </a>
        </div>
    <?php else: ?>
        <div class="admin-list-wrap">
            <table class="admin-list">
                <thead class="admin-list-head">
                    <tr>
                        <th scope="col">Destino</th>
                        <th scope="col">Tipo</th>
                        <th scope="col">Publicación</th>
                        <th scope="col">Mapa</th>
                        <th scope="col">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($places as $place): ?>
                        <?php
                        $adminPlaceImage = (string) ($place['imagen'] ?? '');
                        $adminPlaceActive = (int) ($place['activo'] ?? 1) === 1;
                        $adminHasCoordinates = is_numeric($place['latitud'] ?? null)
                            && is_numeric($place['longitud'] ?? null);
                        ?>
                        <tr class="admin-row">
                            <td class="admin-row-destination" data-label="Destino">
                                <div class="admin-row-place">
                                    <?php if ($adminPlaceImage !== ''): ?>
                                        <img class="admin-row-photo" src="<?= e(asset_url($adminPlaceImage)) ?>" alt="<?= e(($place['imagen_alt'] ?? '') ?: $place['nombre']) ?>" loading="lazy" decoding="async">
                                    <?php else: ?>
                                        <span class="admin-row-photo admin-row-photo-empty" aria-hidden="true">◇</span>
                                    <?php endif; ?>
                                    <div class="admin-row-info">
                                        <h3><?= e($place['nombre']) ?></h3>
                                        <p><?= e($place['provincia']) ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="admin-row-type" data-label="Tipo">
                                <span class="admin-row-type-label"><?= e($place['tipo']) ?></span>
                            </td>
                            <td class="admin-row-status" data-label="Publicación">
                                <span class="admin-badge <?= $adminPlaceActive ? 'admin-badge-active' : 'admin-badge-hidden' ?>">
                                    <?= $adminPlaceActive ? 'Publicado' : 'Oculto' ?>
                                </span>
                            </td>
                            <td class="admin-row-coordinates" data-label="Mapa">
                                <span class="admin-coordinate-note <?= $adminHasCoordinates ? '' : 'admin-coordinate-missing' ?>">
                                    <?= $adminHasCoordinates ? 'Con coordenadas' : 'Sin coordenadas' ?>
                                </span>
                            </td>
                            <td class="admin-row-actions" data-label="Acciones">
                                <a class="admin-edit-link admin-row-edit" href="<?= e(url(['vista' => 'admin_editar', 'id' => $place['id']])) ?>" aria-label="Editar <?= e($place['nombre']) ?>">
                                    Editar
                                </a>
                                <?php if ($adminPlaceActive): ?>
                                    <a class="admin-destination-public admin-row-public" href="<?= e(url(['vista' => 'detalle', 'id' => $place['id']])) ?>" aria-label="Ver ficha pública de <?= e($place['nombre']) ?>">
                                        Ver ficha <span aria-hidden="true">↗</span>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
