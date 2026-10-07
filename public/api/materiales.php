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
            echo json_encode(Material::listarTodos($filtro));
            break;

        case 'POST':
            $datos = json_decode(file_get_contents('php://input'), true) ?? [];
            [$nombre, $precio, $rinde, $err] = validarMaterial($datos);
            if ($err) {
                http_response_code(422);
                echo json_encode(['error' => $err]);
                break;
            }
            $id = Material::crear($nombre, $precio, $rinde);
            echo json_encode(Material::obtener($id));
            break;

        case 'PUT':
        case 'PATCH':
            $id = (int) ($_GET['id'] ?? 0);
            if ($id <= 0 || !Material::obtener($id)) {
                http_response_code(404);
                echo json_encode(['error' => 'Material no encontrado']);
                break;
            }
            $datos = json_decode(file_get_contents('php://input'), true) ?? [];
            [$nombre, $precio, $rinde, $err] = validarMaterial($datos);
            if ($err) {
                http_response_code(422);
                echo json_encode(['error' => $err]);
                break;
            }
            Material::actualizar($id, $nombre, $precio, $rinde);
            echo json_encode(Material::obtener($id));
            break;

        case 'DELETE':
            $id = (int) ($_GET['id'] ?? 0);
            if ($id <= 0) {
                http_response_code(422);
                echo json_encode(['error' => 'Falta el id']);
                break;
            }
            Material::eliminar($id);
            echo json_encode(['ok' => true]);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
    }
} catch (Throwable $e) {
    error_log('api/materiales.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Ocurrió un error inesperado']);
}

/** @return array{0:string,1:float,2:int,3:?string} */
function validarMaterial(array $datos): array
{
    $nombre = trim((string) ($datos['nombre'] ?? ''));
    $precio = (float) ($datos['precio'] ?? -1);
    $rinde = (int) ($datos['rinde'] ?? 0);

    if ($nombre === '') {
        return [$nombre, $precio, $rinde, 'El nombre es obligatorio'];
    }
    if ($precio < 0) {
        return [$nombre, $precio, $rinde, 'El precio debe ser mayor o igual a 0'];
    }
    if ($rinde < 1) {
        return [$nombre, $precio, $rinde, 'El rendimiento debe ser al menos 1'];
    }
    return [$nombre, $precio, $rinde, null];
}
