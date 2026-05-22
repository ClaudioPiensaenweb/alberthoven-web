<?php
/**
 * Al Bert Hoven — Login admin
 */
require_once __DIR__ . '/config.php';

// Si no hay contraseña configurada, redirigir a setup
if (empty(ABH_PASS_HASH)) {
    header('Location: setup.php');
    exit;
}

session_name(ABH_SESSION_NAME);
session_start();

// Ya logueado
if (!empty($_SESSION['abh_logged_in'])) {
    header('Location: posts.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pass = $_POST['password'] ?? '';

    // Rate-limit básico: bloquear tras 5 intentos en 15 min (con sesión)
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 0;
        $_SESSION['login_first_attempt'] = time();
    }

    $elapsed = time() - $_SESSION['login_first_attempt'];
    if ($_SESSION['login_attempts'] >= 5 && $elapsed < 900) {
        $remaining = ceil((900 - $elapsed) / 60);
        $error = "Demasiados intentos. Espera {$remaining} minuto(s).";
    } elseif ($elapsed >= 900) {
        // Reiniciar contador tras 15 min
        $_SESSION['login_attempts'] = 0;
        $_SESSION['login_first_attempt'] = time();
    }

    if (empty($error)) {
        if (password_verify($pass, ABH_PASS_HASH)) {
            session_regenerate_id(true);
            $_SESSION['abh_logged_in'] = true;
            $_SESSION['login_attempts'] = 0;
            header('Location: posts.php');
            exit;
        } else {
            $_SESSION['login_attempts']++;
            $error = 'Contraseña incorrecta.';
            // Pequeño delay anti-fuerza bruta
            sleep(1);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ABH — Acceso</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500&family=Newsreader:ital,wght@1,300&display=swap" rel="stylesheet">
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
    max-width: 380px;
    background: #111;
    border: 1px solid #1e1e1e;
    border-top: 2px solid #4a9eff;
    padding: 2.5rem 2rem;
  }
  .logo {
    font-family: 'Newsreader', serif;
    font-style: italic;
    font-size: 1.6rem;
    font-weight: 300;
    color: #f0f0f0;
    letter-spacing: .05em;
    margin-bottom: .3rem;
  }
  .sub { font-size: .75rem; color: #444; letter-spacing: .1em; text-transform: uppercase; margin-bottom: 2rem; }
  label { display: block; font-size: .72rem; letter-spacing: .1em; text-transform: uppercase; color: #555; margin-bottom: .4rem; }
  input[type=password] {
    width: 100%;
    padding: .65rem .9rem;
    background: #0a0a0a;
    border: 1px solid #222;
    color: #e0e0e0;
    font-family: inherit;
    font-size: .9rem;
    outline: none;
    margin-bottom: 1.4rem;
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
    font-size: .8rem;
    font-weight: 500;
    letter-spacing: .12em;
    text-transform: uppercase;
    cursor: pointer;
    transition: background .2s;
  }
  button:hover { background: #6bb3ff; }
  .error {
    background: #140808;
    border: 1px solid #4a1515;
    color: #e07070;
    padding: .65rem .85rem;
    font-size: .82rem;
    margin-bottom: 1.2rem;
  }
  .back { margin-top: 1.5rem; text-align: center; font-size: .75rem; }
  .back a { color: #333; text-decoration: none; transition: color .2s; }
  .back a:hover { color: #4a9eff; }
</style>
</head>
<body>
<div class="card">
  <div class="logo">Al Bert Hoven</div>
  <p class="sub">Panel de administración</p>

  <?php if ($error): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="post" autocomplete="off">
    <label for="password">Contraseña</label>
    <input type="password" id="password" name="password" required autofocus>
    <button type="submit">Entrar</button>
  </form>

  <p class="back"><a href="../index.html">← Volver al sitio</a></p>
</div>
</body>
</html>
