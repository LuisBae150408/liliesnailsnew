<?php
/**
 * src/ImagenUtil.php
 * -----------------------------------------------------------------
 * Comprime y convierte imágenes a WebP (mucho más liviano que
 * JPG/PNG, mismo aspecto). Si el servidor no tiene soporte de WebP
 * en GD, cae de vuelta a JPEG comprimido en vez de fallar.
 *
 * Se usa tanto para archivos recién subidos ($_FILES) como para
 * reprocesar fotos que ya estaban guardadas de antes (ver
 * reprocesar-imagenes.php).
 * -----------------------------------------------------------------
 */

declare(strict_types=1);

class ImagenUtil
{
    private const EXTENSIONES_PERMITIDAS = ['jpg', 'jpeg', 'png', 'webp'];
    public const ANCHO_MAXIMO = 1600; // px — de sobra para verse nítida, sin pesar de más
    private const CALIDAD_WEBP = 80;
    private const CALIDAD_JPG = 82;

    /**
     * Procesa un archivo recién subido ($_FILES['algo']). Devuelve el
     * nombre final del archivo guardado, o lanza una excepción con un
     * mensaje amigable si algo no sirve.
     */
    public static function guardarComprimida(array $archivo, string $carpetaDestino, string $prefijo): string
    {
        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('No se pudo recibir el archivo. Intenta de nuevo.');
        }
        if ($archivo['size'] > 8 * 1024 * 1024) {
            // 8 MB de tope en el original; luego de comprimir queda mucho más liviano igual
            throw new RuntimeException('La imagen no debe pesar más de 8 MB.');
        }
        $extensionOriginal = strtolower((string) pathinfo($archivo['name'], PATHINFO_EXTENSION));
        if (!in_array($extensionOriginal, self::EXTENSIONES_PERMITIDAS, true)) {
            throw new RuntimeException('Formato no permitido. Usa JPG, PNG o WEBP.');
        }

        return self::procesarArchivo($archivo['tmp_name'], $carpetaDestino, $prefijo);
    }

    /**
     * Reprocesa un archivo que YA está guardado en el servidor (fotos
     * subidas antes de que existiera esta compresión automática).
     */
    public static function comprimirArchivoExistente(string $rutaArchivoActual, string $carpetaDestino, string $prefijo): string
    {
        if (!is_file($rutaArchivoActual)) {
            throw new RuntimeException('El archivo original ya no existe.');
        }

        return self::procesarArchivo($rutaArchivoActual, $carpetaDestino, $prefijo);
    }

    /** Lógica compartida: carga, redimensiona si hace falta, guarda en WebP (o JPG de respaldo). */
    private static function procesarArchivo(string $rutaOrigen, string $carpetaDestino, string $prefijo): string
    {
        if (!function_exists('imagecreatetruecolor')) {
            throw new RuntimeException('El servidor no tiene la extensión GD de PHP habilitada para procesar imágenes.');
        }

        $info = @getimagesize($rutaOrigen);
        if ($info === false) {
            throw new RuntimeException('El archivo no parece ser una imagen válida.');
        }

        // Intenta darle un poco más de memoria para fotos grandes de celular
        // (si el hosting no lo permite, simplemente sigue con el límite que ya había)
        @ini_set('memory_limit', '256M');

        $imagenOrigen = self::cargarImagen($rutaOrigen, $info[2]);
        if ($imagenOrigen === false) {
            throw new RuntimeException('No se pudo procesar la imagen. Prueba con otra foto.');
        }

        $anchoOriginal = imagesx($imagenOrigen);
        $altoOriginal = imagesy($imagenOrigen);

        if ($anchoOriginal > self::ANCHO_MAXIMO) {
            $nuevoAncho = self::ANCHO_MAXIMO;
            $nuevoAlto = (int) round($altoOriginal * ($nuevoAncho / $anchoOriginal));
            $imagenFinal = imagecreatetruecolor($nuevoAncho, $nuevoAlto);
            self::preservarTransparencia($imagenFinal);
            imagecopyresampled($imagenFinal, $imagenOrigen, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $anchoOriginal, $altoOriginal);
            imagedestroy($imagenOrigen);
        } else {
            $imagenFinal = $imagenOrigen;
        }

        if (!is_dir($carpetaDestino)) {
            mkdir($carpetaDestino, 0755, true);
        }

        $nombreArchivo = $prefijo . '_' . uniqid() . '.webp';
        $rutaCompleta = rtrim($carpetaDestino, '/') . '/' . $nombreArchivo;

        $guardadoOk = function_exists('imagewebp') && @imagewebp($imagenFinal, $rutaCompleta, self::CALIDAD_WEBP);

        if (!$guardadoOk) {
            // El servidor no tiene soporte de WebP en GD: cae a JPEG comprimido.
            // JPEG no soporta transparencia, así que se compone sobre fondo blanco.
            $nombreArchivo = $prefijo . '_' . uniqid() . '.jpg';
            $rutaCompleta = rtrim($carpetaDestino, '/') . '/' . $nombreArchivo;

            $ancho = imagesx($imagenFinal);
            $alto = imagesy($imagenFinal);
            $fondoBlanco = imagecreatetruecolor($ancho, $alto);
            $blanco = imagecolorallocate($fondoBlanco, 255, 255, 255);
            imagefilledrectangle($fondoBlanco, 0, 0, $ancho, $alto, $blanco);
            imagealphablending($fondoBlanco, true);
            imagecopy($fondoBlanco, $imagenFinal, 0, 0, 0, 0, $ancho, $alto);

            $guardadoOk = imagejpeg($fondoBlanco, $rutaCompleta, self::CALIDAD_JPG);
            imagedestroy($fondoBlanco);
        }

        imagedestroy($imagenFinal);

        if (!$guardadoOk) {
            throw new RuntimeException('No se pudo guardar la imagen procesada en el servidor.');
        }

        return $nombreArchivo;
    }

    /** @return \GdImage|false */
    private static function cargarImagen(string $ruta, int $tipo)
    {
        return match ($tipo) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($ruta),
            IMAGETYPE_PNG  => @imagecreatefrompng($ruta),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($ruta) : false,
            default        => false,
        };
    }

    private static function preservarTransparencia($imagen): void
    {
        imagealphablending($imagen, false);
        imagesavealpha($imagen, true);
        $transparente = imagecolorallocatealpha($imagen, 0, 0, 0, 127);
        imagefilledrectangle($imagen, 0, 0, imagesx($imagen), imagesy($imagen), $transparente);
    }

    /** Borra un archivo de imagen si existe (para no acumular huérfanos al reemplazar). */
    public static function borrar(string $carpeta, ?string $nombreArchivo): void
    {
        if (!$nombreArchivo) {
            return;
        }
        $ruta = rtrim($carpeta, '/') . '/' . $nombreArchivo;
        if (is_file($ruta)) {
            @unlink($ruta);
        }
    }
}
