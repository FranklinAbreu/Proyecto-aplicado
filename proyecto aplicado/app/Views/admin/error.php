<section class="admin-error-card" aria-labelledby="admin-error-title">
    <p class="admin-kicker">Administración CercaRD</p>
    <h1 id="admin-error-title"><?= e($title) ?></h1>
    <p><?= !$dbAvailable ? 'Activa MySQL en XAMPP y verifica la base de datos del proyecto para continuar.' : 'Regresa al panel o vuelve a abrir el formulario para continuar.' ?></p>
    <div class="admin-error-actions"><a class="admin-button admin-button-primary" href="<?= e(url(['vista' => $adminAuthenticated ? 'admin' : 'admin_login'])) ?>"><?= $adminAuthenticated ? 'Volver al panel' : 'Ir al acceso' ?></a><a class="admin-button admin-button-secondary" href="<?= e(url()) ?>">Ver página</a></div>
</section>
