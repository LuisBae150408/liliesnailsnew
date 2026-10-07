// galeria-carrusel.js — Carrusel inmersivo de la Galería de diseños
(function () {
  const pista = document.getElementById('pistaCarrusel');
  if (!pista) return;

  const slides = Array.from(pista.querySelectorAll('.slide-carrusel'));
  const dots = Array.from(document.querySelectorAll('#dotsCarrusel .dot'));
  const flechaIzq = document.getElementById('flechaGaleriaIzq');
  const flechaDer = document.getElementById('flechaGaleriaDer');
  const fondoA = document.getElementById('fondoCarruselA');
  const fondoB = document.getElementById('fondoCarruselB');

  if (!slides.length) return;

  const INTERVALO_AUTOPLAY = 7000; // 7s: largo a propósito, para no distraer
  let indiceActual = 0;
  let frenteEsA = true; // qué capa de fondo está visible ahora mismo
  let temporizador = null;
  let sincronizando = false;

  // ---------- Fondo ambient blur con crossfade ----------
  function cambiarFondo(url) {
    const capaTrasera = frenteEsA ? fondoB : fondoA;
    const capaFrontal = frenteEsA ? fondoA : fondoB;

    capaTrasera.style.backgroundImage = `url('${url}')`;
    // Fuerza el repaint antes de animar, para que la transición se vea
    requestAnimationFrame(() => {
      capaTrasera.classList.add('activa');
      capaFrontal.classList.remove('activa');
      frenteEsA = !frenteEsA;
    });
  }

  // ---------- Navegación ----------
  function irASlide(indice, comportamiento = 'smooth') {
    indice = ((indice % slides.length) + slides.length) % slides.length; // wraparound
    indiceActual = indice;
    sincronizando = true;
    pista.scrollTo({ left: slides[indice].offsetLeft, behavior: comportamiento });

    cambiarFondo(slides[indice].dataset.imagen);
    dots.forEach((d, i) => d.classList.toggle('activo', i === indice));

    setTimeout(() => { sincronizando = false; }, 500);
  }

  function siguienteSlide() { irASlide(indiceActual + 1); }
  function slideAnterior() { irASlide(indiceActual - 1); }

  flechaDer?.addEventListener('click', () => { siguienteSlide(); reiniciarAutoplay(); });
  flechaIzq?.addEventListener('click', () => { slideAnterior(); reiniciarAutoplay(); });

  dots.forEach((dot) => {
    dot.addEventListener('click', () => {
      irASlide(parseInt(dot.dataset.index, 10));
      reiniciarAutoplay();
    });
  });

  // ---------- Detecta swipe / scroll manual y sincroniza fondo + dots ----------
  let temporizadorScroll = null;
  pista.addEventListener('scroll', () => {
    if (sincronizando) return;
    clearTimeout(temporizadorScroll);
    temporizadorScroll = setTimeout(() => {
      const indiceCercano = Math.round(pista.scrollLeft / pista.clientWidth);
      if (indiceCercano !== indiceActual && slides[indiceCercano]) {
        indiceActual = indiceCercano;
        cambiarFondo(slides[indiceCercano].dataset.imagen);
        dots.forEach((d, i) => d.classList.toggle('activo', i === indiceCercano));
      }
    }, 120);
  }, { passive: true });

  // ---------- Autoplay (largo, se pausa si el usuario interactúa) ----------
  function iniciarAutoplay() {
    temporizador = setInterval(siguienteSlide, INTERVALO_AUTOPLAY);
  }
  function pausarAutoplay() {
    clearInterval(temporizador);
  }
  function reiniciarAutoplay() {
    pausarAutoplay();
    iniciarAutoplay();
  }

  const seccion = document.querySelector('.carrusel-galeria');
  seccion.addEventListener('mouseenter', pausarAutoplay);
  seccion.addEventListener('mouseleave', iniciarAutoplay);
  seccion.addEventListener('touchstart', pausarAutoplay, { passive: true });
  seccion.addEventListener('touchend', () => setTimeout(iniciarAutoplay, 3000), { passive: true });

  // ---------- Reubica el slide activo si cambia el tamaño de pantalla ----------
  let temporizadorResize = null;
  window.addEventListener('resize', () => {
    clearTimeout(temporizadorResize);
    temporizadorResize = setTimeout(() => irASlide(indiceActual, 'auto'), 200);
  });

  // ---------- Estado inicial (sin animación de fondo en la primera carga) ----------
  fondoA.style.backgroundImage = `url('${slides[0].dataset.imagen}')`;
  iniciarAutoplay();
})();
