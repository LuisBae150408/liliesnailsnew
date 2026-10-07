<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
  <title><?= h($tituloPagina ?? 'LiliesNails') ?></title>
  <meta name="description" content="<?= h(configSitio('texto_bienvenida')) ?>" />
  <meta name="theme-color" content="#c122f0" />

  <link rel="manifest" href="manifest.json" />
  <link rel="apple-touch-icon" href="icons/apple-touch-icon.png" />
  <link rel="icon" href="icons/icon-192.png" />

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin />
  <!-- Solo cargamos los paquetes de Font Awesome que realmente se usan
       (solid + brands), en vez de "all.min.css" que trae también los
       íconos regular/light/thin/duotone que este sitio no usa. -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/fontawesome.min.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/solid.min.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/brands.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@800&family=Nunito:wght@700;800&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet" />

  <link rel="stylesheet" href="assets/css/estilos.css" />
  <link rel="stylesheet" href="assets/css/publico.css" />
</head>
<body class="publico">

<header class="barra-publica">
  <div class="marca">
    <img src="assets/img/liliesnails.png" alt="Logo LiliesNails" />
    <span>LiliesNails</span>
  </div>
  <?php $tel = configSitio('telefono_whatsapp'); if ($tel !== ''): ?>
    <a class="btn" href="<?= h(whatsappUrl($tel)) ?>" target="_blank" rel="noopener">
      <i class="fa-brands fa-whatsapp"></i> Agendar
    </a>
  <?php endif; ?>
</header>
