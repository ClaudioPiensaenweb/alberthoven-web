<?php
/**
 * Al Bert Hoven — Instalador de contraseña
 * Ejecutar UNA SOLA VEZ desde el servidor.
 * Una vez establecida la contraseña, este script se auto-deshabilita.
 */

$configFile = __DIR__ . '/config.php';
require_once $configFile;

// Si ya hay contraseña, bloquear acceso
if (!empty(ABH_PASS_HASH)) {
    http_response_code(403);
    die('Acceso denegado. La contraseña ya está configurada. Elimina este archivo del servidor si quieres restablecerla.');
}

$error   = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pass    = $_POST['password']    ?? '';
    $confirm = $_POST['confirm']     ?? '';

    if (strlen($pass) < 8) {
        $error = 'La contraseña debe tener al menos 8 caracteres.';
    } elseif ($pass !== $confirm) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        $hash = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]);

        $newConfig = <<<PHP
<?php
/**
 * Al Bert Hoven — Admin config
 * Generado por setup.php el {$_SERVER['REQUEST_TIME']}
 * NO exponer directamente (protegido por .htaccess)
 */

// Hash de la contraseña
define('ABH_PASS_HASH', '{$hash}');

define('ABH_SESSION_NAME', 'abh_admin');
define('POSTS_FILE', __DIR__ . '/../api/posts.json');
PHP;

        if (file_put_contents($configFile, $newConfig) !== false) {
            $success = true;
        } else {
            $error = 'Error al escribir config.php. Verifica los permisos del servidor.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ABH — Configuración inicial</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #080808;
    font-family: 'DM Sans', sans-serif;
    color: #c0c0c0;
  }
  .card {
    width: 100%;
    max-width: 420px;
    background: #111;
    border: 1px solid #222;
    border-top: 2px solid #4a9eff;
    padding: 2.5rem 2rem;
  }
  h1 {
    font-size: 1.1rem;
    font-weight: 400;
    letter-spacing: .15em;
    text-transform: uppercase;
    color: #f0f0f0;
    margin-bottom: .4rem;
  }
  .sub { font-size: .8rem; color: #555; margin-bottom: 2rem; }
  label { display: block; font-size: .75rem; letter-spacing: .1em; text-transform: uppercase; color: #666; margin-bottom: .4rem; }
  input[type=password] {
    width: 100%;
    padding: .65rem .9rem;
    background: #0a0a0a;
    border: 1px solid #2a2a2a;
    color: #e0e0e0;
    font-family: inherit;
    font-size: .9rem;
    outline: none;
    margin-bottom: 1.2rem;
    transition: border-color .2s;
  }
  input[type=password]:focus { border-color: #4a9eff; }
  button {
    width: 100%;
    padding: .75rem;
    background: #4a9eff;
    border: none;
    color: #080808;
    font-family: inherit;
    font-size: .85rem;
    font-weight: 500;
    letter-spacing: .1em;
    text-transform: uppercase;
    cursor: pointer;
    transition: background .2s;
  }
  button:hover { background: #6bb3ff; }
  .error  { background: #1a0808; border: 1px solid #6b2020; color: #ff7070; padding: .7rem .9rem; font-size: .85rem; margin-bottom: 1.2rem; }
  .ok     { background: #081a08; border: 1px solid #206b20; color: #70c070; padding: .7rem .9rem; font-size: .85rem; }
  .ok a   { color: #4a9eff; }
</style>
</head>
<body>
<div class="card">
  <h1>Al Bert Hoven</h1>
  <p class="sub">Configuración inicial — establece tu contraseña de acceso</p>

  <?php if ($success): ?>
    <div class="ok">
      Contraseña establecida correctamente.<br>
      <strong>Elimina este archivo del servidor</strong> antes de continuar.<br><br>
      <a href="index.php">Ir al acceso →</a>
    </div>
  <?php else: ?>
    <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="post" autocomplete="off">
      <label for="password">Contraseña</label>
      <input type="password" id="password" name="password" minlength="8" required autofocus>
      <label for="confirm">Confirmar contraseña</label>
      <input type="password" id="confirm" name="confirm" minlength="8" required>
      <button type="submit">Establecer contraseña</button>
    </form>
  <?php endif; ?>
</div>
</body>
</html>
