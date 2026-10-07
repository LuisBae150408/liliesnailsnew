// respaldo.js — Descargar / subir el respaldo JSON completo (materiales + servicios)
(function () {
  const btnExportar = document.getElementById('btn-exportar');
  const inputImportar = document.getElementById('importarArchivo');

  if (btnExportar) {
    btnExportar.addEventListener('click', () => {
      window.location.href = 'api/respaldo.php';
    });
  }

  if (inputImportar) {
    inputImportar.addEventListener('change', async (evento) => {
      const archivo = evento.target.files[0];
      if (!archivo) return;

      if (!confirm('Esto reemplazará TODOS tus materiales y servicios actuales por los del archivo. ¿Continuar?')) {
        inputImportar.value = '';
        return;
      }

      try {
        const texto = await archivo.text();
        const datos = JSON.parse(texto);

        const res = await fetch('api/respaldo.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(datos),
        });
        const resultado = await res.json();

        if (!res.ok) {
          alert(resultado.error || 'No se pudo importar el respaldo.');
          return;
        }
        alert('¡Datos restaurados con éxito!');
        window.location.reload();
      } catch (err) {
        alert('Error al leer el archivo de respaldo.');
      } finally {
        inputImportar.value = '';
      }
    });
  }
})();
