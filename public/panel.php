<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
Auth::requerirLogin();

$vista = ($_GET['vista'] ?? 'servicios') === 'materiales' ? 'materiales' : 'servicios';
$tituloPagina = ($vista === 'materiales' ? 'Materiales' : 'Servicios') . ' · Panel LiliesNails';

require __DIR__ . '/../includes/header_panel.php';
?>

<main class="panel-contenido">

  <?php if ($vista === 'servicios'): ?>
    <section class="bloque" id="seccion-servicios">
      <h2>Crear y ver servicios</h2>

      <div class="form-crear">
        <h3>Nuevo servicio</h3>
        <div class="campo">
          <input type="text" id="nombreServicio" placeholder="Nombre (ej: Acrílicas Full)" />
        </div>
        <div class="campo">
          <input type="number" id="gananciaHora" min="0" placeholder="Ganancia deseada / hora ($)" />
        </div>
        <div class="campo">
          <input type="number" id="tiempoTrabajo" min="0" step="15" placeholder="Tiempo de trabajo (minutos)" />
        </div>
        <div class="campo">
          <input type="number" id="margenDeseado" min="0" placeholder="Margen de ganancia deseado (%)" />
        </div>

        <button type="button" class="btn-desplegar" id="btn-desplegar-materiales">
          <span>Materiales a usar</span>
          <i class="fa-solid fa-chevron-down"></i>
        </button>
        <div id="contenedor-chips" class="contenedor-chips oculto"></div>

        <label class="campo" style="display:flex; align-items:center; gap:8px; font-weight:600;">
          <input type="checkbox" id="visiblePublico" style="width:auto;" />
          Mostrar este servicio en el catálogo público
        </label>
        <div class="campo oculto" id="campoDescripcionPublica">
          <label for="descripcionPublica">Descripción para clientas</label>
          <textarea id="descripcionPublica" rows="2" placeholder="Ej: Uñas acrílicas con acabado brillante, duran hasta 3 semanas."></textarea>

          <label for="imagenServicio" style="margin-top:12px;">Foto del servicio</label>
          <div id="previewImagenServicio" class="oculto" style="margin-bottom:8px;">
            <img id="imgPreviewServicio" src="" alt="" style="width:100%; max-width:220px; border-radius:12px; display:block; margin-bottom:6px;" />
            <button type="button" id="btn-quitar-imagen" class="btn btn-peligro" style="font-size:0.72rem; padding:8px 14px;">
              <i class="fa-solid fa-trash"></i> Quitar foto
            </button>
          </div>
          <input type="file" id="imagenServicio" accept="image/png,image/jpeg,image/webp" />
          <small style="color:var(--texto-tenue); font-size:0.75rem;">JPG, PNG o WEBP, máx. 4 MB. Se guarda al guardar el servicio.</small>
        </div>

        <button type="button" id="btn-guardar-servicio" class="btn btn-bloque">Calcular y guardar servicio</button>
      </div>

      <h3 style="font-size:0.95rem;">Lista de servicios (menú)</h3>
      <div class="buscador">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" id="buscarServicio" placeholder="Buscar servicio..." />
      </div>
      <div class="orden-container">
        <span>Ordenar:</span>
        <button type="button" data-orden="nombre" data-tipo="servicios"><i class="fa-solid fa-arrow-down-a-z"></i></button>
        <button type="button" data-orden="precio" data-tipo="servicios"><i class="fa-solid fa-hand-holding-dollar"></i></button>
        <button type="button" data-orden="fecha" data-tipo="servicios"><i class="fa-solid fa-calendar-days"></i></button>
      </div>
      <div class="tabla-scroll">
        <table>
          <thead>
            <tr><th></th><th>Servicio</th><th>%</th><th>Precio final</th><th>Acción</th></tr>
          </thead>
          <tbody id="cuerpo-servicios"></tbody>
        </table>
      </div>
      <div id="vacio-servicios" class="mensaje-vacio oculto">Aún no tienes servicios guardados.</div>
    </section>
  <?php else: ?>
    <section class="bloque" id="seccion-inventario">
      <h2>Inventario de materiales</h2>
      <div class="campo">
        <input type="text" id="nombreMat" placeholder="Material (ej: Top Coat)" />
      </div>
      <div class="campo">
        <input type="number" id="precioMat" min="0" step="0.01" placeholder="Precio pagado ($)" />
      </div>
      <div class="campo">
        <input type="number" id="usoMat" min="1" placeholder="¿Cuántas veces rinde?" />
      </div>
      <button type="button" id="btn-agregar" class="btn btn-bloque">Agregar al inventario</button>

      <div class="buscador">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" id="buscarMaterial" placeholder="Buscar material..." />
      </div>
      <div class="orden-container">
        <span>Ordenar:</span>
        <button type="button" data-orden="nombre" data-tipo="materiales"><i class="fa-solid fa-arrow-down-a-z"></i></button>
        <button type="button" data-orden="precio" data-tipo="materiales"><i class="fa-solid fa-hand-holding-dollar"></i></button>
        <button type="button" data-orden="fecha" data-tipo="materiales"><i class="fa-solid fa-calendar-days"></i></button>
      </div>
      <div class="tabla-scroll">
        <table>
          <thead>
            <tr><th>Nombre</th><th>Precio</th><th>Rinde</th><th>Costo/uso</th><th>Acción</th></tr>
          </thead>
          <tbody id="cuerpo-tabla"></tbody>
        </table>
      </div>
      <div id="vacio-materiales" class="mensaje-vacio oculto">Aún no tienes materiales registrados.</div>
    </section>
  <?php endif; ?>

  <section class="bloque">
    <div class="seccion-respaldo">
      <h3 style="font-size:0.95rem;">Respaldo de seguridad</h3>
      <button type="button" id="btn-exportar" class="btn btn-exito btn-bloque">
        <i class="fa-solid fa-download"></i> Descargar respaldo (.json)
      </button>
      <label for="importarArchivo" class="btn btn-morado btn-bloque" style="cursor:pointer;">
        <i class="fa-solid fa-upload"></i> Subir respaldo
      </label>
      <input type="file" id="importarArchivo" accept="application/json" class="oculto" />
    </div>
  </section>
</main>

<script>window.VISTA_ACTUAL = <?= json_encode($vista) ?>;</script>
<script src="assets/js/panel-materiales.js"></script>
<script src="assets/js/panel-servicios.js"></script>
<script src="assets/js/respaldo.js"></script>

<?php require __DIR__ . '/../includes/footer_panel.php'; ?>
