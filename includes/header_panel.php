<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
  <title><?= h($tituloPagina ?? 'Panel · LiliesNails') ?></title>
  <meta name="theme-color" content="#c122f0" />
  <meta name="robots" content="noindex, nofollow" />

  <link rel="manifest" href="manifest-admin.json" />
  <link rel="apple-touch-icon" href="icons/apple-touch-icon.png" />
  <link rel="icon" href="icons/icon-192.png" />
  <meta name="apple-mobile-web-app-capable" content="yes" />
  <meta name="apple-mobile-web-app-title" content="LN Admin" />

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin />
  <!-- Solo los paquetes de Font Awesome que se usan (solid + brands) -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/fontawesome.min.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/solid.min.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/brands.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@800&family=Nunito:wght@700;800&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet" />

  <link rel="stylesheet" href="assets/css/estilos.css" />
  <link rel="stylesheet" href="assets/css/panel.css" />

  <?php if (ONESIGNAL_APP_ID !== ''): ?>
  <script src="https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.page.js" defer></script>
  <script>
    // Solo el App ID viaja al navegador (no es secreto, hace falta para
    // que el SDK sepa a qué app suscribir este dispositivo). La REST API
    // Key es secreta y ahora se queda siempre en el servidor —
    // ver src/OneSignalPush.php.
    window.ONESIGNAL_APP_ID = <?= json_encode(ONESIGNAL_APP_ID) ?>;
    window.OneSignalDeferred = window.OneSignalDeferred || [];
    OneSignalDeferred.push(async function (OneSignal) {
      await OneSignal.init({
        appId: window.ONESIGNAL_APP_ID,
        // OneSignal exige que su archivo se llame "OneSignalSDKWorker.js"
        // (ver public/OneSignalSDKWorker.js) y lo registramos en un scope
        // propio, separado del "/" que usa nuestro sw.js — así los dos
        // service workers conviven sin pisarse ni pelear por el control
        // de la página.
        serviceWorkerPath: 'OneSignalSDKWorker.js',
        serviceWorkerParam: { scope: '/onesignal/push/' },
      });
    });
  </script>
  <?php else: ?>
  <script>window.ONESIGNAL_APP_ID = '';</script>
  <?php endif; ?>
</head>
<body class="panel">

<div id="banner-recordatorios"></div>

<header class="panel-header">
  <div class="marca">
    <img src="assets/img/liliesnails.png" alt="Logo LiliesNails" />
    <h1>Panel LiliesNails</h1>
  </div>
  <div class="usuario-actual">
    <?= h(Auth::nombreUsuario()) ?>
    · <a href="index.php" style="color:var(--lila-primario);" target="_blank" rel="noopener">Ver sitio</a>
    · <a href="cambiar-password.php" style="color:var(--lila-primario);">Contraseña</a>
    <a href="logout.php">Cerrar sesión</a>
  </div>
</header>
