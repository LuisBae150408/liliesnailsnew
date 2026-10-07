// panel-materiales.js — Inventario de materiales (habla con api/materiales.php)
(function () {
  const cuerpoTabla = document.getElementById('cuerpo-tabla');
  if (!cuerpoTabla) return; // esta vista no está activa

  const inputNombre = document.getElementById('nombreMat');
  const inputPrecio = document.getElementById('precioMat');
  const inputRinde = document.getElementById('usoMat');
  const botonAgregar = document.getElementById('btn-agregar');
  const inputBusqueda = document.getElementById('buscarMaterial');
  const vacioMateriales = document.getElementById('vacio-materiales');

  let listaMateriales = [];
  let idMaterialEditando = null;
  let limiteMateriales = 5;
  let ordenActual = null;

  document.querySelectorAll('#seccion-inventario input[type="number"]').forEach((input) => {
    input.addEventListener('keydown', (e) => {
      if (e.key === '-' || e.key === 'e' || e.key === '+') e.preventDefault();
    });
  });

  async function cargarMateriales() {
    const res = await fetch('api/materiales.php');
    listaMateriales = await res.json();
    render();
  }

  function materialesFiltrados() {
    const filtro = (inputBusqueda.value || '').toLowerCase();
    let lista = listaMateriales.filter((m) => m.nombre.toLowerCase().includes(filtro));
    if (ordenActual === 'nombre') {
      lista = [...lista].sort((a, b) => a.nombre.localeCompare(b.nombre));
    } else if (ordenActual === 'precio') {
      lista = [...lista].sort((a, b) => parseFloat(b.precio) - parseFloat(a.precio));
    } else if (ordenActual === 'fecha') {
      lista = [...lista].sort((a, b) => b.id - a.id);
    }
    return lista;
  }

  function render() {
    const filtrados = materialesFiltrados();
    const visibles = filtrados.slice(0, limiteMateriales);

    cuerpoTabla.innerHTML = visibles.map((mat) => `
      <tr>
        <td>${escapar(mat.nombre)}</td>
        <td>$${Number(mat.precio).toFixed(2)}</td>
        <td>${mat.rinde}</td>
        <td>$${Number(mat.costo_uso).toFixed(2)}</td>
        <td class="acciones">
          <button class="btn-fila editar" data-editar="${mat.id}" title="Editar"><i class="fa-solid fa-pen"></i></button>
          <button class="btn-fila borrar" data-borrar="${mat.id}" title="Eliminar"><i class="fa-solid fa-trash"></i></button>
        </td>
      </tr>`).join('');

    vacioMateriales.classList.toggle('oculto', listaMateriales.length !== 0);

    const btnViejo = document.getElementById('btn-ver-mas-materiales');
    if (btnViejo) btnViejo.remove();
    if (filtrados.length > limiteMateriales) {
      const boton = document.createElement('button');
      boton.id = 'btn-ver-mas-materiales';
      boton.className = 'btn-ver-mas';
      boton.innerHTML = 'Ver más materiales <i class="fa-solid fa-angles-down"></i>';
      boton.addEventListener('click', () => { limiteMateriales += 5; render(); });
      cuerpoTabla.closest('.tabla-scroll').after(boton);
    }

    // Notificar a panel-servicios.js para refrescar los chips de materiales
    document.dispatchEvent(new CustomEvent('materiales-actualizados', { detail: listaMateriales }));
  }

  function escapar(texto) {
    const div = document.createElement('div');
    div.textContent = texto;
    return div.innerHTML;
  }

  function limpiarFormulario() {
    idMaterialEditando = null;
    botonAgregar.textContent = 'Agregar al inventario';
    inputNombre.value = '';
    inputPrecio.value = '';
    inputRinde.value = '';
  }

  botonAgregar.addEventListener('click', async () => {
    const nombre = inputNombre.value.trim();
    const precio = Math.abs(parseFloat(inputPrecio.value));
    const rinde = Math.abs(parseInt(inputRinde.value, 10));

    if (!nombre || isNaN(precio) || isNaN(rinde) || rinde < 1) {
      alert('Por favor, llena todos los campos del material.');
      return;
    }

    const cuerpo = JSON.stringify({ nombre, precio, rinde });
    const url = idMaterialEditando ? `api/materiales.php?id=${idMaterialEditando}` : 'api/materiales.php';
    const metodo = idMaterialEditando ? 'PUT' : 'POST';

    const res = await fetch(url, { method: metodo, headers: { 'Content-Type': 'application/json' }, body: cuerpo });
    const resultado = await res.json();
    if (!res.ok) {
      alert(resultado.error || 'No se pudo guardar el material.');
      return;
    }

    limpiarFormulario();
    await cargarMateriales();
  });

  cuerpoTabla.addEventListener('click', async (e) => {
    const btnEditar = e.target.closest('[data-editar]');
    const btnBorrar = e.target.closest('[data-borrar]');

    if (btnEditar) {
      const material = listaMateriales.find((m) => m.id == btnEditar.dataset.editar);
      if (!material) return;
      inputNombre.value = material.nombre;
      inputPrecio.value = material.precio;
      inputRinde.value = material.rinde;
      idMaterialEditando = material.id;
      botonAgregar.textContent = 'Actualizar material';
      inputNombre.focus();
    }

    if (btnBorrar) {
      if (!confirm('¿Seguro que quieres eliminar este material?')) return;
      await fetch(`api/materiales.php?id=${btnBorrar.dataset.borrar}`, { method: 'DELETE' });
      await cargarMateriales();
    }
  });

  inputBusqueda.addEventListener('input', () => { limiteMateriales = 5; render(); });

  document.querySelectorAll('[data-tipo="materiales"]').forEach((btn) => {
    btn.addEventListener('click', () => { ordenActual = btn.dataset.orden; render(); });
  });

  cargarMateriales();
})();
