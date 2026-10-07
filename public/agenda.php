<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
Auth::requerirLogin();

$tituloPagina = 'Agenda · Panel LiliesNails';
$servicios = Servicio::listarTodos();

require __DIR__ . '/../includes/header_panel.php';
?>

<main class="panel-contenido">

  <section class="bloque">
    <div class="agenda-tabs">
      <button type="button" data-tab="lista" class="activo">Lista</button>
      <button type="button" data-tab="calendario">Calendario</button>
    </div>

    <!-- ===== Vista de lista (día / semana) ===== -->
    <div id="vista-lista">
      <div class="agenda-tabs" style="margin-bottom: 14px;">
        <button type="button" data-rango="dia" class="activo">Día</button>
        <button type="button" data-rango="semana">Semana</button>
      </div>

      <div class="selector-fecha">
        <button type="button" id="btn-fecha-anterior" aria-label="Anterior"><i class="fa-solid fa-chevron-left"></i></button>
        <span class="fecha-actual" id="etiqueta-fecha">—</span>
        <button type="button" id="btn-fecha-siguiente" aria-label="Siguiente"><i class="fa-solid fa-chevron-right"></i></button>
      </div>

      <div id="lista-citas" class="lista-citas"></div>
      <div id="vacio-citas" class="mensaje-vacio oculto">No hay citas agendadas para este rango.</div>
    </div>

    <!-- ===== Vista de calendario mensual ===== -->
    <div id="vista-calendario" class="oculto">
      <div class="selector-fecha">
        <button type="button" id="btn-mes-anterior" aria-label="Mes anterior"><i class="fa-solid fa-chevron-left"></i></button>
        <span class="fecha-actual" id="etiqueta-mes">—</span>
        <button type="button" id="btn-mes-siguiente" aria-label="Mes siguiente"><i class="fa-solid fa-chevron-right"></i></button>
      </div>
      <div class="calendario-grid" id="calendario-grid"></div>

      <h3 style="font-size:0.9rem; margin-top:20px;">Citas del día seleccionado</h3>
      <div id="lista-citas-calendario" class="lista-citas"></div>
      <div id="vacio-citas-calendario" class="mensaje-vacio oculto">No hay citas para este día.</div>
    </div>
  </section>

  <button type="button" id="btn-nueva-cita" class="btn btn-bloque">
    <i class="fa-solid fa-plus"></i> Nueva cita
  </button>
</main>

<!-- ===== Modal crear / editar cita ===== -->
<div id="modal-cita" class="fondo-modal oculto">
  <div class="modal-cita">
    <h3 id="titulo-modal-cita">Nueva cita</h3>
    <form id="form-cita">
      <input type="hidden" id="citaId" value="" />
      <input type="hidden" id="citaClientaId" value="" />

      <div class="campo">
        <label for="citaClientaNombre">Nombre de la clienta</label>
        <input type="text" id="citaClientaNombre" required list="lista-clientas" autocomplete="off" />
        <datalist id="lista-clientas"></datalist>
      </div>
      <div class="campo">
        <label for="citaClientaTelefono">Teléfono / WhatsApp</label>
        <input type="tel" id="citaClientaTelefono" placeholder="Ej: 3001234567" />
      </div>
      <div class="campo">
        <label><i class="fa-solid fa-spa"></i> Servicios (puedes elegir varios)</label>
        <div class="contenedor-chips" id="contenedorChipsServiciosCita">
          <?php foreach ($servicios as $s): ?>
            <label class="chip-material" data-duracion="<?= (int) $s['tiempo_minutos'] ?>">
              <input type="checkbox" class="check-servicio-cita" value="<?= (int) $s['id'] ?>" />
              <?= h($s['nombre']) ?> · $<?= number_format($s['precio_final'], 2) ?>
            </label>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="campo">
        <label for="citaFecha">Fecha</label>
        <input type="date" id="citaFecha" required />
      </div>
      <div style="display:flex; gap:10px;">
        <div class="campo" style="flex:1;">
          <label for="citaHoraInicio">Hora inicio</label>
          <input type="time" id="citaHoraInicio" required />
        </div>
        <div class="campo" style="flex:1;">
          <label for="citaHoraFin">Hora fin</label>
          <input type="time" id="citaHoraFin" required />
        </div>
      </div>
      <div class="campo">
        <label for="citaEstado">Estado</label>
        <select id="citaEstado">
          <option value="pendiente">Pendiente</option>
          <option value="confirmada">Confirmada</option>
          <option value="completada">Completada</option>
          <option value="cancelada">Cancelada</option>
        </select>
      </div>
      <div class="campo">
        <label for="citaRecordatorio"><i class="fa-solid fa-bell"></i> Recordarme</label>
        <select id="citaRecordatorio">
          <option value="">Sin recordatorio</option>
          <option value="15">15 minutos antes</option>
          <option value="30">30 minutos antes</option>
          <option value="60">1 hora antes</option>
          <option value="120">2 horas antes</option>
          <option value="1440">1 día antes</option>
          <option value="2880">2 días antes</option>
        </select>
      </div>
      <div class="campo">
        <label for="citaNotas">Notas</label>
        <textarea id="citaNotas" rows="2" placeholder="Ej: pidió diseño con flores"></textarea>
      </div>

      <div class="modal-acciones">
        <button type="button" class="btn btn-secundario" id="btn-cancelar-cita">Cancelar</button>
        <button type="button" class="btn btn-peligro oculto" id="btn-borrar-cita">Eliminar</button>
        <button type="submit" class="btn">Guardar</button>
      </div>
    </form>
  </div>
</div>

<script src="assets/js/agenda.js"></script>

<?php require __DIR__ . '/../includes/footer_panel.php'; ?>
