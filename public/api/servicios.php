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

try {
    switch ($metodo) {
        case 'GET':
            $filtro = trim((string) ($_GET['buscar'] ?? ''));
            echo json_encode(Servicio::listarTodos($filtro));
            break;

        case 'POST':
            $datos = json_decode(file_get_contents('php://input'), true) ?? [];
            [$campos, $err] = validarServicio($datos);
            if ($err) {
                http_response_code(422);
                echo json_encode(['error' => $err]);
                break;
            }
            $id = Servicio::crear(...$campos);
            echo json_encode(Servicio::obtener($id));
            break;

        case 'PUT':
        case 'PATCH':
            $id = (int) ($_GET['id'] ?? 0);
            if ($id <= 0 || !Servicio::obtener($id)) {
                http_response_code(404);
                echo json_encode(['error' => 'Servicio no encontrado']);
                break;
            }
            $datos = json_decode(file_get_contents('php://input'), true) ?? [];

            // Mover un lugar arriba/abajo en el orden del catálogo
            if (isset($datos['moverOrden'])) {
                $direccion = $datos['direccion'] === 'arriba' ? 'arriba' : 'abajo';
                Servicio::moverOrden($id, $direccion);
                echo json_encode(Servicio::obtener($id));
                break;
            }

            [$campos, $err] = validarServicio($datos);
            if ($err) {
                http_response_code(422);
                echo json_encode(['error' => $err]);
                break;
            }
            Servicio::actualizar($id, ...$campos);
            echo json_encode(Servicio::obtener($id));
            break;

        case 'DELETE':
            $id = (int) ($_GET['id'] ?? 0);
            if ($id <= 0) {
                http_response_code(422);
                echo json_encode(['error' => 'Falta el id']);
                break;
            }
            Servicio::eliminar($id);
            echo json_encode(['ok' => true]);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
    }
} catch (Throwable $e) {
    error_log('api/servicios.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Ocurrió un error inesperado']);
}

/** @return array{0:array,1:?string} */
function validarServicio(array $datos): array
{
    $nombre = trim((string) ($datos['nombre'] ?? ''));
    $margen = (float) ($datos['margenPorcentaje'] ?? -1);
    $gHora = (float) ($datos['gananciaHora'] ?? -1);
    $tiempo = (int) ($datos['tiempoMinutos'] ?? -1);
    $materialesIds = array_map('intval', (array) ($datos['materialesIds'] ?? []));
    $visible = !empty($datos['visiblePublico']);
    $descripcion = trim((string) ($datos['descripcionPublica'] ?? ''));

    if ($nombre === '') {
        return [[], 'Ponle un nombre al servicio'];
    }
    if ($margen < 0 || $gHora < 0 || $tiempo < 0) {
        return [[], 'Revisa que ganancia/hora, tiempo y margen sean números válidos'];
    }

    return [[$nombre, $margen, $gHora, $tiempo, $materialesIds, $visible, $descripcion, null], null];
}
