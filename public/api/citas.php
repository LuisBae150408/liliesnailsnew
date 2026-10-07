<?php
declare(strict_types=1);
require __DIR__ . '/../../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if (!Auth::estaAutenticado()) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

$metodo = $_SERVER['REQUEST_METHOD'];
$estadosValidos = ['pendiente', 'confirmada', 'completada', 'cancelada'];

try {
    switch ($metodo) {
        case 'GET':
            if (isset($_GET['id'])) {
                $cita = Cita::obtener((int) $_GET['id']);
                if (!$cita) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Cita no encontrada']);
                    break;
                }
                echo json_encode($cita);
                break;
            }

            // Citas próximas (para el aviso dentro del panel al abrirlo)
            if (isset($_GET['proximas'])) {
                $minutos = (int) ($_GET['proximas'] > 1 ? $_GET['proximas'] : 180);
                echo json_encode(Cita::listarProximas($minutos));
                break;
            }

            $desde = (string) ($_GET['desde'] ?? date('Y-m-d'));
            $hasta = (string) ($_GET['hasta'] ?? $desde);

            if (isset($_GET['conteo'])) {
                echo json_encode(Cita::contarPorDia($desde, $hasta));
                break;
            }

            echo json_encode(Cita::listarPorRango($desde, $hasta));
            break;

        case 'POST':
            $datos = json_decode(file_get_contents('php://input'), true) ?? [];
            [$campos, $err] = validarCita($datos, $estadosValidos);
            if ($err) {
                http_response_code(422);
                echo json_encode(['error' => $err]);
                break;
            }
            [$clientaId, $servicioIds, $fecha, $horaInicio, $horaFin, $estado, $notas, $recordatorio] = $campos;
            $id = Cita::crear($clientaId, $servicioIds, $fecha, $horaInicio, $horaFin, $estado, $notas, $recordatorio);
            echo json_encode(Cita::obtener($id));
            break;

        case 'PUT':
        case 'PATCH':
            $id = (int) ($_GET['id'] ?? 0);
            if ($id <= 0 || !Cita::obtener($id)) {
                http_response_code(404);
                echo json_encode(['error' => 'Cita no encontrada']);
                break;
            }
            $datos = json_decode(file_get_contents('php://input'), true) ?? [];

            // Cambio rápido de estado (ej. desde un botón "Marcar completada")
            if (isset($datos['soloEstado'])) {
                $estado = (string) ($datos['estado'] ?? '');
                if (!in_array($estado, $estadosValidos, true)) {
                    http_response_code(422);
                    echo json_encode(['error' => 'Estado inválido']);
                    break;
                }
                Cita::cambiarEstado($id, $estado);
                echo json_encode(Cita::obtener($id));
                break;
            }

            [$campos, $err] = validarCita($datos, $estadosValidos);
            if ($err) {
                http_response_code(422);
                echo json_encode(['error' => $err]);
                break;
            }
            [$clientaId, $servicioIds, $fecha, $horaInicio, $horaFin, $estado, $notas, $recordatorio] = $campos;
            Cita::actualizar($id, $clientaId, $servicioIds, $fecha, $horaInicio, $horaFin, $estado, $notas, $recordatorio);
            echo json_encode(Cita::obtener($id));
            break;

        case 'DELETE':
            $id = (int) ($_GET['id'] ?? 0);
            if ($id <= 0) {
                http_response_code(422);
                echo json_encode(['error' => 'Falta el id']);
                break;
            }
            Cita::eliminar($id);
            echo json_encode(['ok' => true]);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
    }
} catch (Throwable $e) {
    error_log('api/citas.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Ocurrió un error inesperado']);
}

/** @return array{0:array,1:?string} */
function validarCita(array $datos, array $estadosValidos): array
{
    $nombreClienta = trim((string) ($datos['clientaNombre'] ?? ''));
    $telefonoClienta = trim((string) ($datos['clientaTelefono'] ?? ''));
    $clientaIdExistente = isset($datos['clientaId']) ? (int) $datos['clientaId'] : 0;
    $servicioIds = array_values(array_unique(array_map('intval', (array) ($datos['servicioIds'] ?? []))));
    $fecha = (string) ($datos['fecha'] ?? '');
    $horaInicio = (string) ($datos['horaInicio'] ?? '');
    $horaFin = (string) ($datos['horaFin'] ?? '');
    $estado = (string) ($datos['estado'] ?? 'pendiente');
    $notas = trim((string) ($datos['notas'] ?? ''));
    $recordatorio = !empty($datos['recordatorioMinutos']) ? (int) $datos['recordatorioMinutos'] : null;

    if ($clientaIdExistente <= 0 && $nombreClienta === '') {
        return [[], 'Escribe el nombre de la clienta'];
    }
    if (!$fecha || !$horaInicio || !$horaFin) {
        return [[], 'Completa fecha, hora de inicio y hora de fin'];
    }
    if ($horaFin <= $horaInicio) {
        return [[], 'La hora de fin debe ser posterior a la hora de inicio'];
    }
    if (!in_array($estado, $estadosValidos, true)) {
        return [[], 'Estado inválido'];
    }

    $clientaId = $clientaIdExistente > 0
        ? $clientaIdExistente
        : Clienta::buscarOCrear($nombreClienta, $telefonoClienta);

    return [[$clientaId, $servicioIds, $fecha, $horaInicio, $horaFin, $estado, $notas, $recordatorio], null];
}
