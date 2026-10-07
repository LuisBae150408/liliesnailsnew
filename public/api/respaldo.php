<?php
declare(strict_types=1);
require __DIR__ . '/../../includes/bootstrap.php';

if (!Auth::estaAutenticado()) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

$metodo = $_SERVER['REQUEST_METHOD'];

if ($metodo === 'GET') {
    // ------- Exportar: mismo formato que usaba la app original -------
    $materiales = array_map(static function (array $m) {
        return [
            'id' => (int) $m['id'],
            'nombre' => $m['nombre'],
            'precio' => (float) $m['precio'],
            'rinde' => (int) $m['rinde'],
            'costoUso' => (float) $m['costo_uso'],
        ];
    }, Material::listarTodos());

    $servicios = array_map(static function (array $s) {
        return [
            'id' => (int) $s['id'],
            'nombre' => $s['nombre'],
            'precio' => number_format($s['precio_final'], 2, '.', ''),
            'ganancia' => (float) $s['margen_porcentaje'],
            'gHora' => (float) $s['ganancia_hora'],
            'tiempo' => (int) $s['tiempo_minutos'],
            'materialesIds' => $s['materiales_ids'],
        ];
    }, Servicio::listarTodos());

    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="respaldo_nails_' . date('Y-m-d') . '.json"');
    echo json_encode(['materiales' => $materiales, 'servicios' => $servicios], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($metodo === 'POST') {
    // ------- Importar: reemplaza materiales y servicios existentes -------
    header('Content-Type: application/json; charset=utf-8');
    $contenido = file_get_contents('php://input');
    $datos = json_decode($contenido, true);

    if (!is_array($datos) || !isset($datos['materiales'], $datos['servicios'])) {
        http_response_code(422);
        echo json_encode(['error' => 'El archivo de respaldo no tiene el formato esperado']);
        exit;
    }

    $pdo = Database::conexion();
    $pdo->beginTransaction();
    try {
        // Limpieza total (igual que el import original, que reemplazaba el array completo)
        $pdo->exec('DELETE FROM servicio_material');
        $pdo->exec('DELETE FROM servicios');
        $pdo->exec('DELETE FROM materiales');

        $mapaIds = []; // id original del backup => id nuevo real en BD

        foreach ($datos['materiales'] as $m) {
            $nuevoId = Material::crear(
                (string) ($m['nombre'] ?? 'Material'),
                (float) ($m['precio'] ?? 0),
                max(1, (int) ($m['rinde'] ?? 1))
            );
            $mapaIds[(string) ($m['id'] ?? '')] = $nuevoId;
        }

        foreach ($datos['servicios'] as $s) {
            $idsOriginales = (array) ($s['materialesIds'] ?? []);
            $idsNuevos = [];
            foreach ($idsOriginales as $idOriginal) {
                if (isset($mapaIds[(string) $idOriginal])) {
                    $idsNuevos[] = $mapaIds[(string) $idOriginal];
                }
            }
            Servicio::crear(
                (string) ($s['nombre'] ?? 'Servicio'),
                (float) ($s['ganancia'] ?? 0),
                (float) ($s['gHora'] ?? 0),
                (int) ($s['tiempo'] ?? 0),
                $idsNuevos,
                true
            );
        }

        $pdo->commit();
        echo json_encode(['ok' => true]);
    } catch (Throwable $e) {
        $pdo->rollBack();
        error_log('api/respaldo.php import: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'No se pudo importar el respaldo']);
    }
    exit;
}

http_response_code(405);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['error' => 'Método no permitido']);
