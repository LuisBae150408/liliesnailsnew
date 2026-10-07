<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
Auth::requerirLogin();

$mensaje = '';
$error = '';

// ---------- Guardar datos generales del sitio ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_config'])) {
    $campos = ['nombre_negocio', 'telefono_whatsapp', 'direccion', 'horario', 'instagram', 'tiktok', 'texto_bienvenida'];
    $pdo = Database::conexion();
    $stmt = $pdo->prepare(
        'INSERT INTO sitio_config (clave, valor) VALUES (:clave, :valor)
         ON DUPLICATE KEY UPDATE valor = :valor2'
    );
    foreach ($campos as $clave) {
        $valor = trim((string) ($_POST[$clave] ?? ''));
        $stmt->execute(['clave' => $clave, 'valor' => $valor, 'valor2' => $valor]);
    }
    $mensaje = 'Datos del sitio actualizados.';
}

// ---------- Subir foto a la galería ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar_foto'])) {
    $titulo = trim((string) ($_POST['titulo_foto'] ?? ''));
    $descripcion = trim((string) ($_POST['descripcion_foto'] ?? ''));
    $archivo = $_FILES['foto'] ?? null;

    if ($titulo === '' || $descripcion === '') {
        $error = 'El título y la descripción son obligatorios.';
    } elseif (!$archivo) {
        $error = 'Selecciona una foto válida para subir.';
    } else {
        try {
            $nombreArchivo = ImagenUtil::guardarComprimida($archivo, __DIR__ . '/assets/img/galeria/', 'diseno');
            Galeria::crear($titulo, $nombreArchivo, $descripcion);
            $mensaje = 'Foto agregada a la galería.';
        } catch (RuntimeException $e) {
            $error = $e->getMessage();
        }
    }
}

// ---------- Eliminar foto de la galería ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_foto'])) {
    $idFoto = (int) $_POST['eliminar_foto'];
    $fotoAEliminar = array_filter(Galeria::listarTodas(), fn($f) => (int) $f['id'] === $idFoto);
    $fotoAEliminar = reset($fotoAEliminar);
    if ($fotoAEliminar) {
        ImagenUtil::borrar(__DIR__ . '/assets/img/galeria/', $fotoAEliminar['imagen']);
    }
    Galeria::eliminar($idFoto);
    $mensaje = 'Foto eliminada de la galería.';
}

$tituloPagina = 'Configuración del sitio · Panel LiliesNails';
$galeria = Galeria::listarTodas();

require __DIR__ . '/../includes/header_panel.php';
?>

<main class="panel-contenido">

  <?php if ($mensaje !== ''): ?><div class="mensaje-exito"><?= h($mensaje) ?></div><?php endif; ?>
  <?php if ($error !== ''): ?><div class="mensaje-error"><?= h($error) ?></div><?php endif; ?>

  <section class="bloque">
    <h2>Datos generales del sitio</h2>
    <form method="post" action="configuracion.php">
      <div class="campo">
        <label for="nombre_negocio">Nombre del negocio</label>
        <input type="text" id="nombre_negocio" name="nombre_negocio" value="<?= h(configSitio('nombre_negocio')) ?>" />
      </div>
      <div class="campo">
        <label for="telefono_whatsapp">WhatsApp (con código de país, solo números)</label>
        <input type="tel" id="telefono_whatsapp" name="telefono_whatsapp" placeholder="Ej: 573001234567" value="<?= h(configSitio('telefono_whatsapp')) ?>" />
      </div>
      <div class="campo">
        <label for="direccion">Dirección</label>
        <input type="text" id="direccion" name="direccion" value="<?= h(configSitio('direccion')) ?>" />
      </div>
      <div class="campo">
        <label for="horario">Horario de atención</label>
        <input type="text" id="horario" name="horario" value="<?= h(configSitio('horario')) ?>" />
      </div>
      <div class="campo">
        <label for="instagram">Usuario de Instagram (sin @)</label>
        <input type="text" id="instagram" name="instagram" value="<?= h(configSitio('instagram')) ?>" />
      </div>
      <div class="campo">
        <label for="tiktok">Usuario de TikTok (sin @)</label>
        <input type="text" id="tiktok" name="tiktok" value="<?= h(configSitio('tiktok')) ?>" />
      </div>
      <div class="campo">
        <label for="texto_bienvenida">Texto de bienvenida (portada)</label>
        <textarea id="texto_bienvenida" name="texto_bienvenida" rows="3"><?= h(configSitio('texto_bienvenida')) ?></textarea>
      </div>
      <button type="submit" name="guardar_config" value="1" class="btn btn-bloque">Guardar datos del sitio</button>
    </form>
  </section>

  <section class="bloque">
    <h2>Galería pública de diseños</h2>

    <form method="post" action="configuracion.php" enctype="multipart/form-data" class="form-crear">
      <h3>Agregar foto</h3>
      <div class="campo">
        <input type="file" name="foto" accept=".jpg,.jpeg,.png,.webp" required />
      </div>
      <div class="campo">
        <label for="titulo_foto">Título del diseño</label>
        <input type="text" id="titulo_foto" name="titulo_foto" placeholder="Ej: Francesita con flores" required />
      </div>
      <div class="campo">
        <label for="descripcion_foto">Descripción del diseño</label>
        <textarea id="descripcion_foto" name="descripcion_foto" rows="2" placeholder="Ej: Base nude con detalles florales pintados a mano y encapsulado en gel." required></textarea>
      </div>
      <button type="submit" name="agregar_foto" value="1" class="btn btn-bloque">Subir foto</button>
    </form>

    <?php if (empty($galeria)): ?>
      <p class="mensaje-vacio">Aún no has subido fotos a la galería.</p>
    <?php else: ?>
      <div class="lista-galeria-admin" style="margin-top:16px;">
        <?php foreach ($galeria as $foto): ?>
          <div class="fila-galeria-admin">
            <img src="assets/img/galeria/<?= h($foto['imagen']) ?>" alt="<?= h($foto['titulo'] ?: '') ?>" />
            <div class="texto-fila-galeria">
              <strong><?= h($foto['titulo'] ?: 'Sin título') ?></strong>
              <p><?= h($foto['descripcion'] ?: 'Sin descripción') ?></p>
            </div>
            <form method="post" action="configuracion.php" onsubmit="return confirm('¿Eliminar esta foto de la galería?');">
              <button type="submit" name="eliminar_foto" value="<?= (int) $foto['id'] ?>" class="btn-fila borrar" title="Eliminar"><i class="fa-solid fa-trash"></i></button>
            </form>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
  <section class="bloque">
    <h2><i class="fa-solid fa-bell"></i> Notificaciones de citas</h2>
    <?php if (ONESIGNAL_APP_ID === ''): ?>
      <p class="mensaje-vacio">
        Las notificaciones push todavía no están configuradas.<br>
        Sigue los pasos de la sección "Notificaciones" del README para activarlas (gratis, con OneSignal).
      </p>
    <?php else: ?>
      <p style="color:var(--texto-mas-suave); font-size:0.88rem; margin-bottom:14px;">
        Actívalas en este dispositivo para recibir un aviso cuando se acerque una cita
        (según el "Recordarme" que elijas al crear cada cita).
      </p>
      <button type="button" id="btn-activar-notis" class="btn btn-bloque">
        <i class="fa-solid fa-bell"></i> Activar notificaciones en este dispositivo
      </button>
      <p id="estado-notis" style="text-align:center; font-size:0.8rem; color:var(--texto-tenue); margin-top:10px;"></p>
      <button type="button" id="btn-probar-notis" class="btn btn-secundario btn-bloque" style="margin-top:10px;">
        <i class="fa-solid fa-vial"></i> Probar notificación ahora
      </button>
      <p id="estado-prueba-notis" style="text-align:center; font-size:0.8rem; color:var(--texto-tenue); margin-top:6px;"></p>

      <details style="margin-top:14px; font-size:0.75rem; color:var(--texto-tenue);">
        <summary style="cursor:pointer;">Diagnóstico (para revisar con soporte si algo no llega)</summary>
        <p style="margin-top:8px;">Dispositivos guardados en el servidor: <strong id="diag-total">—</strong></p>
        <p>Id de <em>este</em> dispositivo: <code id="diag-id-local" style="word-break:break-all;">—</code></p>
        <p id="diag-coincide"></p>
      </details>
    <?php endif; ?>
  </section>

  <section class="bloque">
    <h2><i class="fa-solid fa-file-zipper"></i> Fotos antiguas</h2>
    <p style="color:var(--texto-mas-suave); font-size:0.88rem; margin-bottom:14px;">
      ¿Subiste fotos antes de que esto comprimiera automático? Puedes pasarlas a WebP ahora.
    </p>
    <a href="reprocesar-imagenes.php" class="btn btn-secundario btn-bloque">
      <i class="fa-solid fa-bolt"></i> Comprimir fotos antiguas
    </a>
  </section>

</main>

<?php require __DIR__ . '/../includes/footer_panel.php'; ?>
<?php if (ONESIGNAL_APP_ID !== ''): ?>
<script>
  OneSignalDeferred.push(async function (OneSignal) {
    const btn = document.getElementById('btn-activar-notis');
    const estado = document.getElementById('estado-notis');
    if (!btn) return;

    // Guarda en el servidor el id real de ESTE dispositivo suscrito, para
    // poder mandarle las notificaciones directo a él (en vez de a un
    // "segmento" que puede no existir o no coincidir con este dispositivo).
    async function guardarSubscripcion() {
      const id = OneSignal.User.PushSubscription.id;
      const diagId = document.getElementById('diag-id-local');
      if (diagId) diagId.textContent = id || '(todavía no asignado)';
      if (!id) return;
      try {
        const res = await fetch('api/onesignal-subscripcion.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ subscriptionId: id }),
        });
        const resultado = await res.json();
        const diagTotal = document.getElementById('diag-total');
        const diagCoincide = document.getElementById('diag-coincide');
        if (diagTotal && resultado.total != null) diagTotal.textContent = resultado.total;
        if (diagCoincide) {
          diagCoincide.textContent = resultado.idGuardado === id
            ? '✅ Este dispositivo quedó guardado correctamente.'
            : '⚠️ Algo no coincidió al guardar — avísame si ves esto.';
        }
      } catch (e) {
        console.warn('No se pudo guardar el id de suscripción:', e);
      }
    }

    function actualizarEstado() {
      const activas = OneSignal.User.PushSubscription.optedIn;
      estado.textContent = activas ? '✅ Notificaciones activadas en este dispositivo' : 'Aún no están activadas en este dispositivo';
      btn.textContent = activas ? 'Ya están activadas aquí' : 'Activar notificaciones en este dispositivo';
      if (activas) guardarSubscripcion();
    }
    actualizarEstado();

    // El celular suele tardar más que la PC en terminar de crear la
    // suscripción (le toma un rato conseguir el "id" real). En vez de
    // adivinar cuánto esperar con un temporizador fijo, escuchamos el
    // evento que OneSignal dispara justo cuando el id queda listo —
    // así se guarda apenas exista, sin importar cuánto tarde.
    OneSignal.User.PushSubscription.addEventListener('change', guardarSubscripcion);

    btn.addEventListener('click', async () => {
      await OneSignal.User.PushSubscription.optIn();
      setTimeout(actualizarEstado, 800);
    });
  });

  document.getElementById('btn-probar-notis')?.addEventListener('click', async (e) => {
    const btn = e.currentTarget;
    const estado = document.getElementById('estado-prueba-notis');
    btn.disabled = true;
    estado.textContent = 'Enviando...';
    try {
      const res = await fetch('api/notificacion-prueba.php', { method: 'POST' });
      const resultado = await res.json();
      estado.textContent = resultado.ok
        ? '✅ Enviada — debería llegarte en unos segundos'
        : '⚠️ No se pudo enviar. Revisa que hayas activado las notificaciones en este dispositivo.';
    } catch (e) {
      estado.textContent = '⚠️ No se pudo enviar. Revisa tu conexión.';
    } finally {
      btn.disabled = false;
    }
  });
</script>
<?php endif; ?>
