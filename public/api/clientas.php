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
            echo json_encode(Clienta::listarTodas($filtro));
            break;

        case 'POST':
            $datos = json_decode(file_get_contents('php://input'), true) ?? [];
            $nombre = trim((string) ($datos['nombre'] ?? ''));
            $telefono = trim((string) ($datos['telefono'] ?? ''));
            $notas = trim((string) ($datos['notas'] ?? ''));
            if ($nombre === '') {
                http_response_code(422);
                echo json_encode(['error' => 'El nombre de la clienta es obligatorio']);
                break;
            }
            $id = Clienta::crear($nombre, $telefono, $notas);
            echo json_encode(Clienta::obtener($id));
            break;

        case 'PUT':
        case 'PATCH':
            $id = (int) ($_GET['id'] ?? 0);
            if ($id <= 0 || !Clienta::obtener($id)) {
                http_response_code(404);
                echo json_encode(['error' => 'Clienta no encontrada']);
                break;
            }
            $datos = json_decode(file_get_contents('php://input'), true) ?? [];
            $nombre = trim((string) ($datos['nombre'] ?? ''));
            $telefono = trim((string) ($datos['telefono'] ?? ''));
            $notas = trim((string) ($datos['notas'] ?? ''));
            if ($nombre === '') {
                http_response_code(422);
                echo json_encode(['error' => 'El nombre de la clienta es obligatorio']);
                break;
            }
            Clienta::actualizar($id, $nombre, $telefono, $notas);
            echo json_encode(Clienta::obtener($id));
            break;

        case 'DELETE':
            $id = (int) ($_GET['id'] ?? 0);
            if ($id <= 0) {
                http_response_code(422);
                echo json_encode(['error' => 'Falta el id']);
                break;
            }
            Clienta::eliminar($id);
            echo json_encode(['ok' => true]);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
    }
} catch (Throwable $e) {
    error_log('api/clientas.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Ocurrió un error inesperado']);
}
