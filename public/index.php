<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';

$tituloPagina = 'LiliesNails · Diseño de uñas';
$serviciosPublicos = Servicio::listarPublicos();
$galeria = Galeria::listarActivas();
$telefono = configSitio('telefono_whatsapp');
$telLimpio = preg_replace('/\D/', '', $telefono);

require __DIR__ . '/../includes/header_publico.php';
?>

<section class="hero">
  <img class="marca-agua" src="assets/img/liliesnails.png" alt="" aria-hidden="true" />
  <img class="marca-agua izq" src="assets/img/liliesnails.png" alt="" aria-hidden="true" />
  <div class="hero-contenido">
    <img class="logo-hero" src="assets/img/liliesnails.png" alt="Logo LiliesNails" />
    <h1>LiliesNails</h1>
    <p class="tagline"><?= h(configSitio('texto_bienvenida', 'Diseños de uñas hechos con dedicación.')) ?></p>
    <div class="acciones-hero">
      <?php if ($telLimpio !== ''): ?>
        <a class="btn" href="<?= h(whatsappUrl($telefono)) ?>" target="_blank" rel="noopener">
          <i class="fa-brands fa-whatsapp"></i> Agendar por WhatsApp
        </a>
      <?php endif; ?>
      <a class="btn btn-secundario" href="#catalogo">Ver servicios</a>
    </div>
  </div>
</section>

<section class="seccion-publica" id="catalogo">
  <h2 class="titulo-seccion">Nuestros servicios</h2>
  <p class="subtitulo-seccion">Precios calculados con cuidado para que sepas exactamente qué incluye cada diseño.</p>

  <?php if (empty($serviciosPublicos)): ?>
    <p class="mensaje-vacio">Muy pronto publicaremos nuestro catálogo de servicios.</p>
  <?php else: ?>
    <div class="grid-servicios">
      <?php foreach ($serviciosPublicos as $s): ?>
        <article class="tarjeta-servicio">
          <?php if (!empty($s['imagen'])): ?>
            <img class="foto" src="assets/img/servicios/<?= h($s['imagen']) ?>" alt="<?= h($s['nombre']) ?>" loading="lazy" />
          <?php else: ?>
            <div class="foto marcador"><i class="fa-solid fa-spa"></i></div>
          <?php endif; ?>
          <div class="cuerpo">
            <h3><?= h($s['nombre']) ?></h3>
            <?php if (!empty($s['descripcion_publica'])): ?>
              <p><?= h($s['descripcion_publica']) ?></p>
            <?php endif; ?>
            <div class="precio">$<?= number_format($s['precio_final'], 2) ?> <span>aprox.</span></div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<?php if (!empty($galeria)): ?>
<section class="carrusel-galeria" id="galeria" aria-label="Galería de diseños">
  <div class="fondo-carrusel-capa activa" id="fondoCarruselA"></div>
  <div class="fondo-carrusel-capa" id="fondoCarruselB"></div>
  <div class="overlay-carrusel"></div>

  <div class="contenido-carrusel">
    <h2 class="titulo-seccion-carrusel">Galería de diseños</h2>
    <p class="subtitulo-seccion-carrusel">Un poco de inspiración para tu próxima cita.</p>

    <div class="marco-carrusel">
      <button type="button" class="flecha-carrusel izq" id="flechaGaleriaIzq" aria-label="Diseño anterior">
        <i class="fa-solid fa-chevron-left"></i>
      </button>

      <div class="pista-carrusel" id="pistaCarrusel">
        <?php foreach ($galeria as $foto): ?>
          <article class="slide-carrusel" data-imagen="assets/img/galeria/<?= h($foto['imagen']) ?>">
            <div class="slide-texto">
              <h3><?= h($foto['titulo'] ?: 'Diseño de uñas') ?></h3>
              <?php if (!empty($foto['descripcion'])): ?>
                <p><?= h($foto['descripcion']) ?></p>
              <?php endif; ?>
            </div>
            <div class="slide-imagen">
              <img src="assets/img/galeria/<?= h($foto['imagen']) ?>" alt="<?= h($foto['titulo'] ?: 'Diseño de uñas') ?>" loading="lazy" />
            </div>
          </article>
        <?php endforeach; ?>
      </div>

      <button type="button" class="flecha-carrusel der" id="flechaGaleriaDer" aria-label="Siguiente diseño">
        <i class="fa-solid fa-chevron-right"></i>
      </button>
    </div>

    <div class="dots-carrusel" id="dotsCarrusel">
      <?php foreach ($galeria as $i => $foto): ?>
        <button type="button" class="dot<?= $i === 0 ? ' activo' : '' ?>" data-index="<?= $i ?>" aria-label="Ir al diseño <?= $i + 1 ?>"></button>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if (!empty($galeria)): ?>
<script src="assets/js/galeria-carrusel.js"></script>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer_publico.php'; ?>
