// sw.js — Service Worker de LiliesNails
// Estrategia (pensada para que NUNCA vuelva a quedarse pegado en una
// versión vieja del sitio, sin que tengas que borrar caché a mano):
//  - Páginas (navegación) y CSS/JS: "stale-while-revalidate" — se
//    muestra la copia guardada al instante (rápido), pero al mismo
//    tiempo se pide la versión real por internet y se guarda para la
//    siguiente vez. O sea: como mucho ves la versión vieja una sola
//    recarga, nunca más.
//  - Imágenes/íconos: cache-first (casi no cambian, y si cambian el
//    nombre del archivo siempre es distinto, así que no hay problema).
//  - Todo lo que vive en /api/: siempre red, nunca se cachea.
//
// Nota: las notificaciones push (OneSignal) usan su PROPIO service
// worker por separado — ver public/OneSignalSDKWorker.js — registrado
// en otro "scope" para no chocar con este. Antes se intentaba fusionar
// los dos aquí mismo, pero OneSignal exige un nombre de archivo exacto
// que este sw.js no tenía, así que la fusión nunca funcionaba de verdad.

// =====================================================================
// 👉 SUBE ESTE NÚMERO CADA VEZ QUE EDITES UN ARCHIVO .css O .js
//    (por ejemplo, si tú misma cambias un color o un texto en el
//    código, sin que yo te lo entregue ya listo).
//
//    Cómo hacerlo: cambia "v2" por "v3" (el próximo cambio sería "v4",
//    y así sucesivamente, siempre para arriba, nunca repitas un
//    número ya usado). Con solo ese cambio, en la próxima visita el
//    sitio se actualiza solo en todos los dispositivos (ver
//    sw-registro.js, que es quien hace la recarga automática).
//
//    Si solo cambias fotos (galería, servicios) o textos del panel de
//    Configuración, NO hace falta tocar esto — esos ya se actualizan
//    solos porque no pasan por esta caché.
// =====================================================================
const VERSION = 'liliesnails-v16';
const CACHE_ESTATICOS = `${VERSION}-estaticos`;
const CACHE_PAGINAS = `${VERSION}-paginas`;

const ASSETS_PRECARGA = [
  'assets/css/estilos.css',
  'assets/css/publico.css',
  'assets/css/panel.css',
  'assets/js/respaldo.js',
  'assets/js/panel-materiales.js',
  'assets/js/panel-servicios.js',
  'assets/js/agenda.js',
  'assets/js/galeria-carrusel.js',
  'assets/js/recordatorios-panel.js',
  'assets/img/liliesnails.png',
  'icons/icon-192.png',
  'icons/icon-512.png',
  'manifest.json',
  'index.php',
];

self.addEventListener('install', (evento) => {
  evento.waitUntil(
    caches.open(CACHE_ESTATICOS)
      // { cache: 'reload' } fuerza a pedir el archivo real por internet,
      // sin usar la caché del navegador, para precargar siempre lo último.
      .then((cache) => Promise.all(
        ASSETS_PRECARGA.map((ruta) =>
          fetch(ruta, { cache: 'reload' }).then((res) => res.ok && cache.put(ruta, res)).catch(() => {})
        )
      ))
  );
  self.skipWaiting();
});

self.addEventListener('activate', (evento) => {
  evento.waitUntil(
    caches.keys().then((claves) =>
      Promise.all(
        claves
          .filter((clave) => clave.startsWith('liliesnails-') && clave !== CACHE_ESTATICOS && clave !== CACHE_PAGINAS)
          .map((clave) => caches.delete(clave))
      )
    )
  );
  self.clients.claim();
});

/** Sirve lo que haya en caché al instante, y en paralelo pide la versión
 *  real por internet para dejarla lista para la próxima vez. */
function staleWhileRevalidate(peticion, nombreCache) {
  return caches.open(nombreCache).then((cache) =>
    cache.match(peticion).then((enCache) => {
      const enRed = fetch(peticion, { cache: 'reload' })
        .then((respuesta) => {
          if (respuesta.ok) cache.put(peticion, respuesta.clone());
          return respuesta;
        })
        .catch(() => enCache); // sin internet: usa lo que había en caché

      return enCache || enRed;
    })
  );
}

self.addEventListener('fetch', (evento) => {
  const url = new URL(evento.request.url);

  // Nunca cachear la API: siempre necesita datos frescos (o fallar si no hay red)
  if (url.pathname.includes('/api/')) {
    return;
  }

  // Solo manejamos peticiones GET del mismo origen
  if (evento.request.method !== 'GET' || url.origin !== self.location.origin) {
    return;
  }

  // Navegación (páginas PHP) y CSS/JS: stale-while-revalidate
  if (evento.request.mode === 'navigate' || /\.(css|js)$/.test(url.pathname)) {
    const nombreCache = evento.request.mode === 'navigate' ? CACHE_PAGINAS : CACHE_ESTATICOS;
    evento.respondWith(
      staleWhileRevalidate(evento.request, nombreCache).catch(() =>
        caches.match('index.php')
      )
    );
    return;
  }

  // Resto de assets estáticos (imágenes, íconos, fuentes): cache-first
  evento.respondWith(
    caches.match(evento.request).then((enCache) => {
      if (enCache) return enCache;
      return fetch(evento.request).then((respuesta) => {
        const copia = respuesta.clone();
        caches.open(CACHE_ESTATICOS).then((cache) => cache.put(evento.request, copia));
        return respuesta;
      });
    })
  );
});
