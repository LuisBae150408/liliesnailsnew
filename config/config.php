<?php
/**
 * config/config.php
 * -----------------------------------------------------------------
 * Carga las variables de entorno (.env) y expone la configuración
 * de la aplicación. No contiene credenciales: todo viene del .env,
 * que no se sube al repositorio (ver .gitignore).
 * -----------------------------------------------------------------
 */

declare(strict_types=1);

// -----------------------------------------------------------------
// 1) Mini-cargador de .env (sin dependencias externas / sin Composer)
// -----------------------------------------------------------------
function cargarEnv(string $rutaArchivo): void
{
    if (!is_file($rutaArchivo)) {
        return;
    }

    $lineas = file($rutaArchivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lineas as $linea) {
        $linea = trim($linea);
        if ($linea === '' || str_starts_with($linea, '#')) {
            continue;
        }
        if (!str_contains($linea, '=')) {
            continue;
        }
        [$clave, $valor] = explode('=', $linea, 2);
        $clave = trim($clave);
        $valor = trim($valor);
        // Quita comillas envolventes si las hay
        $valor = trim($valor, "\"'");

        if ($clave !== '' && getenv($clave) === false) {
            putenv("{$clave}={$valor}");
            $_ENV[$clave] = $valor;
        }
    }
}

cargarEnv(__DIR__ . '/../.env');

/**
 * Helper para leer variables de entorno con valor por defecto.
 */
function env(string $clave, ?string $porDefecto = null): ?string
{
    $valor = getenv($clave);
    return $valor === false ? $porDefecto : $valor;
}

// -----------------------------------------------------------------
// 2) Configuración general de la aplicación
// -----------------------------------------------------------------
define('APP_ENV', env('APP_ENV', 'local'));
define('APP_SECRET', env('APP_SECRET', ''));
define('APP_NOMBRE', 'LiliesNails');

// Zona horaria del negocio (ajusta si hace falta)
date_default_timezone_set('America/Caracas');

// Muestra errores solo en local; en producción se ocultan del usuario
if (APP_ENV === 'local') {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL);
}

// -----------------------------------------------------------------
// 3) Datos de conexión a MySQL (Aiven)
// -----------------------------------------------------------------
define('DB_HOST', env('DB_HOST', 'sql202.infinityfree.com'));
define('DB_PORT', env('DB_PORT', '3306'));
define('DB_NAME', env('DB_NAME', 'if0_42627611_liliesnails'));
define('DB_USER', env('DB_USER', 'if0_42627611'));
define('DB_PASSWORD', env('DB_PASSWORD', '4bIS6vxZUVQdSK'));
$rutaSslCa = env('DB_SSL_CA', '');
if ($rutaSslCa !== '' && !str_starts_with($rutaSslCa, '/') && !preg_match('/^[A-Za-z]:\\\\/', $rutaSslCa)) {
    // Ruta relativa (ej: "config/certs/ca.pem") -> se resuelve contra la raíz del proyecto,
    // no contra el directorio del script que se está ejecutando (public/index.php).
    $rutaSslCa = __DIR__ . '/../' . $rutaSslCa;
}
define('DB_SSL_CA', $rutaSslCa);

// -----------------------------------------------------------------
// 4) Notificaciones push (OneSignal) — opcional
// -----------------------------------------------------------------
// Si dejas estas dos vacías, el sistema de recordatorios simplemente
// se queda apagado (no rompe nada del resto de la app).
define('ONESIGNAL_APP_ID', env('ONESIGNAL_APP_ID', ''));
define('ONESIGNAL_API_KEY', env('ONESIGNAL_API_KEY', ''));

// Sesiones seguras (cookie de sesión más difícil de robar)
if (session_status() === PHP_SESSION_NONE) {
    $conexionSegura = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        || (($_SERVER['SERVER_PORT'] ?? '') === '443');

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => $conexionSegura, // solo exige HTTPS si la conexión actual realmente es HTTPS
    ]);
    session_start();
}
