<?php
/**
 * src/Database.php
 * -----------------------------------------------------------------
 * Envoltorio simple sobre PDO. Una sola conexión reutilizada
 * (patrón singleton) usando siempre sentencias preparadas.
 * -----------------------------------------------------------------
 */

declare(strict_types=1);

class Database
{
    private static ?PDO $instancia = null;

    public static function conexion(): PDO
    {
        if (self::$instancia !== null) {
            return self::$instancia;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            DB_HOST,
            DB_PORT,
            DB_NAME
        );

        $opciones = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        // Aiven exige TLS. Si se definió un certificado CA, lo usamos
        // y forzamos verificación; en local (sin certificado) se omite.
        if (DB_SSL_CA !== '' && is_file(DB_SSL_CA)) {
            $opciones[PDO::MYSQL_ATTR_SSL_CA] = DB_SSL_CA;
            $opciones[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
        }

        try {
            self::$instancia = new PDO($dsn, DB_USER, DB_PASSWORD, $opciones);
        } catch (PDOException $e) {
            // No exponemos detalles de conexión al usuario final
            error_log('Error de conexión a la base de datos: ' . $e->getMessage());
            http_response_code(500);
            die('No se pudo conectar a la base de datos. Intenta de nuevo en unos minutos.');
        }

        return self::$instancia;
    }
}
