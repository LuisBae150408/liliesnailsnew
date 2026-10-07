// agenda.js — Agenda de citas: vista de lista (día/semana) y calendario mensual
(function () {
  const NOMBRES_DIA = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb']; // usado con .getDay() (0=domingo)
  const NOMBRES_DIA_CALENDARIO = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom']; // el grid del mes arranca en lunes
  const NOMBRES_MES = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

  let vistaActual = 'lista';
  let rangoActual = 'dia';
  let fechaReferencia = new Date();
  let mesReferencia = new Date();
  let fechaSeleccionadaCalendario = formatoISO(new Date());
  let listaClientas = [];

  // ---------- Utilidades de fecha ----------
  function formatoISO(date) {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
  }

  function sumarDias(date, dias) {
    const nueva = new Date(date);
    nueva.setDate(nueva.getDate() + dias);
    return nueva;
  }

  function inicioSemana(date) {
    const nueva = new Date(date);
    const diaSemana = nueva.getDay(); // 0=domingo
    const diff = diaSemana === 0 ? -6 : 1 - diaSemana; // arranca en lunes
    return sumarDias(nueva, diff);
  }

  function formatoHora(hhmmss) {
    const [h, m] = hhmmss.split(':').map(Number);
    const periodo = h >= 12 ? 'pm' : 'am';
    const h12 = h % 12 === 0 ? 12 : h % 12;
    return `${h12}:${String(m).padStart(2, '0')} ${periodo}`;
  }

  function escapar(texto) {
    const div = document.createElement('div');
    div.textContent = texto || '';
    return div.innerHTML;
  }

  // Nota: el recordatorio push (OneSignal) se programa/cancela solo,
  // del lado del servidor, cada vez que se crea/edita/borra una cita
  // (ver src/Cita.php). Antes esto se hacía desde aquí con la REST
  // API Key de OneSignal expuesta en el navegador — un riesgo de
  // seguridad real, además de una espera extra al guardar. Ahora el
  // navegador ni se entera: solo manda los datos de la cita.

  // ---------- Pestañas Lista / Calendario ----------
  document.querySelectorAll('.agenda-tabs [data-tab]').forEach((btn) => {
    btn.addEventListener('click', () => {
      vistaActual = btn.dataset.tab;
      document.querySelectorAll('.agenda-tabs [data-tab]').forEach((b) => b.classList.toggle('activo', b === btn));
      document.getElementById('vista-lista').classList.toggle('oculto', vistaActual !== 'lista');
      document.getElementById('vista-calendario').classList.toggle('oculto', vistaActual !== 'calendario');
      if (vistaActual === 'calendario') cargarCalendario();
      else cargarListaCitas();
    });
  });

  document.querySelectorAll('.agenda-tabs [data-rango]').forEach((btn) => {
    btn.addEventListener('click', () => {
      rangoActual = btn.dataset.rango;
      document.querySelectorAll('.agenda-tabs [data-rango]').forEach((b) => b.classList.toggle('activo', b === btn));
      cargarListaCitas();
    });
  });

  // ---------- Vista de lista (día / semana) ----------
  function rangoDeFechas() {
    if (rangoActual === 'semana') {
      const inicio = inicioSemana(fechaReferencia);
      const fin = sumarDias(inicio, 6);
      return { desde: formatoISO(inicio), hasta: formatoISO(fin) };
    }
    const iso = formatoISO(fechaReferencia);
    return { desde: iso, hasta: iso };
  }

  function actualizarEtiquetaFecha() {
    const etiqueta = document.getElementById('etiqueta-fecha');
    if (rangoActual === 'semana') {
      const inicio = inicioSemana(fechaReferencia);
      const fin = sumarDias(inicio, 6);
      etiqueta.textContent = `${inicio.getDate()} ${NOMBRES_MES[inicio.getMonth()].slice(0, 3)} – ${fin.getDate()} ${NOMBRES_MES[fin.getMonth()].slice(0, 3)}`;
    } else {
      etiqueta.textContent = `${NOMBRES_DIA[fechaReferencia.getDay()]}, ${fechaReferencia.getDate()} ${NOMBRES_MES[fechaReferencia.getMonth()]}`;
    }
  }

  async function cargarListaCitas() {
    actualizarEtiquetaFecha();
    const { desde, hasta } = rangoDeFechas();
    const res = await fetch(`api/citas.php?desde=${desde}&hasta=${hasta}`);
    const citas = await res.json();
    renderListaCitas(citas, 'lista-citas', 'vacio-citas', rangoActual === 'semana');
  }

  function renderListaCitas(citas, idContenedor, idVacio, mostrarFecha = false) {
    const contenedor = document.getElementById(idContenedor);
    const vacio = document.getElementById(idVacio);

    if (!citas.length) {
      contenedor.innerHTML = '';
      vacio.classList.remove('oculto');
      return;
    }
    vacio.classList.add('oculto');

    contenedor.innerHTML = citas.map((c) => `
      <div class="cita-item estado-${c.estado}" data-id="${c.id}">
        <div class="hora">
          ${mostrarFecha ? `${c.fecha.slice(8, 10)}/${c.fecha.slice(5, 7)}<br>` : ''}
          ${formatoHora(c.hora_inicio)}
        </div>
        <div class="info">
          <div class="nombre">${escapar(c.clienta_nombre)}</div>
          <div class="servicio">${c.servicios && c.servicios.length ? escapar(c.servicios.map((s) => s.nombre).join(', ')) : 'Sin servicio específico'}</div>
          ${c.notas ? `<div class="notas">${escapar(c.notas)}</div>` : ''}
          <span class="badge badge-${c.estado}">${c.estado}</span>
        </div>
        <div class="acciones-cita">
          ${c.estado === 'pendiente' || c.estado === 'confirmada' ? `
          <button class="btn-fila estado-rapido completar" data-cambiar-estado="${c.id}" data-nuevo-estado="completada" title="Marcar como completada"><i class="fa-solid fa-check"></i></button>
          <button class="btn-fila estado-rapido cancelar" data-cambiar-estado="${c.id}" data-nuevo-estado="cancelada" title="Cancelar cita"><i class="fa-solid fa-xmark"></i></button>
          ` : ''}
          <button class="btn-fila editar" data-editar-cita="${c.id}" title="Editar"><i class="fa-solid fa-pen"></i></button>
        </div>
      </div>`).join('');

    contenedor.querySelectorAll('[data-editar-cita]').forEach((btn) => {
      btn.addEventListener('click', () => abrirModalEditarCita(btn.dataset.editarCita));
    });

    contenedor.querySelectorAll('[data-cambiar-estado]').forEach((btn) => {
      btn.addEventListener('click', async () => {
        const nuevoEstado = btn.dataset.nuevoEstado;
        if (nuevoEstado === 'cancelada' && !confirm('¿Cancelar esta cita?')) return;
        await fetch(`api/citas.php?id=${btn.dataset.cambiarEstado}`, {
          method: 'PUT',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ soloEstado: true, estado: nuevoEstado }),
        });
        refrescarVistaActual();
      });
    });
  }

  document.getElementById('btn-fecha-anterior').addEventListener('click', () => {
    fechaReferencia = sumarDias(fechaReferencia, rangoActual === 'semana' ? -7 : -1);
    cargarListaCitas();
  });
  document.getElementById('btn-fecha-siguiente').addEventListener('click', () => {
    fechaReferencia = sumarDias(fechaReferencia, rangoActual === 'semana' ? 7 : 1);
    cargarListaCitas();
  });

  // ---------- Vista de calendario mensual ----------
  async function cargarCalendario() {
    const anio = mesReferencia.getFullYear();
    const mes = mesReferencia.getMonth();
    document.getElementById('etiqueta-mes').textContent = `${NOMBRES_MES[mes]} ${anio}`;

    const primerDiaMes = new Date(anio, mes, 1);
    const ultimoDiaMes = new Date(anio, mes + 1, 0);
    const inicioGrid = sumarDias(primerDiaMes, primerDiaMes.getDay() === 0 ? -6 : 1 - primerDiaMes.getDay());
    const finGrid = sumarDias(inicioGrid, 41); // 6 semanas completas

    const res = await fetch(`api/citas.php?desde=${formatoISO(inicioGrid)}&hasta=${formatoISO(finGrid)}&conteo=1`);
    const conteos = await res.json();

    const grid = document.getElementById('calendario-grid');
    let html = NOMBRES_DIA_CALENDARIO.map((n) => `<div class="dia-nombre">${n}</div>`).join('');

    const hoyISO = formatoISO(new Date());
    for (let i = 0; i < 42; i++) {
      const dia = sumarDias(inicioGrid, i);
      const iso = formatoISO(dia);
      const fueraDeMes = dia.getMonth() !== mes;
      const clases = ['dia-celda'];
      if (fueraDeMes) clases.push('fuera-de-mes');
      if (iso === hoyISO) clases.push('hoy');
      if (iso === fechaSeleccionadaCalendario) clases.push('seleccionado');

      html += `<div class="${clases.join(' ')}" data-fecha="${iso}">
        ${dia.getDate()}
        ${conteos[iso] ? '<span class="punto-citas"></span>' : ''}
      </div>`;
    }
    grid.innerHTML = html;

    grid.querySelectorAll('[data-fecha]').forEach((celda) => {
      celda.addEventListener('click', () => {
        fechaSeleccionadaCalendario = celda.dataset.fecha;
        grid.querySelectorAll('.dia-celda').forEach((c) => c.classList.remove('seleccionado'));
        celda.classList.add('seleccionado');
        cargarCitasDelDiaCalendario();
      });
    });

    cargarCitasDelDiaCalendario();
  }

  async function cargarCitasDelDiaCalendario() {
    const res = await fetch(`api/citas.php?desde=${fechaSeleccionadaCalendario}&hasta=${fechaSeleccionadaCalendario}`);
    const citas = await res.json();
    renderListaCitas(citas, 'lista-citas-calendario', 'vacio-citas-calendario');
  }

  document.getElementById('btn-mes-anterior').addEventListener('click', () => {
    mesReferencia = new Date(mesReferencia.getFullYear(), mesReferencia.getMonth() - 1, 1);
    cargarCalendario();
  });
  document.getElementById('btn-mes-siguiente').addEventListener('click', () => {
    mesReferencia = new Date(mesReferencia.getFullYear(), mesReferencia.getMonth() + 1, 1);
    cargarCalendario();
  });

  // ---------- Modal: crear / editar cita ----------
  const modal = document.getElementById('modal-cita');
  const form = document.getElementById('form-cita');
  const btnBorrarCita = document.getElementById('btn-borrar-cita');

  async function cargarClientas() {
    const res = await fetch('api/clientas.php');
    listaClientas = await res.json();
    const datalist = document.getElementById('lista-clientas');
    datalist.innerHTML = listaClientas.map((c) => `<option value="${escapar(c.nombre)}">`).join('');
  }

  document.getElementById('citaClientaNombre').addEventListener('input', (e) => {
    const coincidencia = listaClientas.find((c) => c.nombre === e.target.value);
    document.getElementById('citaClientaId').value = coincidencia ? coincidencia.id : '';
    if (coincidencia && coincidencia.telefono) {
      document.getElementById('citaClientaTelefono').value = coincidencia.telefono;
    }
  });

  // Autocompletar hora de fin sumando la duración de TODOS los servicios marcados
  const contenedorChipsCita = document.getElementById('contenedorChipsServiciosCita');
  contenedorChipsCita.addEventListener('change', (e) => {
    const chip = e.target.closest('.chip-material');
    if (chip) chip.classList.toggle('activo', e.target.checked);

    const duracionTotal = Array.from(contenedorChipsCita.querySelectorAll('.check-servicio-cita:checked'))
      .reduce((suma, chk) => suma + parseInt(chk.closest('.chip-material')?.dataset.duracion || '0', 10), 0);
    const horaInicio = document.getElementById('citaHoraInicio').value;
    if (duracionTotal > 0 && horaInicio) {
      const [h, m] = horaInicio.split(':').map(Number);
      const fin = new Date(2000, 0, 1, h, m + duracionTotal);
      document.getElementById('citaHoraFin').value = `${String(fin.getHours()).padStart(2, '0')}:${String(fin.getMinutes()).padStart(2, '0')}`;
    }
  });

  function abrirModal(titulo) {
    document.getElementById('titulo-modal-cita').textContent = titulo;
    modal.classList.remove('oculto');
  }
  function cerrarModal() {
    modal.classList.add('oculto');
    form.reset();
    document.getElementById('citaId').value = '';
    document.getElementById('citaClientaId').value = '';
    contenedorChipsCita.querySelectorAll('.check-servicio-cita').forEach((chk) => { chk.checked = false; });
    contenedorChipsCita.querySelectorAll('.chip-material').forEach((chip) => chip.classList.remove('activo'));
    btnBorrarCita.classList.add('oculto');
  }

  document.getElementById('btn-nueva-cita').addEventListener('click', () => {
    cerrarModal();
    const fechaBase = vistaActual === 'calendario' ? fechaSeleccionadaCalendario : formatoISO(fechaReferencia);
    document.getElementById('citaFecha').value = fechaBase;
    abrirModal('Nueva cita');
  });

  document.getElementById('btn-cancelar-cita').addEventListener('click', cerrarModal);

  async function abrirModalEditarCita(id) {
    const res = await fetch(`api/citas.php?id=${id}`);
    if (!res.ok) return;
    const cita = await res.json();

    document.getElementById('citaId').value = cita.id;
    document.getElementById('citaClientaId').value = cita.clienta_id;
    document.getElementById('citaClientaNombre').value = cita.clienta_nombre;
    document.getElementById('citaClientaTelefono').value = cita.clienta_telefono || '';
    const idsServiciosCita = (cita.servicios || []).map((s) => s.id);
    contenedorChipsCita.querySelectorAll('.check-servicio-cita').forEach((chk) => {
      const marcado = idsServiciosCita.includes(Number(chk.value));
      chk.checked = marcado;
      chk.closest('.chip-material').classList.toggle('activo', marcado);
    });
    document.getElementById('citaFecha').value = cita.fecha;
    document.getElementById('citaHoraInicio').value = cita.hora_inicio.slice(0, 5);
    document.getElementById('citaHoraFin').value = cita.hora_fin.slice(0, 5);
    document.getElementById('citaEstado').value = cita.estado;
    document.getElementById('citaNotas').value = cita.notas || '';
    document.getElementById('citaRecordatorio').value = cita.recordatorio_minutos || '';

    btnBorrarCita.classList.remove('oculto');
    abrirModal('Editar cita');
  }

  btnBorrarCita.addEventListener('click', async () => {
    const id = document.getElementById('citaId').value;
    if (!id || !confirm('¿Eliminar esta cita?')) return;
    await fetch(`api/citas.php?id=${id}`, { method: 'DELETE' });
    cerrarModal();
    refrescarVistaActual();
  });

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const id = document.getElementById('citaId').value;
    const datos = {
      clientaId: document.getElementById('citaClientaId').value || null,
      clientaNombre: document.getElementById('citaClientaNombre').value.trim(),
      clientaTelefono: document.getElementById('citaClientaTelefono').value.trim(),
      servicioIds: Array.from(contenedorChipsCita.querySelectorAll('.check-servicio-cita:checked')).map((chk) => Number(chk.value)),
      fecha: document.getElementById('citaFecha').value,
      horaInicio: document.getElementById('citaHoraInicio').value,
      horaFin: document.getElementById('citaHoraFin').value,
      estado: document.getElementById('citaEstado').value,
      notas: document.getElementById('citaNotas').value.trim(),
      recordatorioMinutos: parseInt(document.getElementById('citaRecordatorio').value, 10) || null,
    };

    const url = id ? `api/citas.php?id=${id}` : 'api/citas.php';
    const metodo = id ? 'PUT' : 'POST';
    const res = await fetch(url, { method: metodo, headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(datos) });
    const resultado = await res.json();

    if (!res.ok) {
      alert(resultado.error || 'No se pudo guardar la cita.');
      return;
    }

    // El recordatorio push (programar/cancelar/reprogramar) ya quedó
    // resuelto en el servidor como parte de este mismo guardado.
    cerrarModal();
    await cargarClientas();
    refrescarVistaActual();
  });

  function refrescarVistaActual() {
    if (vistaActual === 'calendario') cargarCalendario();
    else cargarListaCitas();
  }

  // ---------- Inicio ----------
  cargarClientas();
  cargarListaCitas();
})();
