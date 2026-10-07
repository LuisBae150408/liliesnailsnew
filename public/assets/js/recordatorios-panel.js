// recordatorios-panel.js — Aviso dentro del panel de citas próximas (próximas 3 horas)
// Es el complemento "visual" del recordatorio push de OneSignal: este avisa
// mientras tienes el panel abierto, el push avisa aunque esté cerrado.
(function () {
  const contenedor = document.getElementById('banner-recordatorios');
  if (!contenedor) return;

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

  async function revisarCitasProximas() {
    try {
      const res = await fetch('api/citas.php?proximas=180'); // próximas 3 horas
      if (!res.ok) return;
      const citas = await res.json();
      const ocultas = JSON.parse(sessionStorage.getItem('recordatorios-ocultos') || '[]');
      const visibles = citas.filter((c) => !ocultas.includes(c.id));

      if (!visibles.length) {
        contenedor.innerHTML = '';
        return;
      }

      contenedor.innerHTML = `
        <div class="banner-aviso-citas">
          <i class="fa-solid fa-bell"></i>
          <div class="texto-aviso">
            ${visibles.map((c) => `<div><strong>${escapar(c.clienta_nombre)}</strong> a las ${formatoHora(c.hora_inicio)}</div>`).join('')}
          </div>
          <button type="button" class="cerrar-aviso" aria-label="Cerrar">&times;</button>
        </div>`;

      contenedor.querySelector('.cerrar-aviso').addEventListener('click', () => {
        const idsVisibles = visibles.map((c) => c.id);
        sessionStorage.setItem('recordatorios-ocultos', JSON.stringify([...ocultas, ...idsVisibles]));
        contenedor.innerHTML = '';
      });
    } catch (e) {
      // Silencioso: este aviso es un extra, nunca debe romper el panel
    }
  }

  revisarCitasProximas();
  setInterval(revisarCitasProximas, 5 * 60 * 1000); // revisa cada 5 min mientras el panel esté abierto
})();
