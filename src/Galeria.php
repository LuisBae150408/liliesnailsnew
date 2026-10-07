<?php
/**
 * src/Galeria.php
 * Fotos de diseños de uñas mostradas en el sitio público.
 */

declare(strict_types=1);

class Galeria
{
    public static function listarActivas(): array
    {
        $stmt = Database::conexion()->query(
            'SELECT * FROM galeria WHERE activo = 1 ORDER BY orden ASC, id DESC'
        );
        return $stmt->fetchAll();
    }

    public static function listarTodas(): array
    {
        $stmt = Database::conexion()->query('SELECT * FROM galeria ORDER BY orden ASC, id DESC');
        return $stmt->fetchAll();
    }

    public static function crear(string $titulo, string $imagen, string $descripcion = '', int $orden = 0): int
    {
        $stmt = Database::conexion()->prepare(
            'INSERT INTO galeria (titulo, imagen, descripcion, orden) VALUES (:titulo, :imagen, :descripcion, :orden)'
        );
        $stmt->execute(['titulo' => $titulo, 'imagen' => $imagen, 'descripcion' => $descripcion, 'orden' => $orden]);
        return (int) Database::conexion()->lastInsertId();
    }

    public static function eliminar(int $id): bool
    {
        $stmt = Database::conexion()->prepare('DELETE FROM galeria WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}
