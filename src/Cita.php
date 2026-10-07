<?php
/**
 * src/Cita.php
 * CRUD de citas de la agenda + consultas por rango de fechas
 * (para las vistas de lista diaria/semanal y calendario mensual),
 * el recordatorio (notificación push) de cada cita, y el auto-completado
 * de citas cuya hora de fin ya pasó.
 */

declare(strict_types=1);

class Cita
{
    private const SELECT_BASE = <<<SQL
        SELECT c.id, c.fecha, c.hora_inicio, c.hora_fin, c.estado, c.notas,
               c.recordatorio_minutos, c.notificacion_id,
               c.clienta_id, cl.nombre AS clienta_nombre, cl.telefono AS clienta_telefono
        FROM citas c
        JOIN clientas cl ON cl.id = c.clienta_id
        SQL;

    public static function listarPorRango(string $desde, string $hasta): array
    {
        self::autocompletarVencidas();

        $stmt = Database::conexion()->prepare(
            self::SELECT_BASE . ' WHERE c.fecha BETWEEN :desde AND :hasta
             ORDER BY c.fecha ASC, c.hora_inicio ASC'
        );
        $stmt->execute(['desde' => $desde, 'hasta' => $hasta]);
        return self::adjuntarServicios($stmt->fetchAll());
    }

    public static function listarPorDia(string $fecha): array
    {
        return self::listarPorRango($fecha, $fecha);
    }

    /**
     * Citas entre ahora y "$minutos" hacia adelante, sin contar canceladas.
     * Se usa para el aviso dentro del panel al abrirlo.
     */
    public static function listarProximas(int $minutos): array
    {
        // Primero se acota por "c.fecha" (columna indexada, sin envolverla
        // en TIMESTAMP()) para que MySQL pueda usar idx_citas_fecha y no
        // tenga que revisar cada cita de la tabla; el filtro exacto por
        // hora se aplica después, ya sobre un puñado de filas.
        $stmt = Database::conexion()->prepare(
            self::SELECT_BASE . " WHERE c.estado != 'cancelada'
             AND c.fecha BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 1 DAY)
             AND TIMESTAMP(c.fecha, c.hora_inicio) BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL :minutos MINUTE)
             ORDER BY c.fecha ASC, c.hora_inicio ASC"
        );
        $stmt->execute(['minutos' => $minutos]);
        return self::adjuntarServicios($stmt->fetchAll());
    }

    public static function obtener(int $id): ?array
    {
        self::autocompletarVencidas();

        $stmt = Database::conexion()->prepare(self::SELECT_BASE . ' WHERE c.id = :id');
        $stmt->execute(['id' => $id]);
        $fila = $stmt->fetch();
        if (!$fila) {
            return null;
        }
        $conServicios = self::adjuntarServicios([$fila]);
        return $conServicios[0];
    }

    /**
     * Le agrega "servicios" (lista de {id, nombre}) a un grupo de citas en
     * UNA sola consulta extra, sin importar cuántas citas haya (mismo
     * patrón que Servicio::adjuntarPreciosYMateriales, para evitar
     * repetir el problema de N+1 consultas que tenía antes esta pantalla).
     */
    private static function adjuntarServicios(array $citas): array
    {
        if (empty($citas)) {
            return [];
        }

        $ids = array_map(fn($c) => (int) $c['id'], $citas);
        $marcadores = implode(',', array_fill(0, count($ids), '?'));

        $stmt = Database::conexion()->prepare(
            "SELECT cs.cita_id, s.id AS servicio_id, s.nombre AS servicio_nombre
             FROM cita_servicio cs
             JOIN servicios s ON s.id = cs.servicio_id
             WHERE cs.cita_id IN ($marcadores)
             ORDER BY s.orden ASC, s.nombre ASC"
        );
        $stmt->execute($ids);

        $serviciosPorCita = [];
        foreach ($stmt->fetchAll() as $fila) {
            $serviciosPorCita[(int) $fila['cita_id']][] = [
                'id'     => (int) $fila['servicio_id'],
                'nombre' => $fila['servicio_nombre'],
            ];
        }

        foreach ($citas as &$c) {
            $c['servicios'] = $serviciosPorCita[(int) $c['id']] ?? [];
        }
        unset($c);

        return $citas;
    }

    /**
     * Marca como "completada" cualquier cita pendiente/confirmada cuya
     * hora de fin ya pasó. Como InfinityFree no ofrece tareas programadas
     * (cron), esto se corre solo cada vez que se consulta la agenda —
     * en la práctica, cada vez que abres el panel o la agenda queda al día.
     */
    private static function autocompletarVencidas(): void
    {
        Database::conexion()->exec(
            "UPDATE citas SET estado = 'completada'
             WHERE estado IN ('pendiente', 'confirmada')
             AND TIMESTAMP(fecha, hora_fin) < NOW()"
        );
    }

    public static function crear(
        int $clientaId,
        array $servicioIds,
        string $fecha,
        string $horaInicio,
        string $horaFin,
        string $estado = 'pendiente',
        string $notas = '',
        ?int $recordatorioMinutos = null
    ): int {
        $pdo = Database::conexion();
        $stmt = $pdo->prepare(
            'INSERT INTO citas (clienta_id, fecha, hora_inicio, hora_fin, estado, notas, recordatorio_minutos)
             VALUES (:clienta, :fecha, :inicio, :fin, :estado, :notas, :recordatorio)'
        );
        $stmt->execute([
            'clienta'      => $clientaId,
            'fecha'        => $fecha,
            'inicio'       => $horaInicio,
            'fin'          => $horaFin,
            'estado'       => $estado,
            'notas'        => $notas,
            'recordatorio' => $recordatorioMinutos,
        ]);
        $id = (int) $pdo->lastInsertId();
        self::sincronizarServicios($id, $servicioIds);
        self::sincronizarRecordatorio($id, null, $recordatorioMinutos);
        return $id;
    }

    public static function actualizar(
        int $id,
        int $clientaId,
        array $servicioIds,
        string $fecha,
        string $horaInicio,
        string $horaFin,
        string $estado,
        string $notas = '',
        ?int $recordatorioMinutos = null
    ): bool {
        $anterior = self::obtener($id);

        $stmt = Database::conexion()->prepare(
            'UPDATE citas SET clienta_id = :clienta, fecha = :fecha,
             hora_inicio = :inicio, hora_fin = :fin, estado = :estado, notas = :notas,
             recordatorio_minutos = :recordatorio
             WHERE id = :id'
        );
        $ok = $stmt->execute([
            'clienta'      => $clientaId,
            'fecha'        => $fecha,
            'inicio'       => $horaInicio,
            'fin'          => $horaFin,
            'estado'       => $estado,
            'notas'        => $notas,
            'recordatorio' => $recordatorioMinutos,
            'id'           => $id,
        ]);

        if ($ok) {
            self::sincronizarServicios($id, $servicioIds);
        }

        if ($ok && $anterior) {
            $cambioMomento = $anterior['fecha'] !== $fecha
                || substr((string) $anterior['hora_inicio'], 0, 5) !== substr($horaInicio, 0, 5);
            $cambioRecordatorio = (int) ($anterior['recordatorio_minutos'] ?? 0) !== (int) ($recordatorioMinutos ?? 0);
            $cambioCancelada = $estado === 'cancelada' && $anterior['estado'] !== 'cancelada';

            // Solo se toca OneSignal si de verdad hace falta (cambió la fecha,
            // la hora, el "recordarme" elegido, o se acaba de cancelar la cita).
            // Si nada de eso cambió, no se hace ninguna llamada externa.
            if ($cambioMomento || $cambioRecordatorio || $cambioCancelada) {
                self::sincronizarRecordatorio(
                    $id,
                    (string) $anterior['notificacion_id'] ?: null,
                    $cambioCancelada ? null : $recordatorioMinutos
                );
            }
        }

        return $ok;
    }

    /** Reemplaza la lista de servicios de una cita (borra y vuelve a insertar, como con los materiales de un servicio). */
    private static function sincronizarServicios(int $citaId, array $servicioIds): void
    {
        $pdo = Database::conexion();
        $pdo->prepare('DELETE FROM cita_servicio WHERE cita_id = :id')->execute(['id' => $citaId]);

        if (empty($servicioIds)) {
            return;
        }
        $ins = $pdo->prepare('INSERT INTO cita_servicio (cita_id, servicio_id) VALUES (:cid, :sid)');
        foreach (array_unique(array_map('intval', $servicioIds)) as $servicioId) {
            $ins->execute(['cid' => $citaId, 'sid' => $servicioId]);
        }
    }

    /**
     * Cancela el recordatorio anterior (si había) y programa uno nuevo
     * (si hace falta), guardando el id resultante. Se llama sola desde
     * crear()/actualizar() — ni api/citas.php ni agenda.js necesitan
     * preocuparse por esto, y la REST API Key de OneSignal nunca sale
     * de aquí (ver src/OneSignalPush.php).
     */
    private static function sincronizarRecordatorio(int $id, ?string $notificacionIdAnterior, ?int $recordatorioMinutos): void
    {
        if (!OneSignalPush::habilitado()) {
            return;
        }

        if ($notificacionIdAnterior) {
            OneSignalPush::cancelarRecordatorio($notificacionIdAnterior);
        }

        if (!$recordatorioMinutos) {
            self::actualizarNotificacionId($id, null);
            return;
        }

        $cita = self::obtener($id);
        if (!$cita) {
            return;
        }
        $nuevoId = OneSignalPush::programarRecordatorio($cita, $recordatorioMinutos);
        self::actualizarNotificacionId($id, $nuevoId);
    }

    public static function cambiarEstado(int $id, string $estado): bool
    {
        $stmt = Database::conexion()->prepare('UPDATE citas SET estado = :estado WHERE id = :id');
        return $stmt->execute(['estado' => $estado, 'id' => $id]);
    }

    /** Guarda el id de la notificación programada en OneSignal (o NULL para borrarlo). */
    public static function actualizarNotificacionId(int $id, ?string $notificacionId): bool
    {
        $stmt = Database::conexion()->prepare('UPDATE citas SET notificacion_id = :nid WHERE id = :id');
        return $stmt->execute(['nid' => $notificacionId, 'id' => $id]);
    }

    public static function eliminar(int $id): bool
    {
        $cita = self::obtener($id);
        if ($cita && !empty($cita['notificacion_id'])) {
            OneSignalPush::cancelarRecordatorio((string) $cita['notificacion_id']);
        }
        $stmt = Database::conexion()->prepare('DELETE FROM citas WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    /** Cuenta citas por día dentro de un rango, para pintar el calendario mensual. */
    public static function contarPorDia(string $desde, string $hasta): array
    {
        $stmt = Database::conexion()->prepare(
            'SELECT fecha, COUNT(*) AS total FROM citas
             WHERE fecha BETWEEN :desde AND :hasta GROUP BY fecha'
        );
        $stmt->execute(['desde' => $desde, 'hasta' => $hasta]);
        $resultado = [];
        foreach ($stmt->fetchAll() as $fila) {
            $resultado[$fila['fecha']] = (int) $fila['total'];
        }
        return $resultado;
    }
}
