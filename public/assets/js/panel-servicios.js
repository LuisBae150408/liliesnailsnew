// panel-servicios.js — Calculadora y catálogo de servicios (habla con api/servicios.php)
(function () {
  const cuerpoServicios = document.getElementById('cuerpo-servicios');
  if (!cuerpoServicios) return; // esta vista no está activa

  const inputNombre = document.getElementById('nombreServicio');
  const inputGananciaHora = document.getElementById('gananciaHora');
  const inputTiempo = document.getElementById('tiempoTrabajo');
  const inputMargen = document.getElementById('margenDeseado');
  const btnDesplegar = document.getElementById('btn-desplegar-materiales');
  const contenedorChips = document.getElementById('contenedor-chips');
  const btnGuardar = document.getElementById('btn-guardar-servicio');
  const inputBusqueda = document.getElementById('buscarServicio');
  const vacioServicios = document.getElementById('vacio-servicios');
  const checkVisible = document.getElementById('visiblePublico');
  const campoDescripcion = document.getElementById('campoDescripcionPublica');
  const inputDescripcion = document.getElementById('descripcionPublica');
  const inputImagen = document.getElementById('imagenServicio');
  const previewImagen = document.getElementById('previewImagenServicio');
  const imgPreview = document.getElementById('imgPreviewServicio');
  const btnQuitarImagen = document.getElementById('btn-quitar-imagen');

  let imagenActualServicio = null; // nombre de archivo ya guardado en el servidor (si existe)
  let quitarImagenAlGuardar = false;

  let listaServicios = [];
  let listaMateriales = [];
  let idServicioEditando = null;
  let limiteServicios = 5;
  let ordenActual = null;

  document.querySelectorAll('#seccion-servicios input[type="number"]').forEach((input) => {
    input.addEventListener('keydown', (e) => {
      if (e.key === '-' || e.key === 'e' || e.key === '+') e.preventDefault();
    });
  });

  btnDesplegar.addEventListener('click', () => {
    contenedorChips.classList.toggle('oculto');
    btnDesplegar.classList.toggle('abierto');
  });

  checkVisible.addEventListener('change', () => {
    campoDescripcion.classList.toggle('oculto', !checkVisible.checked);
  });

  inputImagen.addEventListener('change', () => {
    const archivo = inputImagen.files[0];
    if (!archivo) return;
    quitarImagenAlGuardar = false;
    const lector = new FileReader();
    lector.onload = (e) => {
      imgPreview.src = e.target.result;
      previewImagen.classList.remove('oculto');
    };
    lector.readAsDataURL(archivo);
  });

  btnQuitarImagen.addEventListener('click', () => {
    inputImagen.value = '';
    imgPreview.src = '';
    previewImagen.classList.add('oculto');
    quitarImagenAlGuardar = true;
  });

  async function cargarMateriales() {
    const res = await fetch('api/materiales.php');
    listaMateriales = await res.json();
    renderChips();
  }

  async function cargarServicios() {
    const res = await fetch('api/servicios.php');
    listaServicios = await res.json();
    render();
  }

  function renderChips(idsSeleccionados = []) {
    contenedorChips.innerHTML = listaMateriales.map((mat) => `
      <label class="chip-material ${idsSeleccionados.includes(mat.id) ? 'activo' : ''}">
        <input type="checkbox" class="check-material" value="${mat.id}" ${idsSeleccionados.includes(mat.id) ? 'checked' : ''} />
        ${escapar(mat.nombre)} (+$${Number(mat.costo_uso).toFixed(2)})
      </label>`).join('');

    contenedorChips.querySelectorAll('.check-material').forEach((chk) => {
      chk.addEventListener('change', () => {
        chk.closest('.chip-material').classList.toggle('activo', chk.checked);
      });
    });
  }

  function serviciosFiltrados() {
    const filtro = (inputBusqueda.value || '').toLowerCase();
    let lista = listaServicios.filter((s) => s.nombre.toLowerCase().includes(filtro));
    if (ordenActual === 'nombre') {
      lista = [...lista].sort((a, b) => a.nombre.localeCompare(b.nombre));
    } else if (ordenActual === 'precio') {
      lista = [...lista].sort((a, b) => parseFloat(b.precio_final) - parseFloat(a.precio_final));
    } else if (ordenActual === 'fecha') {
      lista = [...lista].sort((a, b) => b.id - a.id);
    }
    return lista;
  }

  function render() {
    const filtrados = serviciosFiltrados();
    const visibles = filtrados.slice(0, limiteServicios);
    const sePuedeReordenar = ordenActual === null && inputBusqueda.value.trim() === '';

    cuerpoServicios.innerHTML = visibles.map((serv) => `
      <tr>
        <td>${serv.imagen ? `<img src="assets/img/servicios/${escapar(serv.imagen)}" alt="" style="width:34px;height:34px;border-radius:8px;object-fit:cover;display:block;" />` : '<i class="fa-solid fa-spa" style="color:var(--lila-oscuro);"></i>'}</td>
        <td>${escapar(serv.nombre)} ${Number(serv.visible_publico) ? '<i class="fa-solid fa-eye" title="Visible en el catálogo público" style="color:var(--exito);"></i>' : ''}</td>
        <td>${Number(serv.margen_porcentaje)}%</td>
        <td>$${Number(serv.precio_final).toFixed(2)}</td>
        <td class="acciones">
          ${sePuedeReordenar ? `
          <button class="btn-fila mover-orden" data-mover="${serv.id}" data-direccion="arriba" title="Subir en el catálogo"><i class="fa-solid fa-arrow-up"></i></button>
          <button class="btn-fila mover-orden" data-mover="${serv.id}" data-direccion="abajo" title="Bajar en el catálogo"><i class="fa-solid fa-arrow-down"></i></button>
          ` : ''}
          <button class="btn-fila clonar" data-clonar="${serv.id}" title="Crear nuevo a partir de este"><i class="fa-solid fa-copy"></i></button>
          <button class="btn-fila editar" data-editar="${serv.id}" title="Editar"><i class="fa-solid fa-pen"></i></button>
          <button class="btn-fila borrar" data-borrar="${serv.id}" title="Eliminar"><i class="fa-solid fa-trash"></i></button>
        </td>
      </tr>`).join('');

    if (!sePuedeReordenar && !document.getElementById('aviso-orden-bloqueado') && (ordenActual !== null || inputBusqueda.value.trim() !== '')) {
      const aviso = document.createElement('p');
      aviso.id = 'aviso-orden-bloqueado';
      aviso.style.cssText = 'font-size:0.75rem;color:var(--texto-tenue);text-align:center;margin-top:8px;';
      aviso.textContent = 'Quita el filtro de búsqueda/orden para poder mover servicios de lugar en el catálogo.';
      cuerpoServicios.closest('.tabla-scroll').after(aviso);
    } else if (sePuedeReordenar) {
      document.getElementById('aviso-orden-bloqueado')?.remove();
    }

    vacioServicios.classList.toggle('oculto', listaServicios.length !== 0);

    const btnViejo = document.getElementById('btn-ver-mas-servicios');
    if (btnViejo) btnViejo.remove();
    if (filtrados.length > limiteServicios) {
      const boton = document.createElement('button');
      boton.id = 'btn-ver-mas-servicios';
      boton.className = 'btn-ver-mas';
      boton.innerHTML = 'Ver más servicios <i class="fa-solid fa-angles-down"></i>';
      boton.addEventListener('click', () => { limiteServicios += 5; render(); });
      cuerpoServicios.closest('.tabla-scroll').after(boton);
    }
  }

  function escapar(texto) {
    const div = document.createElement('div');
    div.textContent = texto;
    return div.innerHTML;
  }

  function limpiarFormulario() {
    idServicioEditando = null;
    inputNombre.value = '';
    inputTiempo.value = '';
    inputMargen.value = '';
    inputGananciaHora.value = '';
    checkVisible.checked = false;
    inputDescripcion.value = '';
    campoDescripcion.classList.add('oculto');
    inputImagen.value = '';
    imgPreview.src = '';
    previewImagen.classList.add('oculto');
    imagenActualServicio = null;
    quitarImagenAlGuardar = false;
    renderChips();
    btnGuardar.textContent = 'Calcular y guardar servicio';
  }

  btnGuardar.addEventListener('click', async () => {
    const nombre = inputNombre.value.trim();
    const gananciaHora = Math.abs(parseFloat(inputGananciaHora.value)) || 0;
    const tiempoMinutos = Math.abs(parseFloat(inputTiempo.value)) || 0;
    const margenPorcentaje = Math.abs(parseFloat(inputMargen.value)) || 0;
    const materialesIds = Array.from(contenedorChips.querySelectorAll('.check-material:checked')).map((c) => Number(c.value));

    if (!nombre) {
      alert('Ponle un nombre al servicio');
      return;
    }

    const cuerpo = JSON.stringify({
      nombre, gananciaHora, tiempoMinutos, margenPorcentaje, materialesIds,
      visiblePublico: checkVisible.checked,
      descripcionPublica: inputDescripcion.value.trim(),
    });

    const url = idServicioEditando ? `api/servicios.php?id=${idServicioEditando}` : 'api/servicios.php';
    const metodo = idServicioEditando ? 'PUT' : 'POST';

    const res = await fetch(url, { method: metodo, headers: { 'Content-Type': 'application/json' }, body: cuerpo });
    const resultado = await res.json();
    if (!res.ok) {
      alert(resultado.error || 'No se pudo guardar el servicio.');
      return;
    }

    const idGuardado = resultado.id;

    // Segundo paso: subir la nueva foto o quitar la existente, si aplica
    const archivo = inputImagen.files[0];
    if (archivo) {
      const formData = new FormData();
      formData.append('servicioId', idGuardado);
      formData.append('imagen', archivo);
      const resImagen = await fetch('api/subir-imagen-servicio.php', { method: 'POST', body: formData });
      const resultadoImagen = await resImagen.json();
      if (!resImagen.ok) {
        alert('El servicio se guardó, pero la foto no se pudo subir: ' + (resultadoImagen.error || ''));
      }
    } else if (quitarImagenAlGuardar && imagenActualServicio) {
      await fetch(`api/subir-imagen-servicio.php?servicioId=${idGuardado}`, { method: 'DELETE' });
    }

    limpiarFormulario();
    await cargarServicios();
  });

  function cargarServicioEnFormulario(serv, comoCopia) {
    inputNombre.value = comoCopia ? `${serv.nombre} (Copia)` : serv.nombre;
    inputGananciaHora.value = serv.ganancia_hora;
    inputTiempo.value = serv.tiempo_minutos;
    inputMargen.value = serv.margen_porcentaje;
    checkVisible.checked = !!Number(serv.visible_publico);
    inputDescripcion.value = serv.descripcion_publica || '';
    campoDescripcion.classList.toggle('oculto', !checkVisible.checked);
    renderChips(serv.materiales_ids || []);

    inputImagen.value = '';
    quitarImagenAlGuardar = false;
    if (!comoCopia && serv.imagen) {
      imagenActualServicio = serv.imagen;
      imgPreview.src = `assets/img/servicios/${serv.imagen}`;
      previewImagen.classList.remove('oculto');
    } else {
      imagenActualServicio = null;
      imgPreview.src = '';
      previewImagen.classList.add('oculto');
    }

    idServicioEditando = comoCopia ? null : serv.id;
    btnGuardar.textContent = comoCopia ? 'Guardar como nuevo servicio' : 'Actualizar servicio';

    contenedorChips.classList.remove('oculto');
    btnDesplegar.classList.add('abierto');
    inputNombre.scrollIntoView({ behavior: 'smooth', block: 'center' });
    inputNombre.focus();
  }

  cuerpoServicios.addEventListener('click', async (e) => {
    const btnEditar = e.target.closest('[data-editar]');
    const btnClonar = e.target.closest('[data-clonar]');
    const btnBorrar = e.target.closest('[data-borrar]');
    const btnMover = e.target.closest('[data-mover]');

    if (btnMover) {
      await fetch(`api/servicios.php?id=${btnMover.dataset.mover}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ moverOrden: true, direccion: btnMover.dataset.direccion }),
      });
      await cargarServicios();
      return;
    }

    if (btnEditar) {
      const serv = listaServicios.find((s) => s.id == btnEditar.dataset.editar);
      if (serv) cargarServicioEnFormulario(serv, false);
    }

    if (btnClonar) {
      const serv = listaServicios.find((s) => s.id == btnClonar.dataset.clonar);
      if (serv) cargarServicioEnFormulario(serv, true);
    }

    if (btnBorrar) {
      if (!confirm('¿Seguro que quieres eliminar este servicio del menú?')) return;
      await fetch(`api/servicios.php?id=${btnBorrar.dataset.borrar}`, { method: 'DELETE' });
      await cargarServicios();
    }
  });

  inputBusqueda.addEventListener('input', () => { limiteServicios = 5; render(); });

  document.querySelectorAll('[data-tipo="servicios"]').forEach((btn) => {
    btn.addEventListener('click', () => { ordenActual = btn.dataset.orden; render(); });
  });

  cargarMateriales();
  cargarServicios();
})();
