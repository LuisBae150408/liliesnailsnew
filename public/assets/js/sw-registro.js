// sw-registro.js — Registra el service worker y recarga la página, como
// mucho UNA vez por sesión, cuando detecta una versión nueva del sitio.
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('sw.js').catch(() => {});
  });

  navigator.serviceWorker.addEventListener('controllerchange', () => {
    // OJO: antes esto usaba una variable normal (yaRecargo), que se
    // resetea cada vez que la página se recarga — si el navegador
    // detectaba "versión nueva" en cada carga (le pasaba sobre todo
    // dentro de la app instalada en el celular), terminaba recargando
    // sin parar, dejando la app inutilizable. sessionStorage SÍ
    // sobrevive a la recarga, así que esto garantiza como mucho una
    // recarga automática por sesión, nunca más, pase lo que pase.
    if (sessionStorage.getItem('lilies_sw_recargo')) return;
    sessionStorage.setItem('lilies_sw_recargo', '1');
    window.location.reload();
  });
}
