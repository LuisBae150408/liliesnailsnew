<?php $paginaActual = basename($_SERVER['SCRIPT_NAME']); ?>
<nav class="nav-inferior">
  <a href="panel.php?vista=servicios" class="<?= $paginaActual === 'panel.php' && ($_GET['vista'] ?? 'servicios') === 'servicios' ? 'activo' : '' ?>">
    <i class="fa-solid fa-spa"></i> Servicios
  </a>
  <a href="panel.php?vista=materiales" class="<?= $paginaActual === 'panel.php' && ($_GET['vista'] ?? '') === 'materiales' ? 'activo' : '' ?>">
    <i class="fa-solid fa-boxes-stacked"></i> Materiales
  </a>
  <a href="agenda.php" class="<?= $paginaActual === 'agenda.php' ? 'activo' : '' ?>">
    <i class="fa-solid fa-calendar-days"></i> Agenda
  </a>
  <a href="configuracion.php" class="<?= $paginaActual === 'configuracion.php' ? 'activo' : '' ?>">
    <i class="fa-solid fa-gear"></i> Sitio
  </a>
</nav>

<script src="assets/js/recordatorios-panel.js"></script>
<script src="assets/js/sw-registro.js"></script>
</body>
</html>
