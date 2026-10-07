<?php
declare(strict_types=1);
require __DIR__ . '/../../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if (!Auth::estaAutenticado()) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$datos = json_decode(file_get_contents('php://input'), true) ?? [];
$subscriptionId = trim((string) ($datos['subscriptionId'] ?? ''));

if ($subscriptionId === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Falta subscriptionId']);
    exit;
}

// Guardamos una LISTA de dispositivos suscritos (no solo uno) — antes,
// activar notificaciones en un dispositivo nuevo borraba silenciosamente
// el anterior (por ejemplo, activar en el celular hacía que dejaran de
// llegarte en la PC, y viceversa). Ahora se van sumando, hasta un máximo
// razonable, sin duplicados.
$idsGuardados = json_decode(configSitio('onesignal_subscription_ids', '[]'), true);
if (!is_array($idsGuardados)) {
    $idsGuardados = [];
}
if (!in_array($subscriptionId, $idsGuardados, true)) {
    $idsGuardados[] = $subscriptionId;
}
$idsGuardados = array_slice($idsGuardados, -10); // como mucho los últimos 10 dispositivos

guardarConfigSitio('onesignal_subscription_ids', json_encode($idsGuardados));

echo json_encode([
    'ok' => true,
    'total' => count($idsGuardados),
    // Devolvemos el id tal cual quedó guardado, para que el navegador
    // pueda mostrarlo en pantalla y comparar visualmente que coincide
    // con el id real de este dispositivo (ver configuracion.php).
    'idGuardado' => $subscriptionId,
]);
