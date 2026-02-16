<?php
session_start();

/* Si ya está logueado, al panel */
if (!empty($_SESSION["auth"]) && $_SESSION["auth"] === true) {
  header("Location: panel.php");
  exit;
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, "UTF-8"); }

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $username = trim((string)($_POST["username"] ?? ""));
  $password = (string)($_POST["password"] ?? "");

  if ($username === "" || $password === "") {
    $error = "Rellena usuario y contraseña.";
  } else {
    $mysqli = new mysqli("localhost", "mcadmin", "mcadmin123", "mc_server_tickets");
    if ($mysqli->connect_error) die("Error DB: " . $mysqli->connect_error);
    $mysqli->set_charset("utf8mb4");

    $stmt = $mysqli->prepare("SELECT id, username, pass_sha256, role FROM mc_users WHERE username = ? LIMIT 1");
    if ($stmt === false) die("Error preparando login: " . $mysqli->error);

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();

    $inputHash = hash("sha256", $password);

    if ($row && hash_equals((string)$row["pass_sha256"], $inputHash)) {
      session_regenerate_id(true);

      $_SESSION["auth"] = true;
      $_SESSION["uid"]  = (int)$row["id"];
      $_SESSION["user"] = (string)$row["username"];
      $_SESSION["role"] = (string)$row["role"];

      header("Location: panel.php");
      exit;
    } else {
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
  <style>
    body{
      font-family:system-ui,-apple-system,"Segoe UI",sans-serif;
      margin:0;
      min-height:100vh;
      display:grid;
      place-items:center;
      background:url('img/fondo_mc.png') center/cover no-repeat;
    }

    .card{
      width:min(420px, 92vw);
      background:#fff;
      border:1px solid #e5e7eb;
      border-radius:14px;
      padding:16px;
      box-shadow:0 10px 30px rgba(15,23,42,.08);
      position:relative;
    }

    .btnlink{
      position:absolute;
      top:12px;
      right:12px;
      display:inline-block;
      padding:7px 10px;
      border-radius:10px;
      font-weight:900;
      font-size:12px;
      background: rgba(27,94,32,.10);
      border: 1px solid rgba(27,94,32,.18);
      color:#1b5e20;
      text-decoration:none;
    }
    .btnlink:hover{ background: rgba(27,94,32,.16); }

    label{
      display:block;
      font-size:12px;
      font-weight:900;
      margin:10px 0 6px;
      color:#334155;
    }

    input{
      width:100%;
      padding:10px 12px;
      border:1px solid #e5e7eb;
      border-radius:12px;
      outline:none;
      box-sizing:border-box;
    }

    button{
      margin-top:12px;
      width:100%;
      padding:10px 12px;
      border:0;
      border-radius:12px;
      background:#1b5e20;
      color:#fff;
      font-weight:950;
      cursor:pointer;
    }

    .err{
      margin-top:10px;
      padding:10px;
      border-radius:12px;
      background:#fff1f2;
      border:1px solid #fecaca;
      color:#7f1d1d;
      font-weight:800;
      font-size:13px;
    }

    .muted{ color:#6b7280; font-size:12px; margin-top:6px; }

    .logo{
      display:flex;
      align-items:center;
      gap:10px;
      margin-top:4px;
    }
    .logo img{
      width:52px;
      height:52px;
      filter: drop-shadow(0 10px 18px rgba(0,0,0,.18));
    }
  </style>
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
