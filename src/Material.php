<?php
/**
 * src/Material.php
 * CRUD del inventario de materiales.
 */

declare(strict_types=1);

class Material
{
    public static function listarTodos(string $filtro = ''): array
    {
        $pdo = Database::conexion();
        if ($filtro !== '') {
            $stmt = $pdo->prepare(
                'SELECT id, nombre, precio, rinde, costo_uso
                 FROM materiales WHERE nombre LIKE :filtro ORDER BY nombre ASC'
            );
            $stmt->execute(['filtro' => '%' . $filtro . '%']);
        } else {
            $stmt = $pdo->query(
                'SELECT id, nombre, precio, rinde, costo_uso FROM materiales ORDER BY nombre ASC'
            );
        }
        return $stmt->fetchAll();
    }

    public static function obtener(int $id): ?array
    {
        $stmt = Database::conexion()->prepare(
            'SELECT id, nombre, precio, rinde, costo_uso FROM materiales WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    public static function crear(string $nombre, float $precio, int $rinde): int
    {
        $stmt = Database::conexion()->prepare(
            'INSERT INTO materiales (nombre, precio, rinde) VALUES (:nombre, :precio, :rinde)'
        );
        $stmt->execute(['nombre' => $nombre, 'precio' => $precio, 'rinde' => $rinde]);
        return (int) Database::conexion()->lastInsertId();
    }

    public static function actualizar(int $id, string $nombre, float $precio, int $rinde): bool
    {
        $stmt = Database::conexion()->prepare(
            'UPDATE materiales SET nombre = :nombre, precio = :precio, rinde = :rinde WHERE id = :id'
        );
        return $stmt->execute(['nombre' => $nombre, 'precio' => $precio, 'rinde' => $rinde, 'id' => $id]);
    }

    public static function eliminar(int $id): bool
    {
        $stmt = Database::conexion()->prepare('DELETE FROM materiales WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}
