<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
Auth::requerirLogin();

$tituloPagina = 'Reprocesar imágenes · Panel LiliesNails';
$resultados = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['procesar'])) {
    @set_time_limit(120);
    $resultados = ['ok' => 0, 'omitidas' => 0, 'errores' => []];

    // ---------- Fotos de servicios ----------
    $carpetaServicios = __DIR__ . '/assets/img/servicios/';
    foreach (Servicio::listarTodos() as $servicio) {
        $imagen = $servicio['imagen'] ?? null;
        if (!$imagen || str_ends_with(strtolower($imagen), '.webp')) {
            $resultados['omitidas']++;
            continue;
        }
        try {
            $rutaActual = $carpetaServicios . $imagen;
            $nuevoNombre = ImagenUtil::comprimirArchivoExistente($rutaActual, $carpetaServicios, 'servicio_' . $servicio['id']);
            Servicio::actualizarImagen((int) $servicio['id'], $nuevoNombre);
            ImagenUtil::borrar($carpetaServicios, $imagen);
            $resultados['ok']++;
        } catch (Throwable $e) {
            $resultados['errores'][] = 'Servicio "' . $servicio['nombre'] . '": ' . $e->getMessage();
        }
    }

    // ---------- Fotos de la galería ----------
    $carpetaGaleria = __DIR__ . '/assets/img/galeria/';
    foreach (Galeria::listarTodas() as $foto) {
        $imagen = $foto['imagen'];
        if (str_ends_with(strtolower($imagen), '.webp')) {
            $resultados['omitidas']++;
            continue;
        }
        try {
            $rutaActual = $carpetaGaleria . $imagen;
            $nuevoNombre = ImagenUtil::comprimirArchivoExistente($rutaActual, $carpetaGaleria, 'diseno');
            Database::conexion()
                ->prepare('UPDATE galeria SET imagen = :imagen WHERE id = :id')
                ->execute(['imagen' => $nuevoNombre, 'id' => $foto['id']]);
            ImagenUtil::borrar($carpetaGaleria, $imagen);
            $resultados['ok']++;
        } catch (Throwable $e) {
            $resultados['errores'][] = 'Foto de galería "' . ($foto['titulo'] ?: $imagen) . '": ' . $e->getMessage();
        }
    }
}

require __DIR__ . '/../includes/header_panel.php';
?>

<main class="panel-contenido">
  <section class="bloque">
    <h2><i class="fa-solid fa-file-zipper"></i> Reprocesar imágenes antiguas</h2>
    <p style="color:var(--texto-mas-suave); font-size:0.88rem; margin-bottom:16px;">
      Esto comprime y convierte a WebP todas las fotos de servicios y de la
      galería que subiste <strong>antes</strong> de que esto se hiciera
      automático. Las fotos que ya son .webp se dejan tal cual — puedes
      correr esto las veces que quieras sin duplicar nada.
    </p>

    <?php if ($resultados !== null): ?>
      <div class="mensaje-exito">
        ✅ <?= (int) $resultados['ok'] ?> foto(s) comprimidas.
        <?php if ($resultados['omitidas'] > 0): ?>
          <?= (int) $resultados['omitidas'] ?> ya estaban en WebP y se dejaron igual.
        <?php endif; ?>
      </div>
      <?php if (!empty($resultados['errores'])): ?>
        <div class="mensaje-error">
          Hubo problemas con algunas fotos:
          <ul style="margin:8px 0 0; padding-left:18px;">
            <?php foreach ($resultados['errores'] as $err): ?>
              <li><?= h($err) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>
    <?php endif; ?>

    <form method="post" action="reprocesar-imagenes.php" onsubmit="return confirm('¿Comprimir ahora todas las fotos antiguas? Puede tardar unos segundos.');">
      <button type="submit" name="procesar" value="1" class="btn btn-bloque">
        <i class="fa-solid fa-bolt"></i> Comprimir fotos antiguas ahora
      </button>
    </form>
  </section>
</main>

<?php require __DIR__ . '/../includes/footer_panel.php'; ?>
