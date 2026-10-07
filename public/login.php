<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';

if (Auth::estaAutenticado()) {
    header('Location: panel.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim((string) ($_POST['usuario'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($usuario === '' || $password === '') {
        $error = 'Escribe tu usuario y contraseña.';
    } elseif (Auth::intentarLogin($usuario, $password)) {
        header('Location: panel.php');
        exit;
    } else {
        $error = 'Usuario o contraseña incorrectos.';
    }
}

$tituloPagina = 'Ingresar · LiliesNails';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
  <title><?= h($tituloPagina) ?></title>
  <meta name="theme-color" content="#c122f0" />
  <meta name="robots" content="noindex, nofollow" />
  <link rel="manifest" href="manifest-admin.json" />
  <meta name="apple-mobile-web-app-capable" content="yes" />
  <meta name="apple-mobile-web-app-title" content="LN Admin" />
  <link rel="icon" href="icons/icon-192.png" />
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@800&family=Nunito:wght@700;800&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="assets/css/estilos.css" />
  <link rel="stylesheet" href="assets/css/panel.css" />
</head>
<body class="panel">
  <div class="pantalla-login">
    <div class="tarjeta-login">
      <img src="assets/img/liliesnails.png" alt="Logo LiliesNails" />
      <h1>LiliesNails</h1>
      <p class="subtitulo">Panel de administración</p>

      <?php if ($error !== ''): ?>
        <div class="mensaje-error"><?= h($error) ?></div>
      <?php endif; ?>

      <form method="post" action="login.php" autocomplete="off">
        <div class="campo">
          <label for="usuario">Usuario</label>
          <input type="text" id="usuario" name="usuario" required autofocus />
        </div>
        <div class="campo">
          <label for="password">Contraseña</label>
          <input type="password" id="password" name="password" required />
        </div>
        <button type="submit" class="btn btn-bloque">Ingresar</button>
      </form>
    </div>
  </div>
  <script src="assets/js/sw-registro.js"></script>
</body>
</html>
