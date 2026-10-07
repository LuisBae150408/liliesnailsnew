<?php
declare(strict_types=1);
require __DIR__ . '/../../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if (!Auth::estaAutenticado()) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

$carpetaDestino = __DIR__ . '/../assets/img/servicios/';
if (!is_dir($carpetaDestino)) {
    mkdir($carpetaDestino, 0755, true);
}

$metodo = $_SERVER['REQUEST_METHOD'];

try {
    // ---------- Quitar imagen ----------
    if ($metodo === 'DELETE') {
        $servicioId = (int) ($_GET['servicioId'] ?? 0);
        $servicio = $servicioId > 0 ? Servicio::obtener($servicioId) : null;

        if (!$servicio) {
            http_response_code(404);
            echo json_encode(['error' => 'Servicio no encontrado']);
            exit;
        }

        ImagenUtil::borrar($carpetaDestino, $servicio['imagen'] ?? null);
        Servicio::quitarImagen($servicioId);
        echo json_encode(['ok' => true]);
        exit;
    }

    // ---------- Subir imagen ----------
    if ($metodo === 'POST') {
        $servicioId = (int) ($_POST['servicioId'] ?? 0);
        $servicio = $servicioId > 0 ? Servicio::obtener($servicioId) : null;
        $archivo = $_FILES['imagen'] ?? null;

        if (!$servicio) {
            http_response_code(404);
            echo json_encode(['error' => 'Servicio no encontrado']);
            exit;
        }
        if (!$archivo) {
            http_response_code(422);
            echo json_encode(['error' => 'Selecciona una imagen válida']);
            exit;
        }

        try {
            $nombreArchivo = ImagenUtil::guardarComprimida($archivo, $carpetaDestino, 'servicio_' . $servicioId);
        } catch (RuntimeException $e) {
            http_response_code(422);
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }

        // Borra la imagen anterior para no acumular archivos huérfanos
        ImagenUtil::borrar($carpetaDestino, $servicio['imagen'] ?? null);

        Servicio::actualizarImagen($servicioId, $nombreArchivo);
        echo json_encode(['ok' => true, 'imagen' => $nombreArchivo]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
} catch (Throwable $e) {
    error_log('api/subir-imagen-servicio.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Ocurrió un error inesperado']);
}
