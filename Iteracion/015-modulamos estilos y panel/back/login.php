<?php
// login.php — Formulario de inicio de sesión para administradores

session_start();

/* =========================================
   1. REDIRECCIÓN SI YA ESTÁ LOGUEADO
   ========================================= */
// Si el usuario ya tiene sesión iniciada, no tiene sentido que vea el login.
// Lo mandamos directamente al panel.
if (!empty($_SESSION["auth"]) && $_SESSION["auth"] === true) {
  header("Location: panel.php");
  exit;
}

// Helper para escapar HTML (seguridad XSS)
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, "UTF-8"); }

$error = "";

/* =========================================
   2. PROCESAR LOGIN (POST)
   ========================================= */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  
  // Recogemos usuario y contraseña del formulario
  $username = trim((string)($_POST["username"] ?? ""));
  $password = (string)($_POST["password"] ?? "");

  // Validación básica: que no estén vacíos
  if ($username === "" || $password === "") {
    $error = "Rellena usuario y contraseña.";
  } else {
    // Conexión a la base de datos
    $mysqli = new mysqli("localhost", "mcadmin", "mcadmin123", "mc_server_tickets");
    if ($mysqli->connect_error) die("Error DB: " . $mysqli->connect_error);
    $mysqli->set_charset("utf8mb4");

    // Buscamos al usuario en la BD (solo necesitamos su ID, pass y rol)
    // Usamos prepared statements para evitar inyección SQL
    $stmt = $mysqli->prepare("SELECT id, username, pass_sha256, role FROM mc_users WHERE username = ? LIMIT 1");
    if ($stmt === false) die("Error preparando login: " . $mysqli->error);

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();

    // Calculamos el hash de la contraseña ingresada
    // Aquí usamos hash('sha256') por simplicidad académica
    $inputHash = hash("sha256", $password);

    // Comparamos el hash de la BD con el hash calculado
    // hash_equals evita ataques de tiempo (timing attacks)
    if ($row && hash_equals((string)$row["pass_sha256"], $inputHash)) {
      
      // ¡Login correcto!
      
      // Regeneramos el ID de sesión para prevenir Session Fixation
      session_regenerate_id(true);

      // Guardamos datos en la sesión
      $_SESSION["auth"] = true;
      $_SESSION["uid"]  = (int)$row["id"];
      $_SESSION["user"] = (string)$row["username"];
      $_SESSION["role"] = (string)$row["role"];

      // Redirigimos al panel
      header("Location: panel.php");
      exit;
    } else {
      // Login fallido: no damos pistas si falló usuario o clave
      $error = "Usuario o contraseña incorrectos.";
    }
  }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Login · Tickets Minecraft</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="css/login.css">
</head>
<body>
  <div class="card">

    <a class="btnlink" href="../index.php">← Ir a tickets</a>

    <div class="logo">
      <img src="https://piero7ov.github.io/pierodev-assets/brand/pierodev/logos/logocompleto.png" alt="PieroDev logo">
      <div>
        <div style="font-weight:950;">Tickets Minecraft</div>
        <div class="muted">Acceso al panel</div>
      </div>
    </div>

    <!-- Mostrar error si existe -->
    <?php if ($error !== ""): ?>
      <div class="err"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="post" action="login.php">
      <label>Usuario</label>
      <input name="username" autocomplete="username">

      <label>Contraseña</label>
      <input name="password" type="password" autocomplete="current-password">

      <button type="submit">Entrar</button>
    </form>
  </div>
</body>
</html>
