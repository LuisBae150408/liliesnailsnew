<?php
/**
 * includes/bootstrap.php
 * Punto de entrada común: carga configuración, conexión y modelos.
 * Cada página en /public/ debe empezar con:
 *   require __DIR__ . '/../includes/bootstrap.php';
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Material.php';
require_once __DIR__ . '/../src/Servicio.php';
require_once __DIR__ . '/../src/Clienta.php';
require_once __DIR__ . '/../src/Cita.php';
require_once __DIR__ . '/../src/Galeria.php';
require_once __DIR__ . '/../src/ImagenUtil.php';
require_once __DIR__ . '/../src/OneSignalPush.php';

/** Helper corto para imprimir texto escapado en las vistas. */
function h(?string $texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

/** Construye el link de wa.me con el número y el mensaje predeterminado de agendamiento. */
function whatsappUrl(string $telefono, string $mensaje = '¡Hola!, quiero agendar una cita,'): string
{
    $telLimpio = preg_replace('/\D/', '', $telefono);
    return 'https://wa.me/' . $telLimpio . '?text=' . rawurlencode($mensaje);
}

/** Helper para leer un valor de sitio_config con valor por defecto. */
function configSitio(string $clave, string $porDefecto = ''): string
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        $stmt = Database::conexion()->query('SELECT clave, valor FROM sitio_config');
        foreach ($stmt->fetchAll() as $fila) {
            $cache[$fila['clave']] = $fila['valor'];
        }
    }
    return $cache[$clave] ?? $porDefecto;
}

/** Guarda (o actualiza) un valor en sitio_config. */
function guardarConfigSitio(string $clave, string $valor): void
{
    $stmt = Database::conexion()->prepare(
        'INSERT INTO sitio_config (clave, valor) VALUES (:clave, :valor)
         ON DUPLICATE KEY UPDATE valor = :valor2'
    );
    $stmt->execute(['clave' => $clave, 'valor' => $valor, 'valor2' => $valor]);
}
