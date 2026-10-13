<?php
$adminIsEdit = !empty($values['id']);
$adminImage = (string) ($values['imagen'] ?? '');
$adminProvinceOptions = [
    'Azua', 'Bahoruco', 'Barahona', 'Dajabón', 'Distrito Nacional', 'Duarte',
    'Elías Piña', 'El Seibo', 'Espaillat', 'Hato Mayor', 'Hermanas Mirabal',
    'Independencia', 'La Altagracia', 'La Romana', 'La Vega',
    'María Trinidad Sánchez', 'Monseñor Nouel', 'Monte Cristi', 'Monte Plata',
    'Pedernales', 'Peravia', 'Puerto Plata', 'Samaná', 'Sánchez Ramírez',
    'San Cristóbal', 'San José de Ocoa', 'San Juan', 'San Pedro de Macorís',
    'Santiago', 'Santiago Rodríguez', 'Santo Domingo', 'Valverde',
];
?>
<a class="admin-back-link" href="<?= e(url(['vista' => 'admin'])) ?>">← Volver a destinos</a>

<div class="admin-page-heading admin-form-heading">
    <div>
        <p class="admin-kicker">Administración del catálogo</p>
        <h1><?= $adminIsEdit ? 'Editar destino' : 'Agregar destino' ?></h1>
        <p><?= $adminIsEdit ? 'Actualiza la información y la fotografía de este lugar.' : 'Completa los datos del lugar que quieres agregar al catálogo.' ?></p>
    </div>
</div>

<form class="admin-editor" method="post" action="<?= e(url(['vista' => 'admin_guardar'])) ?>" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
    <input type="hidden" name="id" value="<?= e($values['id'] ?? '') ?>">

    <div class="admin-editor-content">
        <section class="admin-form-section" aria-labelledby="informacion-titulo">
            <div class="admin-form-section-heading">
                <span class="admin-section-number" aria-hidden="true">01</span>
                <div>
                    <h2 id="informacion-titulo">Información del destino</h2>
                    <p>Los campos con asterisco son obligatorios.</p>
                </div>
            </div>

            <div class="admin-field">
                <label for="nombre">Nombre del destino <span aria-hidden="true">*</span></label>
                <input id="nombre" name="nombre" type="text" value="<?= e($values['nombre'] ?? '') ?>" maxlength="150" required autofocus placeholder="Ej. Parque Duarte de Cotuí">
            </div>
            <div class="admin-field-grid">
                <div class="admin-field">
                    <label for="provincia">Provincia <span aria-hidden="true">*</span></label>
                    <input id="provincia" name="provincia" type="text" list="provincias" value="<?= e($values['provincia'] ?? '') ?>" maxlength="100" required placeholder="Ej. Sánchez Ramírez">
                    <datalist id="provincias">
                        <?php foreach ($adminProvinceOptions as $province): ?>
                            <option value="<?= e($province) ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div class="admin-field">
                    <label for="tipo">Tipo de lugar <span aria-hidden="true">*</span></label>
                    <select id="tipo" name="tipo" required>
                        <option value="">Selecciona un tipo</option>
                        <?php foreach (['Playa', 'Río', 'Montaña', 'Parque', 'Cultural'] as $type): ?>
                            <option value="<?= e($type) ?>" <?= ($values['tipo'] ?? '') === $type ? 'selected' : '' ?>><?= e($type) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="admin-field">
                <label for="descripcion">Descripción <span aria-hidden="true">*</span></label>
                <textarea id="descripcion" name="descripcion" rows="5" maxlength="5000" required placeholder="Describe el lugar y lo que se puede conocer allí."><?= e($values['descripcion'] ?? '') ?></textarea>
            </div>

            <details class="admin-details" <?= !empty($errors) ? 'open' : '' ?>>
                <summary class="admin-details-summary">
                    <span>Información adicional</span>
                    <span class="admin-optional">Opcional</span>
                </summary>
                <div class="admin-details-content">
                    <div class="admin-field-grid">
                        <div class="admin-field">
                            <label for="region">Región</label>
                            <input id="region" name="region" type="text" value="<?= e($values['region'] ?? '') ?>" maxlength="100" placeholder="Ej. Cibao">
                        </div>
                        <div class="admin-field">
                            <label for="destacado">Frase para la tarjeta</label>
                            <input id="destacado" name="destacado" type="text" value="<?= e($values['destacado'] ?? '') ?>" maxlength="150" placeholder="Ej. Un paseo por el corazón de Cotuí">
                        </div>
                    </div>
                    <div class="admin-field">
                        <label for="fuente_url">Fuente de la información</label>
                        <input id="fuente_url" name="fuente_url" type="url" value="<?= e($values['fuente_url'] ?? '') ?>" maxlength="500" placeholder="https://…">
                        <small>Enlace que respalda la información del destino.</small>
                    </div>
                </div>
            </details>
        </section>

        <section class="admin-form-section" aria-labelledby="ubicacion-titulo">
            <div class="admin-form-section-heading">
                <span class="admin-section-number" aria-hidden="true">02</span>
                <div>
                    <h2 id="ubicacion-titulo">Ubicación y rutas</h2>
                    <p>Añade las coordenadas para mostrar el lugar en el mapa.</p>
                </div>
            </div>
            <div class="admin-field">
                <label for="ubicacion">Dirección o referencia <span aria-hidden="true">*</span></label>
                <input id="ubicacion" name="ubicacion" type="text" value="<?= e($values['ubicacion'] ?? '') ?>" maxlength="255" required placeholder="Municipio, sector o referencia para llegar">
            </div>
            <div class="admin-field-grid">
                <div class="admin-field">
                    <label for="latitud">Latitud <span class="admin-optional">Opcional</span></label>
                    <input id="latitud" name="latitud" type="number" step="any" min="-90" max="90" value="<?= e($values['latitud'] ?? '') ?>" placeholder="Ej. 19.052">
                </div>
                <div class="admin-field">
                    <label for="longitud">Longitud <span class="admin-optional">Opcional</span></label>
                    <input id="longitud" name="longitud" type="number" step="any" min="-180" max="180" value="<?= e($values['longitud'] ?? '') ?>" placeholder="Ej. -70.150">
                </div>
            </div>
            <p class="admin-field-note">Completa ambas coordenadas o deja las dos vacías. Puedes obtenerlas al seleccionar el lugar en Google Maps.</p>
        </section>

        <section class="admin-form-section" aria-labelledby="foto-titulo">
            <div class="admin-form-section-heading">
                <span class="admin-section-number" aria-hidden="true">03</span>
                <div>
                    <h2 id="foto-titulo">Fotografía</h2>
                    <p>Sube una fotografía real del destino.</p>
                </div>
            </div>
            <div class="admin-photo-preview <?= $adminImage !== '' ? 'has-photo' : '' ?>" id="foto-preview">
                <img id="foto-preview-imagen" <?= $adminImage !== '' ? 'src="' . e(asset_url($adminImage)) . '"' : 'hidden' ?> alt="<?= e($values['imagen_alt'] ?? 'Vista previa de la fotografía') ?>">
                <div id="foto-preview-vacio" <?= $adminImage !== '' ? 'hidden' : '' ?>>
                    <span aria-hidden="true">＋</span>
                    <strong>Vista previa de la fotografía</strong>
                    <span>Selecciona una imagen del lugar</span>
                </div>
                <span class="admin-preview-tag">Vista previa</span>
            </div>
            <div class="admin-field">
                <label for="foto"><?= $adminImage !== '' ? 'Cambiar fotografía' : 'Subir fotografía' ?></label>
                <input id="foto" name="foto" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" aria-describedby="foto-ayuda foto-estado">
                <small id="foto-ayuda">JPG, PNG o WebP. Tamaño máximo: 5 MB. <?= $adminImage !== '' ? 'Si no seleccionas otra imagen, se conserva la actual.' : 'Usa una foto propia o con permiso de uso.' ?></small>
                <small id="foto-estado" role="status"></small>
            </div>
            <div class="admin-field">
                <label for="imagen_alt">Descripción de la fotografía <span class="admin-optional">Opcional</span></label>
                <input id="imagen_alt" name="imagen_alt" type="text" value="<?= e($values['imagen_alt'] ?? '') ?>" maxlength="255" placeholder="Ej. Vista del parque y su glorieta">
                <small>Describe lo que se ve para quienes usan un lector de pantalla.</small>
            </div>

            <details class="admin-details" <?= !empty($errors) ? 'open' : '' ?>>
                <summary class="admin-details-summary">
                    <span>Créditos y permiso de la fotografía</span>
                    <span class="admin-optional">Opcional</span>
                </summary>
                <div class="admin-details-content">
                    <div class="admin-field-grid">
                        <div class="admin-field">
                            <label for="foto_autor">Autor de la fotografía</label>
                            <input id="foto_autor" name="foto_autor" type="text" value="<?= e($values['foto_autor'] ?? '') ?>" maxlength="150" placeholder="Nombre del fotógrafo">
                        </div>
                        <div class="admin-field">
                            <label for="foto_licencia">Licencia o permiso</label>
                            <input id="foto_licencia" name="foto_licencia" type="text" value="<?= e($values['foto_licencia'] ?? '') ?>" maxlength="100" placeholder="Ej. Foto propia o CC BY-SA 4.0">
                        </div>
                    </div>
                    <div class="admin-field-grid">
                        <div class="admin-field">
                            <label for="foto_autor_url">Enlace del autor</label>
                            <input id="foto_autor_url" name="foto_autor_url" type="url" value="<?= e($values['foto_autor_url'] ?? '') ?>" maxlength="500" placeholder="https://…">
                        </div>
                        <div class="admin-field">
                            <label for="foto_licencia_url">Enlace de la licencia</label>
                            <input id="foto_licencia_url" name="foto_licencia_url" type="url" value="<?= e($values['foto_licencia_url'] ?? '') ?>" maxlength="500" placeholder="https://…">
                        </div>
                    </div>
                    <div class="admin-field">
                        <label for="foto_fuente">Enlace de la fotografía</label>
                        <input id="foto_fuente" name="foto_fuente" type="url" value="<?= e($values['foto_fuente'] ?? '') ?>" maxlength="500" placeholder="https://…">
                        <small>Incluye la página de origen si la fotografía procede de otra fuente.</small>
                    </div>
                    <div class="admin-field">
                        <label for="foto_fuente_label">Nombre de la fuente</label>
                        <input id="foto_fuente_label" name="foto_fuente_label" type="text" value="<?= e($values['foto_fuente_label'] ?? '') ?>" maxlength="100" placeholder="Ej. Wikimedia Commons">
                    </div>
                </div>
            </details>
        </section>
    </div>

    <aside class="admin-publish-panel" aria-labelledby="publicacion-titulo">
        <p class="admin-kicker">Estado del destino</p>
        <h2 id="publicacion-titulo">Publicación</h2>
        <p>Elige si el destino estará visible para los visitantes.</p>
        <input type="hidden" name="activo" value="0">
        <label class="admin-toggle" for="activo">
            <input id="activo" name="activo" type="checkbox" value="1" <?= (int) ($values['activo'] ?? 1) === 1 ? 'checked' : '' ?>>
            <span>
                <strong>Publicado en la página</strong>
                <small>Al desmarcar esta opción, el lugar queda oculto en el catálogo y en el mapa.</small>
            </span>
        </label>
        <button class="admin-button admin-button-primary admin-button-full" type="submit">
            <?= $adminIsEdit ? 'Guardar cambios' : 'Crear destino' ?>
            <span aria-hidden="true">→</span>
        </button>
        <a class="admin-button admin-button-secondary admin-button-full" href="<?= e(url(['vista' => 'admin'])) ?>">Cancelar</a>
        <p class="admin-publish-note">Los cambios se aplican al guardar.</p>
    </aside>
</form>
