<footer class="footer-publico">
  <div class="info-contacto">
    <?php $direccion = configSitio('direccion'); if ($direccion !== ''): ?>
      <div><i class="fa-solid fa-location-dot"></i><?= h($direccion) ?></div>
    <?php endif; ?>
    <?php $horario = configSitio('horario'); if ($horario !== ''): ?>
      <div><i class="fa-solid fa-clock"></i><?= h($horario) ?></div>
    <?php endif; ?>
    <?php $tel = configSitio('telefono_whatsapp'); if ($tel !== ''): ?>
      <div><i class="fa-brands fa-whatsapp"></i><?= h($tel) ?></div>
    <?php endif; ?>
  </div>
  <div class="redes">
    <?php $ig = configSitio('instagram'); if ($ig !== ''): ?>
      <a href="https://instagram.com/<?= h(ltrim($ig, '@')) ?>" target="_blank" rel="noopener" aria-label="Instagram">
        <i class="fa-brands fa-instagram"></i>
      </a>
    <?php endif; ?>
    <?php $tt = configSitio('tiktok'); if ($tt !== ''): ?>
      <a href="https://tiktok.com/@<?= h(ltrim($tt, '@')) ?>" target="_blank" rel="noopener" aria-label="TikTok">
        <i class="fa-brands fa-tiktok"></i>
      </a>
    <?php endif; ?>
  </div>
  <small>
    <a href="login.php" class="acceso-discreto" aria-label="Acceso administrador">✦</a>
    &copy; <?= date('Y') ?> LiliesNails. Todos los derechos reservados.
  </small>
</footer>

<?php $tel = configSitio('telefono_whatsapp'); if ($tel !== ''): ?>
  <a class="flotante-whatsapp" href="<?= h(whatsappUrl($tel)) ?>" target="_blank" rel="noopener" aria-label="Escribir por WhatsApp">
    <i class="fa-brands fa-whatsapp"></i>
  </a>
<?php endif; ?>

<script src="assets/js/sw-registro.js"></script>
</body>
</html>
