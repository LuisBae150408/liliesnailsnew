<?php
/**
 * src/OneSignalPush.php
 * -----------------------------------------------------------------
 * Antes, agenda.js llamaba a la API de OneSignal DIRECTO desde el
 * navegador, lo que obligaba a exponer la REST API Key (secreta) en
 * el HTML del panel — cualquiera con acceso a "Ver código fuente"
 * podía copiarla y usarla para mandar notificaciones a nombre tuyo.
 *
 * Ahora esa llamada se hace aquí, en el servidor: la API Key nunca
 * sale del backend, y de paso el navegador ya no tiene que esperar
 * una petición extra a un servidor externo antes de cerrar el modal
 * de la cita (guardar se siente más rápido).
 * -----------------------------------------------------------------
 */

declare(strict_types=1);

class OneSignalPush
{
    private const ENDPOINT = 'https://api.onesignal.com/notifications';

    public static function habilitado(): bool
    {
        return ONESIGNAL_APP_ID !== '' && ONESIGNAL_API_KEY !== '';
    }

    /**
     * Programa un recordatorio push para que llegue justo cuando toque
     * (fecha+hora de la cita, menos los minutos de anticipación).
     * Devuelve el id de la notificación en OneSignal, o null si no se
     * pudo programar (o si ya pasó el momento de envío).
     */
    public static function programarRecordatorio(array $cita, int $minutosAntes): ?string
    {
        if (!self::habilitado() || $minutosAntes <= 0) {
            return null;
        }

        try {
            $momentoCita = new DateTime($cita['fecha'] . ' ' . $cita['hora_inicio']);
        } catch (Throwable $e) {
            return null;
        }
        $momentoEnvio = (clone $momentoCita)->modify("-{$minutosAntes} minutes");
        if ($momentoEnvio->getTimestamp() <= time()) {
            return null; // ya pasó, no tiene caso programarlo
        }

        // OneSignal espera "send_after" en UTC. $momentoEnvio se armó con la
        // fecha/hora tal cual se guardan (hora local del negocio, según
        // date_default_timezone_set en config.php), así que hay que
        // convertirla explícitamente antes de mandarla — si no, el aviso
        // llegaría a la hora equivocada (desfasado por el huso horario).
        $momentoEnvioUtc = (clone $momentoEnvio)->setTimezone(new DateTimeZone('UTC'));

        $horaCorta = substr((string) $cita['hora_inicio'], 0, 5);
        $mensaje = trim((string) $cita['clienta_nombre']) . ' — ' . $horaCorta;

        $respuesta = self::peticion('POST', self::ENDPOINT, array_merge([
            'app_id'            => ONESIGNAL_APP_ID,
            'target_channel'    => 'push',
            'headings'          => ['en' => 'Cita próxima', 'es' => 'Cita próxima'],
            'contents'          => ['en' => $mensaje, 'es' => $mensaje],
            'send_after'        => $momentoEnvioUtc->format('D, d M Y H:i:s \G\M\T'),
        ], self::destinatario()));

        return $respuesta['id'] ?? null;
    }

    public static function cancelarRecordatorio(?string $notificacionId): void
    {
        if (!self::habilitado() || !$notificacionId) {
            return;
        }
        self::peticion(
            'DELETE',
            self::ENDPOINT . '/' . rawurlencode($notificacionId) . '?app_id=' . rawurlencode(ONESIGNAL_APP_ID)
        );
    }

    /** Manda una notificación de prueba inmediata (botón "Probar notificación" en Configuración). */
    public static function enviarPrueba(): bool
    {
        if (!self::habilitado()) {
            return false;
        }
        $respuesta = self::peticion('POST', self::ENDPOINT, array_merge([
            'app_id'            => ONESIGNAL_APP_ID,
            'target_channel'    => 'push',
            'headings'          => ['en' => 'LiliesNails', 'es' => 'LiliesNails'],
            'contents'          => ['en' => 'Notificación de prueba ✅', 'es' => 'Notificación de prueba ✅'],
        ], self::destinatario()));
        return isset($respuesta['id']);
    }

    /**
     * A quién le llega la notificación. Manda a TODOS los dispositivos que
     * hayan activado notificaciones alguna vez (PC, celular, la app
     * instalada, etc. — ver public/api/onesignal-subscripcion.php), en vez
     * de a un "segmento" que puede no coincidir con tu app, o a un solo
     * dispositivo que se pisa con los demás.
     */
    private static function destinatario(): array
    {
        $ids = json_decode(configSitio('onesignal_subscription_ids', '[]'), true);
        if (is_array($ids) && !empty($ids)) {
            return ['include_subscription_ids' => array_values($ids)];
        }
        return ['included_segments' => ['Subscribed Users']];
    }

    private static function peticion(string $metodo, string $url, ?array $cuerpo = null): array
    {
        if (!function_exists('curl_init')) {
            // Nunca debe romper el guardado de una cita por esto — si el
            // hosting no tiene cURL habilitado, simplemente no hay recordatorio.
            error_log('OneSignalPush: la extensión cURL de PHP no está habilitada.');
            return [];
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => $metodo,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Key ' . ONESIGNAL_API_KEY,
            ],
        ]);
        if ($cuerpo !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($cuerpo));
        }
        $respuestaCruda = curl_exec($ch);
        curl_close($ch);

        if ($respuestaCruda === false) {
            return [];
        }
        return json_decode($respuestaCruda, true) ?? [];
    }
}
