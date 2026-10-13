<section class="admin-auth-card" aria-labelledby="admin-login-title">
    <p class="admin-kicker">Panel de CercaRD</p>
    <h1 id="admin-login-title">Acceso de administración</h1>
    <p class="admin-auth-intro">Ingresa con tu usuario y contraseña.</p>
    <?php if ($dbAvailable): ?>
        <form class="admin-auth-form" method="post" action="<?= e(url(['vista' => 'admin_login'])) ?>">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <div class="admin-field">
                <label for="usuario">Nombre de usuario</label>
                <input id="usuario" name="usuario" type="text" value="<?= e($values['usuario'] ?? '') ?>" maxlength="40" autocomplete="username" autocapitalize="none" spellcheck="false" required autofocus>
            </div>
            <div class="admin-field">
                <label for="clave">Contraseña</label>
                <input id="clave" name="clave" type="password" maxlength="72" autocomplete="current-password" required>
            </div>
            <button class="admin-button admin-button-primary admin-button-full" type="submit">Entrar al panel <span aria-hidden="true">→</span></button>
        </form>
        <?php if ($canSetup): ?><p class="admin-auth-note">Aún no hay una cuenta. <a href="<?= e(url(['vista' => 'admin_setup'])) ?>">Crear administrador</a>.</p><?php endif; ?>
    <?php else: ?>
        <div class="admin-inline-note"><p>Activa MySQL en XAMPP y prepara la base de datos para acceder al panel.</p></div>
        <a class="admin-button admin-button-secondary admin-button-full" href="<?= e(url(['vista' => 'admin_login'])) ?>">Intentar de nuevo</a>
    <?php endif; ?>
    <a class="admin-auth-back" href="<?= e(url()) ?>">← Volver a la página</a>
</section>
