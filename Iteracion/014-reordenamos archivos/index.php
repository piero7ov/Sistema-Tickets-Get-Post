<?php
// index.php — Front público simple: crear ticket (INSERT) sin login

session_start();

/* ====== CONFIG DB ====== */
$dbHost = "localhost";
$dbUser = "mcadmin";
$dbPass = "mcadmin123";
$dbName = "mc_server_tickets";

/* ====== helpers ====== */
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, "UTF-8"); }

/* ====== allowlists ====== */
$tiposOk = ["ayuda","bug","reporte","sugerencia"];
$priosOk = ["baja","media","alta"];

/* ====== detectar si hay sesión admin (tolerante a tu key) ====== */
$adminLogged =
  isset($_SESSION["mc_user"]) ||
  isset($_SESSION["user"]) ||
  isset($_SESSION["username"]) ||
  isset($_SESSION["auth"]);

$adminHref = $adminLogged ? "back/panel.php" : "back/login.php";
$adminText = $adminLogged ? "Ir al panel" : "Ir a admin";

/* ====== estado UI ====== */
$ok = false;
$ticketId = 0;
$error = "";

/* ====== valores para repintar ====== */
$old = [
  "minecraft_user" => "",
  "tipo" => "ayuda",
  "prioridad" => "media",
  "titulo" => "",
  "descripcion" => ""
];

/* ====== POST: crear ticket ====== */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

  $old["minecraft_user"] = trim((string)($_POST["minecraft_user"] ?? ""));
  $old["tipo"]           = (string)($_POST["tipo"] ?? "ayuda");
  $old["prioridad"]      = (string)($_POST["prioridad"] ?? "media");
  $old["titulo"]         = trim((string)($_POST["titulo"] ?? ""));
  $old["descripcion"]    = trim((string)($_POST["descripcion"] ?? ""));

  // normalizar allowlists
  if (!in_array($old["tipo"], $tiposOk, true)) $old["tipo"] = "ayuda";
  if (!in_array($old["prioridad"], $priosOk, true)) $old["prioridad"] = "media";

  // validación mínima
  if ($old["minecraft_user"] === "" || $old["titulo"] === "" || $old["descripcion"] === "") {
    $error = "Rellena usuario, título y descripción.";
  } elseif (mb_strlen($old["minecraft_user"], "UTF-8") > 32) {
    $error = "El usuario de Minecraft no puede pasar de 32 caracteres.";
  } elseif (mb_strlen($old["titulo"], "UTF-8") > 120) {
    $error = "El título no puede pasar de 120 caracteres.";
  } else {
    // INSERT seguro con prepared statement
    $mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
    if ($mysqli->connect_error) {
      $error = "Error DB: " . $mysqli->connect_error;
    } else {
      $mysqli->set_charset("utf8mb4");

      $stmt = $mysqli->prepare("
        INSERT INTO mc_tickets (minecraft_user, tipo, titulo, descripcion, prioridad, estado)
        VALUES (?,?,?,?,?,'abierto')
      ");
      if ($stmt === false) {
        $error = "Error preparando INSERT: " . $mysqli->error;
      } else {
        $stmt->bind_param(
          "sssss",
          $old["minecraft_user"],
          $old["tipo"],
          $old["titulo"],
          $old["descripcion"],
          $old["prioridad"]
        );

        if ($stmt->execute()) {
          $ok = true;
          $ticketId = (int)$mysqli->insert_id;

          // limpiar formulario tras éxito
          $old = [
            "minecraft_user" => "",
            "tipo" => "ayuda",
            "prioridad" => "media",
            "titulo" => "",
            "descripcion" => ""
          ];
        } else {
          $error = "No se pudo crear el ticket: " . $stmt->error;
        }
      }
    }
  }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Crear ticket — Minecraft</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    :root{
      --card: rgba(255,255,255,.92);
      --border: rgba(229,231,235,.95);
      --text: #0f172a;
      --muted: #6b7280;

      --grass1: #1b5e20;
      --grass2: #2e7d32;

      --shadow: 0 16px 36px rgba(15,23,42,.18);
      --radius: 14px;
    }

    *{ box-sizing:border-box; }

    body{
      margin:0;
      font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
      color: var(--text);
      min-height:100vh;
      background:
        linear-gradient(rgba(0,0,0,.35), rgba(0,0,0,.35)),
        url('img/fondo_mc.png') center/cover no-repeat fixed;
    }

    .wrap{
      max-width: 820px;
      margin: 0 auto;
      padding: 22px 16px;
    }

    .card{
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 16px;
      margin-top: 14px;
      box-shadow: var(--shadow);
      backdrop-filter: blur(6px);
    }

    h1{ margin:0 0 6px; font-size:22px; }
    .muted{ color: var(--muted); font-size:12px; }

    label{
      display:block;
      font-weight: 900;
      font-size: 12px;
      margin: 12px 0 6px;
      color:#334155;
    }

    input,select,textarea{
      width:100%;
      padding: 10px 12px;
      border: 1px solid var(--border);
      border-radius: 12px;
      background: #fff;
      font: inherit;
      outline: none;
    }

    input:focus,select:focus,textarea:focus{
      border-color: rgba(27,94,32,.45);
      box-shadow: 0 0 0 4px rgba(27,94,32,.12);
    }

    textarea{
      min-height: 140px;
      resize: vertical;
    }

    .row{
      display:grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 12px;
      margin-top: 6px;
    }

    button{
      border:0;
      border-radius: 12px;
      padding: 10px 14px;
      cursor:pointer;
      font-weight: 950;
      background: var(--grass1);
      color:#fff;
    }

    button:hover{ background: var(--grass2); }

    .error{
      background:#fff1f2;
      border:1px solid #fecaca;
      color:#7f1d1d;
      border-radius: var(--radius);
      padding: 12px;
      margin-top: 12px;
      font-weight: 900;
    }

    .ok{
      background:#ecfdf5;
      border:1px solid #bbf7d0;
      color:#166534;
      border-radius: var(--radius);
      padding: 12px;
      margin-top: 12px;
      font-weight: 950;
    }

    .tiny{ font-size:12px; }

    /* ===== Botón admin esquina ===== */
    .admin-btn{
      position: fixed;
      top: 14px;
      right: 14px;
      z-index: 50;
      text-decoration:none;
      display:inline-flex;
      align-items:center;
      gap:8px;
      padding: 10px 12px;
      border-radius: 999px;
      font-weight: 950;
      font-size: 12px;
      color: #fff;
      background: rgba(15,23,42,.55);
      border: 1px solid rgba(255,255,255,.18);
      backdrop-filter: blur(8px);
      box-shadow: 0 10px 24px rgba(0,0,0,.20);
    }
    .admin-btn:hover{ background: rgba(15,23,42,.65); }

    @media (max-width: 720px){
      .row{ grid-template-columns: 1fr; }
      .admin-btn{ top: 10px; right: 10px; }
    }
  </style>
</head>
<body>

  <a class="admin-btn" href="<?= h($adminHref) ?>">🛠️ <?= h($adminText) ?></a>

  <div class="wrap">

    <div class="card">
      <h1>Crear ticket</h1>
      <div class="muted">Soporte del servidor Minecraft</div>

      <?php if ($ok): ?>
        <div class="ok">
          ✅ Ticket creado correctamente. ID: #<?= (int)$ticketId ?>
          <div class="tiny" style="margin-top:6px; font-weight:800;">
            Un admin lo verá en el panel.
          </div>
        </div>
      <?php endif; ?>

      <?php if ($error !== ""): ?>
        <div class="error">⚠️ <?= h($error) ?></div>
      <?php endif; ?>
    </div>

    <div class="card">
      <form method="post" action="">
        <div class="row">
          <div>
            <label>Minecraft user</label>
            <input name="minecraft_user" maxlength="32" value="<?= h($old["minecraft_user"]) ?>">
          </div>

          <div>
            <label>Tipo</label>
            <select name="tipo">
              <?php foreach ($tiposOk as $t): ?>
                <option value="<?= h($t) ?>" <?= $old["tipo"]===$t ? "selected" : "" ?>><?= h($t) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label>Prioridad</label>
            <select name="prioridad">
              <?php foreach ($priosOk as $p): ?>
                <option value="<?= h($p) ?>" <?= $old["prioridad"]===$p ? "selected" : "" ?>><?= h($p) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <label>Título</label>
        <input name="titulo" maxlength="120" value="<?= h($old["titulo"]) ?>">

        <label>Descripción</label>
        <textarea name="descripcion"><?= h($old["descripcion"]) ?></textarea>

        <div style="margin-top:12px;">
          <button type="submit">Enviar ticket</button>
        </div>
      </form>
    </div>

  </div>
</body>
</html>
