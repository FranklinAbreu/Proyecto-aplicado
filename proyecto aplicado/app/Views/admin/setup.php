<section class="admin-auth-card" aria-labelledby="admin-setup-title">
    <p class="admin-kicker">Configuración inicial</p>
    <h1 id="admin-setup-title">Crear administrador</h1>
    <p class="admin-auth-intro">Define el usuario y la contraseña para administrar los destinos.</p>
    <?php if ($dbAvailable && $canSetup): ?>
        <form class="admin-auth-form" method="post" action="<?= e(url(['vista' => 'admin_setup'])) ?>">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <div class="admin-field">
                <label for="usuario">Nombre de usuario</label>
                <input id="usuario" name="usuario" type="text" value="<?= e($values['usuario'] ?? '') ?>" minlength="3" maxlength="40" autocomplete="username" autocapitalize="none" spellcheck="false" required autofocus aria-describedby="usuario-ayuda">
                <small id="usuario-ayuda">De 3 a 40 caracteres: letras sin tilde, números, punto, guion o guion bajo.</small>
            </div>
            <div class="admin-field">
                <label for="clave">Contraseña</label>
                <input id="clave" name="clave" type="password" minlength="10" maxlength="72" autocomplete="new-password" required aria-describedby="clave-ayuda">
                <small id="clave-ayuda">Mínimo 10 caracteres y máximo 72 bytes. Las letras con tilde y algunos símbolos ocupan más de un byte.</small>
            </div>
            <div class="admin-field">
                <label for="confirmar_clave">Repite la contraseña</label>
                <input id="confirmar_clave" name="confirmar_clave" type="password" minlength="10" maxlength="72" autocomplete="new-password" required>
            </div>
            <button class="admin-button admin-button-primary admin-button-full" type="submit">Crear administrador <span aria-hidden="true">→</span></button>
        </form>
        <p class="admin-auth-note">La primera cuenta se crea desde este equipo. Guarda tus credenciales para entrar al panel.</p>
    <?php else: ?>
        <div class="admin-inline-note">
            <p><?= !$dbAvailable ? 'Para comenzar, activa MySQL en XAMPP y prepara la base de datos del proyecto.' : 'La configuración inicial solo está disponible desde este equipo y cuando aún no existe un administrador.' ?></p>
        </div>
        <a class="admin-button admin-button-primary admin-button-full" href="<?= e(url(['vista' => 'admin_login'])) ?>">Ir al acceso</a>
    <?php endif; ?>
    <a class="admin-auth-back" href="<?= e(url()) ?>">← Volver a la página</a>
</section>
