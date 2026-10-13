<?php

declare(strict_types=1);

final class AdminAuth
{
    public function __construct(private PDO $connection)
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            ini_set('session.use_strict_mode', '1');
            session_name('cercard_admin');
            session_set_cookie_params([
                'lifetime' => 0, 'path' => base_url() ?: '/',
                'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                'httponly' => true, 'samesite' => 'Lax',
            ]);
            session_start();
        }
    }

    public function hasAdministrators(): bool
    {
        return (int) $this->connection->query('SELECT COUNT(*) FROM administradores')->fetchColumn() > 0;
    }

    public function canSetup(): bool
    {
        return in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true) && !$this->hasAdministrators();
    }

    public function token(): string
    {
        if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public function verifyToken($token): bool
    {
        return is_string($token) && isset($_SESSION['csrf_token'])
            && hash_equals($_SESSION['csrf_token'], $token);
    }

    public function currentUser(): ?array
    {
        $id = $_SESSION['admin_id'] ?? null;
        if (!is_int($id) || time() - (int) ($_SESSION['admin_last_active'] ?? 0) > 7200) {
            unset($_SESSION['admin_id'], $_SESSION['admin_last_active']);
            return null;
        }
        $statement = $this->connection->prepare('SELECT id, usuario FROM administradores WHERE id = ?');
        $statement->execute([$id]);
        $user = $statement->fetch(PDO::FETCH_ASSOC);
        if ($user === false) {
            unset($_SESSION['admin_id'], $_SESSION['admin_last_active']);
            return null;
        }
        $_SESSION['admin_last_active'] = time();
        return $user;
    }

    public function setup(string $username, string $password): void
    {
        if (!$this->canSetup()) {
            throw new InvalidArgumentException('La cuenta inicial ya existe o la configuración no está disponible desde este equipo.');
        }
        if (!preg_match('/\A[a-zA-Z0-9_.-]{3,40}\z/D', $username)) {
            throw new InvalidArgumentException('Usa un nombre de usuario de 3 a 40 letras, números, puntos o guiones.');
        }
        if (str_contains($password, "\0") || mb_strlen($password, 'UTF-8') < 10 || strlen($password) > 72) {
            throw new InvalidArgumentException('La contraseña debe tener al menos 10 caracteres y como máximo 72 bytes.');
        }
        $statement = $this->connection->prepare('INSERT INTO administradores (id, usuario, clave_hash) VALUES (1, ?, ?)');
        try {
            // El ID fijo garantiza que dos configuraciones simultáneas no creen dos cuentas.
            $statement->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                throw new InvalidArgumentException('La cuenta inicial ya fue creada. Entra con tu usuario y contraseña.');
            }
            throw $exception;
        }
        $this->authenticate(1);
    }

    public function login(string $username, string $password): bool
    {
        if (time() < (int) ($_SESSION['login_blocked_until'] ?? 0)) {
            throw new InvalidArgumentException('Espera un minuto antes de volver a intentar el acceso.');
        }
        $statement = $this->connection->prepare('SELECT id, clave_hash FROM administradores WHERE usuario = ?');
        $statement->execute([$username]);
        $user = $statement->fetch(PDO::FETCH_ASSOC);
        $hash = $user === false ? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi' : $user['clave_hash'];
        $valid = strlen($password) <= 72 && !str_contains($password, "\0") && password_verify($password, $hash);
        if ($user === false || !$valid) {
            $_SESSION['login_failures'] = (int) ($_SESSION['login_failures'] ?? 0) + 1;
            if ($_SESSION['login_failures'] >= 5) {
                $_SESSION['login_blocked_until'] = time() + 60;
                $_SESSION['login_failures'] = 0;
            }
            return false;
        }
        $this->authenticate((int) $user['id']);
        return true;
    }

    private function authenticate(int $id): void
    {
        session_regenerate_id(true);
        $_SESSION = ['admin_id' => $id, 'admin_last_active' => time(), 'csrf_token' => bin2hex(random_bytes(32))];
    }

    public function logout(): void
    {
        $_SESSION = [];
        session_regenerate_id(true);
    }
}
