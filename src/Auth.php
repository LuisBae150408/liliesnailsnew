<?php
/**
 * src/Auth.php
 * -----------------------------------------------------------------
 * Autenticación simple basada en sesión para proteger el panel
 * administrativo y la agenda (ambos quedarán expuestos en internet
 * al conectarse a Aiven, así que necesitan estar protegidos).
 * -----------------------------------------------------------------
 */

declare(strict_types=1);

class Auth
{
    private const MAX_INTENTOS = 5;
    private const MINUTOS_BLOQUEO = 10;

    public static function intentarLogin(string $usuario, string $password): bool
    {
        if (self::estaBloqueado()) {
            return false;
        }

        $pdo = Database::conexion();
        $stmt = $pdo->prepare(
            'SELECT id, nombre, usuario, password_hash FROM usuarios
             WHERE usuario = :usuario AND activo = 1 LIMIT 1'
        );
        $stmt->execute(['usuario' => $usuario]);
        $fila = $stmt->fetch();

        if (!$fila || !password_verify($password, $fila['password_hash'])) {
            self::registrarIntentoFallido();
            return false;
        }

        // Login correcto: renueva el id de sesión (evita fijación de sesión)
        session_regenerate_id(true);
        $_SESSION['usuario_id'] = (int) $fila['id'];
        $_SESSION['usuario_nombre'] = $fila['nombre'];
        unset($_SESSION['intentos_fallidos'], $_SESSION['bloqueado_hasta']);

        $upd = $pdo->prepare('UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = :id');
        $upd->execute(['id' => $fila['id']]);

        return true;
    }

    private static function estaBloqueado(): bool
    {
        if (empty($_SESSION['bloqueado_hasta'])) {
            return false;
        }
        if (time() < $_SESSION['bloqueado_hasta']) {
            return true;
        }
        unset($_SESSION['bloqueado_hasta'], $_SESSION['intentos_fallidos']);
        return false;
    }

    private static function registrarIntentoFallido(): void
    {
        $_SESSION['intentos_fallidos'] = ($_SESSION['intentos_fallidos'] ?? 0) + 1;
        if ($_SESSION['intentos_fallidos'] >= self::MAX_INTENTOS) {
            $_SESSION['bloqueado_hasta'] = time() + self::MINUTOS_BLOQUEO * 60;
        }
    }

    public static function estaAutenticado(): bool
    {
        return !empty($_SESSION['usuario_id']);
    }

    public static function nombreUsuario(): string
    {
        return $_SESSION['usuario_nombre'] ?? '';
    }

    public static function cerrarSesion(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $parametros = session_get_cookie_params();
            setcookie('PHPSESSID', '', time() - 42000, $parametros['path'], $parametros['domain'], $parametros['secure'], $parametros['httponly']);
        }
        session_destroy();
    }

    public static function usuarioId(): int
    {
        return (int) ($_SESSION['usuario_id'] ?? 0);
    }

    /**
     * Cambia la contraseña del usuario autenticado.
     * @return string  Cadena vacía si todo salió bien, o un mensaje de error.
     */
    public static function cambiarPassword(string $actual, string $nueva, string $confirmacion): string
    {
        if (strlen($nueva) < 8) {
            return 'La nueva contraseña debe tener al menos 8 caracteres.';
        }
        if ($nueva !== $confirmacion) {
            return 'La confirmación no coincide con la nueva contraseña.';
        }

        $pdo = Database::conexion();
        $stmt = $pdo->prepare('SELECT password_hash FROM usuarios WHERE id = :id');
        $stmt->execute(['id' => self::usuarioId()]);
        $fila = $stmt->fetch();

        if (!$fila || !password_verify($actual, $fila['password_hash'])) {
            return 'Tu contraseña actual no es correcta.';
        }

        $nuevoHash = password_hash($nueva, PASSWORD_DEFAULT);
        $upd = $pdo->prepare('UPDATE usuarios SET password_hash = :hash WHERE id = :id');
        $upd->execute(['hash' => $nuevoHash, 'id' => self::usuarioId()]);

        return '';
    }

    /**
     * Llamar al inicio de cualquier página protegida del panel.
     * Redirige a login.php si no hay sesión activa.
     */
    public static function requerirLogin(): void
    {
        if (!self::estaAutenticado()) {
            header('Location: login.php');
            exit;
        }
    }
}
