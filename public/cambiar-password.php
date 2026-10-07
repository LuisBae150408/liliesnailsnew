<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
Auth::requerirLogin();

$error = '';
$exito = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = Auth::cambiarPassword(
        (string) ($_POST['actual'] ?? ''),
        (string) ($_POST['nueva'] ?? ''),
        (string) ($_POST['confirmacion'] ?? '')
    );
    $exito = $error === '';
}

$tituloPagina = 'Cambiar contraseña · Panel LiliesNails';
require __DIR__ . '/../includes/header_panel.php';
?>

<main class="panel-contenido">
  <section class="bloque">
    <h2>Cambiar contraseña</h2>

    <?php if ($exito): ?>
      <div class="mensaje-exito">Tu contraseña se actualizó correctamente.</div>
    <?php elseif ($error !== ''): ?>
      <div class="mensaje-error"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="post" action="cambiar-password.php" autocomplete="off">
      <div class="campo">
        <label for="actual">Contraseña actual</label>
        <input type="password" id="actual" name="actual" required />
      </div>
      <div class="campo">
        <label for="nueva">Nueva contraseña (mínimo 8 caracteres)</label>
        <input type="password" id="nueva" name="nueva" minlength="8" required />
      </div>
      <div class="campo">
        <label for="confirmacion">Repite la nueva contraseña</label>
        <input type="password" id="confirmacion" name="confirmacion" minlength="8" required />
      </div>
      <button type="submit" class="btn btn-bloque">Guardar nueva contraseña</button>
    </form>
  </section>
</main>

<?php require __DIR__ . '/../includes/footer_panel.php'; ?>
