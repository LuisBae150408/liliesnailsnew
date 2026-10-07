<?php
/**
 * src/Servicio.php
 * -----------------------------------------------------------------
 * CRUD de servicios + cálculo del precio final.
 *
 * Fórmula (idéntica a la app original en script.js):
 *   costoMateriales = suma de costo_uso de los materiales seleccionados
 *   costoBase       = costoMateriales + (gananciaHora / 60) * tiempoMinutos
 *   precioFinal     = costoBase + costoBase * (margenPorcentaje / 100)
 *
 * El precio NUNCA se guarda fijo en la base de datos: se recalcula
 * cada vez a partir del costo actual de los materiales, tal como
 * hacía obtenerPrecioFinalDinamico() en la versión original. Así,
 * si sube el precio de un esmalte, todos los servicios que lo usan
 * se actualizan solos.
 * -----------------------------------------------------------------
 */

declare(strict_types=1);

class Servicio
{
    public static function listarTodos(string $filtro = ''): array
    {
        $pdo = Database::conexion();
        if ($filtro !== '') {
            $stmt = $pdo->prepare('SELECT * FROM servicios WHERE nombre LIKE :filtro ORDER BY orden ASC, nombre ASC');
            $stmt->execute(['filtro' => '%' . $filtro . '%']);
        } else {
            $stmt = $pdo->query('SELECT * FROM servicios ORDER BY orden ASC, nombre ASC');
        }
        return self::adjuntarPreciosYMateriales($stmt->fetchAll());
    }

    public static function listarPublicos(): array
    {
        $stmt = Database::conexion()->query(
            'SELECT * FROM servicios WHERE visible_publico = 1 ORDER BY orden ASC, nombre ASC'
        );
        return self::adjuntarPreciosYMateriales($stmt->fetchAll());
    }

    /**
     * Le agrega "materiales_ids" y "precio_final" a una lista de servicios
     * en UNA sola consulta extra (antes se hacían 2 consultas POR CADA
     * servicio: N servicios = hasta 2N+1 consultas a la base de datos,
     * lo cual hacía lenta la carga del catálogo público y del panel
     * mientras más servicios se agregaban). Ahora siempre son 2 consultas
     * en total, sin importar cuántos servicios haya.
     */
    private static function adjuntarPreciosYMateriales(array $servicios): array
    {
        if (empty($servicios)) {
            return [];
        }

        $ids = array_map('intval', array_column($servicios, 'id'));
        $marcadores = implode(',', array_fill(0, count($ids), '?'));

        $stmt = Database::conexion()->prepare(
            "SELECT sm.servicio_id, sm.material_id, m.costo_uso
             FROM servicio_material sm
             JOIN materiales m ON m.id = sm.material_id
             WHERE sm.servicio_id IN ($marcadores)"
        );
        $stmt->execute($ids);

        $materialesPorServicio = [];
        $costoPorServicio = [];
        foreach ($stmt->fetchAll() as $fila) {
            $sid = (int) $fila['servicio_id'];
            $materialesPorServicio[$sid][] = (int) $fila['material_id'];
            $costoPorServicio[$sid] = ($costoPorServicio[$sid] ?? 0.0) + (float) $fila['costo_uso'];
        }

        foreach ($servicios as &$s) {
            $sid = (int) $s['id'];
            $s['materiales_ids'] = $materialesPorServicio[$sid] ?? [];
            $costoBase = ($costoPorServicio[$sid] ?? 0.0) + ((float) $s['ganancia_hora'] / 60) * (int) $s['tiempo_minutos'];
            $s['precio_final'] = round($costoBase + $costoBase * ((float) $s['margen_porcentaje'] / 100), 2);
        }
        unset($s);

        return $servicios;
    }

    public static function obtener(int $id): ?array
    {
        $stmt = Database::conexion()->prepare('SELECT * FROM servicios WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $fila = $stmt->fetch();
        if (!$fila) {
            return null;
        }
        $fila['materiales_ids'] = self::idsDeMateriales($id);
        $fila['precio_final'] = self::calcularPrecioFinal(
            (float) $fila['margen_porcentaje'],
            (float) $fila['ganancia_hora'],
            (int) $fila['tiempo_minutos'],
            $fila['materiales_ids']
        );
        return $fila;
    }

    private static function idsDeMateriales(int $servicioId): array
    {
        $stmt = Database::conexion()->prepare(
            'SELECT material_id FROM servicio_material WHERE servicio_id = :id'
        );
        $stmt->execute(['id' => $servicioId]);
        return array_map('intval', array_column($stmt->fetchAll(), 'material_id'));
    }

    public static function calcularPrecioFinal(
        float $margenPorcentaje,
        float $gananciaHora,
        int $tiempoMinutos,
        array $materialesIds
    ): float {
        $costoMateriales = 0.0;

        if (!empty($materialesIds)) {
            $marcadores = implode(',', array_fill(0, count($materialesIds), '?'));
            $stmt = Database::conexion()->prepare(
                "SELECT costo_uso FROM materiales WHERE id IN ($marcadores)"
            );
            $stmt->execute($materialesIds);
            foreach ($stmt->fetchAll() as $fila) {
                $costoMateriales += (float) $fila['costo_uso'];
            }
        }

        $costoBase = $costoMateriales + ($gananciaHora / 60) * $tiempoMinutos;
        $precioFinal = $costoBase + $costoBase * ($margenPorcentaje / 100);

        return round($precioFinal, 2);
    }

    public static function crear(
        string $nombre,
        float $margenPorcentaje,
        float $gananciaHora,
        int $tiempoMinutos,
        array $materialesIds,
        bool $visiblePublico = false,
        string $descripcionPublica = '',
        ?string $imagen = null
    ): int {
        $pdo = Database::conexion();
        $pdo->beginTransaction();
        try {
            $siguienteOrden = (int) $pdo->query('SELECT COALESCE(MAX(orden), 0) + 1 FROM servicios')->fetchColumn();
            $stmt = $pdo->prepare(
                'INSERT INTO servicios (nombre, margen_porcentaje, ganancia_hora, tiempo_minutos, visible_publico, descripcion_publica, imagen, orden)
                 VALUES (:nombre, :margen, :ghora, :tiempo, :visible, :descripcion, :imagen, :orden)'
            );
            $stmt->execute([
                'nombre'      => $nombre,
                'margen'      => $margenPorcentaje,
                'ghora'       => $gananciaHora,
                'tiempo'      => $tiempoMinutos,
                'visible'     => $visiblePublico ? 1 : 0,
                'descripcion' => $descripcionPublica,
                'imagen'      => $imagen,
                'orden'       => $siguienteOrden,
            ]);
            $id = (int) $pdo->lastInsertId();
            self::sincronizarMateriales($id, $materialesIds);
            $pdo->commit();
            return $id;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function actualizar(
        int $id,
        string $nombre,
        float $margenPorcentaje,
        float $gananciaHora,
        int $tiempoMinutos,
        array $materialesIds,
        bool $visiblePublico = false,
        string $descripcionPublica = '',
        ?string $imagen = null
    ): bool {
        $pdo = Database::conexion();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'UPDATE servicios SET nombre = :nombre, margen_porcentaje = :margen,
                 ganancia_hora = :ghora, tiempo_minutos = :tiempo, visible_publico = :visible,
                 descripcion_publica = :descripcion, imagen = COALESCE(:imagen, imagen)
                 WHERE id = :id'
            );
            $stmt->execute([
                'nombre'      => $nombre,
                'margen'      => $margenPorcentaje,
                'ghora'       => $gananciaHora,
                'tiempo'      => $tiempoMinutos,
                'visible'     => $visiblePublico ? 1 : 0,
                'descripcion' => $descripcionPublica,
                'imagen'      => $imagen,
                'id'          => $id,
            ]);
            self::sincronizarMateriales($id, $materialesIds);
            $pdo->commit();
            return true;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    private static function sincronizarMateriales(int $servicioId, array $materialesIds): void
    {
        $pdo = Database::conexion();
        $del = $pdo->prepare('DELETE FROM servicio_material WHERE servicio_id = :id');
        $del->execute(['id' => $servicioId]);

        if (empty($materialesIds)) {
            return;
        }
        $ins = $pdo->prepare(
            'INSERT INTO servicio_material (servicio_id, material_id) VALUES (:sid, :mid)'
        );
        foreach (array_unique(array_map('intval', $materialesIds)) as $materialId) {
            $ins->execute(['sid' => $servicioId, 'mid' => $materialId]);
        }
    }

    public static function eliminar(int $id): bool
    {
        $stmt = Database::conexion()->prepare('DELETE FROM servicios WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    /** Guarda el nombre del archivo de imagen para un servicio (ya subido a assets/img/servicios/). */
    public static function actualizarImagen(int $id, string $nombreArchivo): bool
    {
        $stmt = Database::conexion()->prepare('UPDATE servicios SET imagen = :imagen WHERE id = :id');
        return $stmt->execute(['imagen' => $nombreArchivo, 'id' => $id]);
    }

    /** Quita la imagen asociada a un servicio (deja el campo en NULL). */
    public static function quitarImagen(int $id): bool
    {
        $stmt = Database::conexion()->prepare('UPDATE servicios SET imagen = NULL WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Mueve un servicio un lugar hacia arriba o hacia abajo en el orden
     * del catálogo, intercambiando su "orden" con el del vecino inmediato.
     */
    public static function moverOrden(int $id, string $direccion): bool
    {
        $pdo = Database::conexion();
        $actual = $pdo->prepare('SELECT id, orden FROM servicios WHERE id = :id');
        $actual->execute(['id' => $id]);
        $filaActual = $actual->fetch();
        if (!$filaActual) {
            return false;
        }

        if ($direccion === 'arriba') {
            $vecino = $pdo->prepare(
                'SELECT id, orden FROM servicios WHERE orden < :orden ORDER BY orden DESC LIMIT 1'
            );
        } else {
            $vecino = $pdo->prepare(
                'SELECT id, orden FROM servicios WHERE orden > :orden ORDER BY orden ASC LIMIT 1'
            );
        }
        $vecino->execute(['orden' => $filaActual['orden']]);
        $filaVecino = $vecino->fetch();
        if (!$filaVecino) {
            return false; // ya está en el extremo, no hay a dónde moverlo
        }

        $pdo->beginTransaction();
        try {
            $upd = $pdo->prepare('UPDATE servicios SET orden = :orden WHERE id = :id');
            $upd->execute(['orden' => $filaVecino['orden'], 'id' => $filaActual['id']]);
            $upd->execute(['orden' => $filaActual['orden'], 'id' => $filaVecino['id']]);
            $pdo->commit();
            return true;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
