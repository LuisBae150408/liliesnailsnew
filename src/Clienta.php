<?php
/**
 * src/Clienta.php
 * CRUD de clientas registradas en la agenda.
 */

declare(strict_types=1);

class Clienta
{
    public static function listarTodas(string $filtro = ''): array
    {
        $pdo = Database::conexion();
        if ($filtro !== '') {
            $stmt = $pdo->prepare(
                'SELECT * FROM clientas WHERE nombre LIKE :f OR telefono LIKE :f ORDER BY nombre ASC'
            );
            $stmt->execute(['f' => '%' . $filtro . '%']);
        } else {
            $stmt = $pdo->query('SELECT * FROM clientas ORDER BY nombre ASC');
        }
        return $stmt->fetchAll();
    }

    public static function obtener(int $id): ?array
    {
        $stmt = Database::conexion()->prepare('SELECT * FROM clientas WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    /**
     * Busca una clienta existente por teléfono; si no existe, la crea.
     * Útil para no duplicar clientas al agendar citas rápido.
     */
    public static function buscarOCrear(string $nombre, string $telefono, string $notas = ''): int
    {
        $pdo = Database::conexion();
        if ($telefono !== '') {
            $stmt = $pdo->prepare('SELECT id FROM clientas WHERE telefono = :tel LIMIT 1');
            $stmt->execute(['tel' => $telefono]);
            $existente = $stmt->fetch();
            if ($existente) {
                return (int) $existente['id'];
            }
        }
        return self::crear($nombre, $telefono, $notas);
    }

    public static function crear(string $nombre, string $telefono, string $notas = ''): int
    {
        $stmt = Database::conexion()->prepare(
            'INSERT INTO clientas (nombre, telefono, notas) VALUES (:nombre, :telefono, :notas)'
        );
        $stmt->execute(['nombre' => $nombre, 'telefono' => $telefono, 'notas' => $notas]);
        return (int) Database::conexion()->lastInsertId();
    }

    public static function actualizar(int $id, string $nombre, string $telefono, string $notas = ''): bool
    {
        $stmt = Database::conexion()->prepare(
            'UPDATE clientas SET nombre = :nombre, telefono = :telefono, notas = :notas WHERE id = :id'
        );
        return $stmt->execute(['nombre' => $nombre, 'telefono' => $telefono, 'notas' => $notas, 'id' => $id]);
    }

    public static function eliminar(int $id): bool
    {
        $stmt = Database::conexion()->prepare('DELETE FROM clientas WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}
