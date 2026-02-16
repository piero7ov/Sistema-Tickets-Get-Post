<?php
session_start();

/* ====== CREDENCIALES ======*/
const ADMIN_USER = "admin";
const ADMIN_PASS = "1234";

/* Si ya está logueado, al panel */
if (!empty($_SESSION["admin"])) {
  header("Location: index.php?vista=list");
  exit;
}

/* Return (a dónde volver después del login) */
$return = isset($_GET["return"]) ? (string)$_GET["return"] : "index.php?vista=list";
if ($return === "" || strpos($return, "http") === 0) $return = "index.php?vista=list"; // anti-redirect raro

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $user = trim((string)($_POST["user"] ?? ""));
  $pass = (string)($_POST["pass"] ?? "");
  $returnPost = isset($_POST["return"]) ? (string)$_POST["return"] : "index.php?vista=list";
  if ($returnPost === "" || strpos($returnPost, "http") === 0) $returnPost = "index.php?vista=list";

  if ($user === ADMIN_USER && $pass === ADMIN_PASS) {
    session_regenerate_id(true);
    $_SESSION["admin"] = true;
    $_SESSION["admin_user"] = $user;

    header("Location: " . $returnPost);
    exit;
  } else {
    $error = "Usuario o contraseña incorrectos.";
    $return = $returnPost;
  }
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, "UTF-8"); }
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Login · Tickets MC</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body{ font-family: system-ui, -apple-system, "Segoe UI", sans-serif; margin:0; padding:16px; background:#f6f7f4; }
    .card{ max-width:420px; margin:60px auto; background:#fff; border:1px solid #e5e7eb; border-radius:14px; padding:16px; }
    h1{ margin:0 0 10px; font-size:20px; }
    .muted{ color:#6b7280; font-size:12px; margin-bottom:10px; }
    label{ display:block; font-size:12px; font-weight:800; color:#374151; margin:10px 0 6px; }
    input{ width:100%; padding:10px; border:1px solid #e5e7eb; border-radius:12px; }
    button{ margin-top:12px; width:100%; padding:10px 12px; border:0; border-radius:12px; cursor:pointer; font-weight:900; background:#1b5e20; color:#fff; }
    button:hover{ background:#2e7d32; }
    .error{ margin-top:10px; padding:10px; border-radius:12px; border:1px solid #fecaca; background:#fff1f2; color:#7f1d1d; font-weight:800; }
  </style>
</head>
<body>

  <div class="card">
    <h1>Login admin</h1>
    <div class="muted">Acceso al panel de tickets.</div>

    <?php if ($error !== ""): ?>
      <div class="error"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="post" action="login.php">
      <input type="hidden" name="return" value="<?= h($return) ?>">

      <label>Usuario</label>
      <input name="user" autocomplete="username" placeholder="admin">

      <label>Contraseña</label>
      <input name="pass" type="password" autocomplete="current-password" placeholder="1234">

      <button type="submit">Entrar</button>
    </form>
  </div>

</body>
</html>
