<?php
session_start();

/* =========================
   GUARD
   ========================= */
if (empty($_SESSION["auth"])) {
  header("Location: login.php");
  exit;
}

// Panel (SELECT) + Nuevo (INSERT) + Cambiar estado (UPDATE) + Borrar (DELETE) + Ver (GET) + Editar (GET+POST UPDATE)

$vista = isset($_GET["vista"]) ? (string)$_GET["vista"] : "list";
if (!in_array($vista, ["list","new","ver","edit"], true)) $vista = "list";

/* ========= allowlists ========= */
$estadosOk = ["", "abierto", "en_proceso", "cerrado"];
$estadosPostOk = ["abierto", "en_proceso", "cerrado"];
$tiposOk   = ["", "bug", "reporte", "ayuda", "sugerencia"];
$ordersOk  = ["new", "old", "prio"];
$tiposPostOk = ["bug","reporte","ayuda","sugerencia"];
$priosPostOk = ["baja","media","alta"];

/* ========= helpers UI ========= */
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, "UTF-8"); }
function estadoClass($e){
  $e = (string)$e;
  if ($e === "en_proceso") return "en-proceso";
  if ($e === "abierto") return "abierto";
  if ($e === "cerrado") return "cerrado";
  return "abierto";
}
function prioClass($p){
  $p = (string)$p;
  if ($p === "alta") return "alta";
  if ($p === "media") return "media";
  if ($p === "baja") return "baja";
  return "media";
}

/* ========= variables para errores/repintar form ========= */
$error = "";
$old = [
  "minecraft_user" => "",
  "tipo" => "ayuda",
  "prioridad" => "media",
  "titulo" => "",
  "descripcion" => ""
];

/* =========================================================
   POST: acciones
   - accion=crear  -> INSERT
   - accion=estado -> UPDATE estado
   - accion=borrar -> DELETE
   - accion=editar -> UPDATE (campos del ticket)
   ========================================================= */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $accion = isset($_POST["accion"]) ? (string)$_POST["accion"] : "crear";

  // a dónde volvemos (mantener filtros o volver a ver ticket)
  $return = isset($_POST["return"]) ? (string)$_POST["return"] : "?vista=list";
  if ($return === "" || strpos($return, "?") !== 0) $return = "?vista=list"; // seguridad simple

  /* -----------------------------
     A) UPDATE estado
     ----------------------------- */
  if ($accion === "estado") {
    $id = isset($_POST["id"]) ? (int)$_POST["id"] : 0;
    $nuevoEstado = isset($_POST["estado"]) ? (string)$_POST["estado"] : "";

    if ($id > 0 && in_array($nuevoEstado, $estadosPostOk, true)) {
      $mysqli = new mysqli("localhost", "mcadmin", "mcadmin123", "mc_server_tickets");
      if ($mysqli->connect_error) die("Error DB: " . $mysqli->connect_error);
      $mysqli->set_charset("utf8mb4");

      $stmt = $mysqli->prepare("UPDATE mc_tickets SET estado = ? WHERE id = ?");
      if ($stmt === false) die("Error preparando UPDATE: " . $mysqli->error);

      $stmt->bind_param("si", $nuevoEstado, $id);
      $stmt->execute();
    }

    header("Location: " . $return);
    exit;
  }

  /* -----------------------------
     B) DELETE ticket
     ----------------------------- */
  if ($accion === "borrar") {
    $id = isset($_POST["id"]) ? (int)$_POST["id"] : 0;

    if ($id > 0) {
      $mysqli = new mysqli("localhost", "mcadmin", "mcadmin123", "mc_server_tickets");
      if ($mysqli->connect_error) die("Error DB: " . $mysqli->connect_error);
      $mysqli->set_charset("utf8mb4");

      $stmt = $mysqli->prepare("DELETE FROM mc_tickets WHERE id = ?");
      if ($stmt === false) die("Error preparando DELETE: " . $mysqli->error);

      $stmt->bind_param("i", $id);
      $stmt->execute();
    }

    header("Location: " . $return);
    exit;
  }

  /* -----------------------------
     C) EDITAR ticket (UPDATE campos)
     ----------------------------- */
  if ($accion === "editar") {
    $id = isset($_POST["id"]) ? (int)$_POST["id"] : 0;

    $old["minecraft_user"] = trim((string)($_POST["minecraft_user"] ?? ""));
    $old["tipo"]           = (string)($_POST["tipo"] ?? "ayuda");
    $old["prioridad"]      = (string)($_POST["prioridad"] ?? "media");
    $old["titulo"]         = trim((string)($_POST["titulo"] ?? ""));
    $old["descripcion"]    = trim((string)($_POST["descripcion"] ?? ""));

    if ($id <= 0) {
      $error = "ID inválido.";
      $vista = "edit";
    } elseif ($old["minecraft_user"] === "" || $old["titulo"] === "" || $old["descripcion"] === "") {
      $error = "Rellena usuario, título y descripción.";
      $vista = "edit";
    } else {
      if (!in_array($old["tipo"], $tiposPostOk, true)) $old["tipo"] = "ayuda";
      if (!in_array($old["prioridad"], $priosPostOk, true)) $old["prioridad"] = "media";

      $mysqli = new mysqli("localhost", "mcadmin", "mcadmin123", "mc_server_tickets");
      if ($mysqli->connect_error) die("Error DB: " . $mysqli->connect_error);
      $mysqli->set_charset("utf8mb4");

      $stmt = $mysqli->prepare("
        UPDATE mc_tickets
        SET minecraft_user = ?, tipo = ?, prioridad = ?, titulo = ?, descripcion = ?
        WHERE id = ?
      ");
      if ($stmt === false) die("Error preparando UPDATE editar: " . $mysqli->error);

      $stmt->bind_param(
        "sssssi",
        $old["minecraft_user"],
        $old["tipo"],
        $old["prioridad"],
        $old["titulo"],
        $old["descripcion"],
        $id
      );
      $stmt->execute();

      // Tras editar: ir a "ver ticket"
      $go = "?" . http_build_query([
        "vista" => "ver",
        "id" => $id,
        "return" => $return
      ]);
      header("Location: " . $go);
      exit;
    }
  }

  /* -----------------------------
     D) INSERT ticket (crear)
     ----------------------------- */
  $old["minecraft_user"] = trim((string)($_POST["minecraft_user"] ?? ""));
  $old["tipo"]           = (string)($_POST["tipo"] ?? "ayuda");
  $old["prioridad"]      = (string)($_POST["prioridad"] ?? "media");
  $old["titulo"]         = trim((string)($_POST["titulo"] ?? ""));
  $old["descripcion"]    = trim((string)($_POST["descripcion"] ?? ""));

  if ($old["minecraft_user"] === "" || $old["titulo"] === "" || $old["descripcion"] === "") {
    $error = "Rellena usuario, título y descripción.";
    $vista = "new";
  } else {
    if (!in_array($old["tipo"], $tiposPostOk, true)) $old["tipo"] = "ayuda";
    if (!in_array($old["prioridad"], $priosPostOk, true)) $old["prioridad"] = "media";

    $mysqli = new mysqli("localhost", "mcadmin", "mcadmin123", "mc_server_tickets");
    if ($mysqli->connect_error) die("Error DB: " . $mysqli->connect_error);
    $mysqli->set_charset("utf8mb4");

    $stmt = $mysqli->prepare("
      INSERT INTO mc_tickets (minecraft_user, tipo, titulo, descripcion, prioridad, estado)
      VALUES (?,?,?,?,?,'abierto')
    ");
    if ($stmt === false) die("Error preparando INSERT: " . $mysqli->error);

    $stmt->bind_param("sssss", $old["minecraft_user"], $old["tipo"], $old["titulo"], $old["descripcion"], $old["prioridad"]);
    $stmt->execute();

    header("Location: ?vista=list");
    exit;
  }
}

/* =========================================================
   GET: filtros (listado)
   ========================================================= */
$q      = isset($_GET["q"]) ? trim((string)$_GET["q"]) : "";
$estado = isset($_GET["estado"]) ? (string)$_GET["estado"] : "";
$tipo   = isset($_GET["tipo"]) ? (string)$_GET["tipo"] : "";
$order  = isset($_GET["order"]) ? (string)$_GET["order"] : "new";

if (!in_array($estado, $estadosOk, true)) $estado = "";
if (!in_array($tipo, $tiposOk, true)) $tipo = "";
if (!in_array($order, $ordersOk, true)) $order = "new";

/* URL de retorno (volver al listado con filtros actuales) */
$returnHere = "?" . http_build_query([
  "vista"  => "list",
  "q"      => $q,
  "estado" => $estado,
  "tipo"   => $tipo,
  "order"  => $order
]);

/* =========================================================
   SELECT: listado (vista=list)
   ========================================================= */
$tickets = [];
$useFullText = false;

if ($vista === "list") {
  $mysqli = new mysqli("localhost", "mcadmin", "mcadmin123", "mc_server_tickets");
  if ($mysqli->connect_error) die("Error DB: " . $mysqli->connect_error);
  $mysqli->set_charset("utf8mb4");

  $where  = [];
  $params = [];
  $types  = "";

  if ($q !== "") {
    $useFullText = true;
    $where[] = "MATCH(titulo, descripcion) AGAINST (? IN NATURAL LANGUAGE MODE)";
    $params[] = $q;
    $types .= "s";
  }

  if ($estado !== "") { $where[] = "estado = ?"; $params[] = $estado; $types .= "s"; }
  if ($tipo   !== "") { $where[] = "tipo = ?";   $params[] = $tipo;   $types .= "s"; }

  $sql = "SELECT id, minecraft_user, tipo, titulo, prioridad, estado, creado_en
          FROM mc_tickets";
  if ($where) $sql .= " WHERE " . implode(" AND ", $where);

  if ($order === "old")       $sql .= " ORDER BY creado_en ASC";
  elseif ($order === "prio")  $sql .= " ORDER BY FIELD(prioridad,'alta','media','baja'), creado_en DESC";
  else                        $sql .= " ORDER BY creado_en DESC";

  $stmt = $mysqli->prepare($sql);

  // Fallback LIKE si FULLTEXT falla
  if ($stmt === false && $q !== "") {
    $useFullText = false;

    $where  = [];
    $params = [];
    $types  = "";

    $where[] = "(titulo LIKE ? OR descripcion LIKE ?)";
    $like = "%".$q."%";
    $params[] = $like; $params[] = $like;
    $types .= "ss";

    if ($estado !== "") { $where[] = "estado = ?"; $params[] = $estado; $types .= "s"; }
    if ($tipo   !== "") { $where[] = "tipo = ?";   $params[] = $tipo;   $types .= "s"; }

    $sql = "SELECT id, minecraft_user, tipo, titulo, prioridad, estado, creado_en
            FROM mc_tickets
            WHERE " . implode(" AND ", $where);

    if ($order === "old")       $sql .= " ORDER BY creado_en ASC";
    elseif ($order === "prio")  $sql .= " ORDER BY FIELD(prioridad,'alta','media','baja'), creado_en DESC";
    else                        $sql .= " ORDER BY creado_en DESC";

    $stmt = $mysqli->prepare($sql);
  }

  if ($stmt === false) die("Error preparando SELECT: " . $mysqli->error);

  if ($types !== "") {
    $bind = [];
    $bind[] = $types;
    foreach ($params as $k => $v) $bind[] = &$params[$k];
    call_user_func_array([$stmt, "bind_param"], $bind);
  }

  $stmt->execute();
  $res = $stmt->get_result();
  while ($row = $res->fetch_assoc()) $tickets[] = $row;
}

/* =========================================================
   SELECT: ver ticket (vista=ver&id=...)
   ========================================================= */
$ticket = null;
$verError = "";
$returnFromView = "?vista=list";
$selfView = "?vista=list";

if ($vista === "ver") {
  $idVer = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

  if (isset($_GET["return"])) {
    $tmp = (string)$_GET["return"];
    if ($tmp !== "" && strpos($tmp, "?") === 0) $returnFromView = $tmp;
  } else {
    $returnFromView = $returnHere;
  }

  if ($idVer <= 0) {
    $verError = "ID inválido.";
  } else {
    $mysqli = new mysqli("localhost", "mcadmin", "mcadmin123", "mc_server_tickets");
    if ($mysqli->connect_error) die("Error DB: " . $mysqli->connect_error);
    $mysqli->set_charset("utf8mb4");

    $stmt = $mysqli->prepare("
      SELECT id, minecraft_user, tipo, titulo, descripcion, prioridad, estado, creado_en, actualizado_en
      FROM mc_tickets
      WHERE id = ?
      LIMIT 1
    ");
    if ($stmt === false) die("Error preparando SELECT ver: " . $mysqli->error);

    $stmt->bind_param("i", $idVer);
    $stmt->execute();
    $res = $stmt->get_result();
    $ticket = $res->fetch_assoc();

    if (!$ticket) $verError = "No existe el ticket.";
  }

  $selfView = "?" . http_build_query([
    "vista" => "ver",
    "id"    => $idVer,
    "return"=> $returnFromView
  ]);
}

/* =========================================================
   SELECT: editar ticket (vista=edit&id=...)
   ========================================================= */
$editTicket = null;
$editError = "";
$returnFromEdit = "?vista=list";
$selfEdit = "?vista=list";

if ($vista === "edit") {
  $idEdit = isset($_GET["id"]) ? (int)$_GET["id"] : (int)($_POST["id"] ?? 0);

  if (isset($_GET["return"])) {
    $tmp = (string)$_GET["return"];
    if ($tmp !== "" && strpos($tmp, "?") === 0) $returnFromEdit = $tmp;
  } else {
    $returnFromEdit = $returnHere;
  }

  if ($idEdit <= 0) {
    $editError = "ID inválido.";
  } else {
    $mysqli = new mysqli("localhost", "mcadmin", "mcadmin123", "mc_server_tickets");
    if ($mysqli->connect_error) die("Error DB: " . $mysqli->connect_error);
    $mysqli->set_charset("utf8mb4");

    $stmt = $mysqli->prepare("
      SELECT id, minecraft_user, tipo, titulo, descripcion, prioridad, estado, creado_en, actualizado_en
      FROM mc_tickets
      WHERE id = ?
      LIMIT 1
    ");
    if ($stmt === false) die("Error preparando SELECT edit: " . $mysqli->error);

    $stmt->bind_param("i", $idEdit);
    $stmt->execute();
    $res = $stmt->get_result();
    $editTicket = $res->fetch_assoc();

    if (!$editTicket) $editError = "No existe el ticket.";

    if ($editTicket && $error === "") {
      $old["minecraft_user"] = (string)$editTicket["minecraft_user"];
      $old["tipo"]           = (string)$editTicket["tipo"];
      $old["prioridad"]      = (string)$editTicket["prioridad"];
      $old["titulo"]         = (string)$editTicket["titulo"];
      $old["descripcion"]    = (string)$editTicket["descripcion"];
    }
  }

  $selfEdit = "?" . http_build_query([
    "vista" => "edit",
    "id"    => $idEdit,
    "return"=> $returnFromEdit
  ]);
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Tickets Minecraft</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <style>
    :root{
      --bg: #f6f7f4;
      --card: #ffffff;
      --text: #0f172a;
      --muted: #6b7280;
      --border: #e5e7eb;

      --grass1: #1b5e20;
      --grass2: #2e7d32;

      --shadow: 0 10px 30px rgba(15, 23, 42, .08);
      --radius: 14px;

      --danger: #b91c1c;
    }

    *{ box-sizing:border-box; }

    body{
      margin:0;
      font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
      background: var(--bg);
      color: var(--text);
      display:flex;
      min-height:100vh;
    }

    /* ===== Sidebar izquierda ===== */
    .sidebar{
      width: 280px;
      padding: 16px;
      background: linear-gradient(180deg, var(--grass1), var(--grass2));
      color: #fff;
      display:flex;
      flex-direction:column;
    }

    .brand{
      display:flex;
      align-items:center;
      gap: 12px;
      margin-bottom: 14px;
      text-decoration:none;
      color:#fff;
    }

    .brand img{
      width: 65px;
      height: 65px;
      display:block;
      background: transparent;
      filter: drop-shadow(0 10px 20px rgba(0,0,0,.25));
    }

    .brand .t1{ font-weight: 950; font-size: 15px; line-height: 1.1; }
    .brand .t2{ font-weight: 750; font-size: 12px; opacity:.92; }

    .nav{
      display:flex;
      flex-direction:column;
      gap: 8px;
      margin-top: 10px;
    }

    .nav a{
      color:#fff;
      text-decoration:none;
      font-weight: 900;
      font-size: 13px;
      padding: 10px 12px;
      border-radius: 12px;
      border: 1px solid rgba(255,255,255,.18);
      background: rgba(255,255,255,.10);
    }

    .nav a:hover{ background: rgba(255,255,255,.18); }

    .nav a.activo{
      background: #fff;
      color: var(--grass1);
      border-color:#fff;
    }

    .sidebar .foot{
      margin-top:auto;
      opacity:.95;
      font-size: 12px;
      padding-top: 16px;
    }

    /* ===== Main ===== */
    main{
      flex:1;
      padding: 18px;
      overflow:auto;
    }

    h1{ margin: 0 0 6px; font-size: 22px; }
    .muted{ color: var(--muted); font-size: 12px; }

    .box{
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      padding: 14px;
      margin-top: 12px;
    }

    /* links globales */
    a{ color: var(--grass1); text-decoration:none; }
    a:hover{ opacity:.9; }

    .btnlink{
      display:inline-block;
      padding: 7px 10px;
      border-radius: 10px;
      font-weight: 900;
      font-size: 12px;
      background: rgba(27,94,32,.10);
      border: 1px solid rgba(27,94,32,.18);
      color: var(--grass1);
    }
    .btnlink:hover{ background: rgba(27,94,32,.16); }

    /* ===== Forms ===== */
    .row{ display:flex; gap:10px; flex-wrap:wrap; margin-top: 10px; }
    input,select,textarea{
      padding: 10px 10px;
      border: 1px solid var(--border);
      border-radius: 12px;
      outline: none;
      background: #fff;
      font: inherit;
    }
    textarea{ width:100%; min-height: 120px; resize: vertical; }

    button{
      border: 0;
      border-radius: 12px;
      padding: 10px 12px;
      cursor:pointer;
      font-weight: 950;
      background: var(--grass1);
      color: #fff;
    }
    button:hover{ background: var(--grass2); }

    form.inline{ display:inline; }
    form.inline button{
      padding: 7px 10px;
      border-radius: 10px;
      font-size: 12px;
      font-weight: 950;
    }

    /* ===== Badges (estado/prioridad) ===== */
    .badge{
      display:inline-flex;
      align-items:center;
      padding: 4px 10px;
      border-radius: 999px;
      font-size: 12px;
      font-weight: 950;
      border: 1px solid rgba(15,23,42,.08);
      white-space: nowrap;
    }

    /* Estado (colores intuitivos) */
    .estado-abierto{
      background:#dcfce7; color:#166534; border-color:#bbf7d0;
    }
    .estado-en-proceso{
      background:#dbeafe; color:#1e40af; border-color:#bfdbfe;
    }
    .estado-cerrado{
      background:#f1f5f9; color:#334155; border-color:#e2e8f0;
    }

    /* Prioridad */
    .prio-alta{
      background:#fee2e2; color:#991b1b; border-color:#fecaca;
    }
    .prio-media{
      background:#fef9c3; color:#854d0e; border-color:#fde68a;
    }
    .prio-baja{
      background:#e0f2fe; color:#075985; border-color:#bae6fd;
    }

    /* ===== Table ===== */
    table{
      width:100%;
      border-collapse: separate;
      border-spacing: 0;
      margin-top: 12px;
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      overflow: hidden;
      box-shadow: var(--shadow);
    }
    th,td{
      padding: 10px 12px;
      border-bottom: 1px solid var(--border);
      text-align:left;
      vertical-align: top;
      font-size: 14px;
    }
    th{
      background: rgba(27,94,32,.10);
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: .04em;
    }
    tbody tr:hover td{ background: rgba(34,197,94,.06); }

    .error{
      border: 1px solid #fecaca;
      background: #fff1f2;
      padding: 12px;
      border-radius: var(--radius);
      margin-top: 12px;
      color: #7f1d1d;
      font-weight: 800;
    }

    @media (max-width: 860px){
      body{ display:block; }
      .sidebar{ width:auto; }
      .nav{ flex-direction:row; flex-wrap:wrap; }
      main{ padding-top: 10px; }
    }
  </style>
</head>
<body>

  <aside class="sidebar">
    <a class="brand" href="?vista=list">
      <img src="https://piero7ov.github.io/pierodev-assets/brand/pierodev/logos/logocompleto.png" alt="PieroDev logo">
      <div>
        <div class="t1">Tickets Minecraft</div>
        <div class="t2">Panel de soporte</div>
      </div>
    </a>

    <nav class="nav">
      <a href="?vista=list" class="<?= $vista==="list" ? "activo" : "" ?>">📋 Listado</a>
      <a href="?vista=new"  class="<?= $vista==="new"  ? "activo" : "" ?>">➕ Nuevo</a>
      <a href="logout.php">🚪 Salir</a>
    </nav>

    <div class="foot">👤 <?= h($_SESSION["user"] ?? "admin") ?> · <?= h($_SESSION["role"] ?? "") ?></div>
  </aside>

  <main>
    <?php if ($vista === "new"): ?>

      <h1>Nuevo ticket</h1>
      <div class="muted">Formulario con POST (INSERT).</div>

      <?php if ($error !== ""): ?>
        <div class="error"><?= h($error) ?></div>
      <?php endif; ?>

      <div class="box">
        <form method="post" action="?vista=new">
          <input type="hidden" name="accion" value="crear">

          <div class="row">
            <div>
              <div class="muted">Minecraft user</div>
              <input name="minecraft_user" maxlength="32"
                     value="<?= h($old["minecraft_user"]) ?>"
                     placeholder="Ej: PieroDev">
            </div>

            <div>
              <div class="muted">Tipo</div>
              <select name="tipo">
                <option value="ayuda" <?= $old["tipo"]==="ayuda" ? "selected" : "" ?>>ayuda</option>
                <option value="bug" <?= $old["tipo"]==="bug" ? "selected" : "" ?>>bug</option>
                <option value="reporte" <?= $old["tipo"]==="reporte" ? "selected" : "" ?>>reporte</option>
                <option value="sugerencia" <?= $old["tipo"]==="sugerencia" ? "selected" : "" ?>>sugerencia</option>
              </select>
            </div>

            <div>
              <div class="muted">Prioridad</div>
              <select name="prioridad">
                <option value="baja" <?= $old["prioridad"]==="baja" ? "selected" : "" ?>>baja</option>
                <option value="media" <?= $old["prioridad"]==="media" ? "selected" : "" ?>>media</option>
                <option value="alta" <?= $old["prioridad"]==="alta" ? "selected" : "" ?>>alta</option>
              </select>
            </div>
          </div>

          <div style="margin-top:10px;">
            <div class="muted">Título</div>
            <input name="titulo" maxlength="120" style="width:100%;"
                   value="<?= h($old["titulo"]) ?>"
                   placeholder="Ej: Se bugueó el spawn">
          </div>

          <div style="margin-top:10px;">
            <div class="muted">Descripción</div>
            <textarea name="descripcion" placeholder="Describe el problema..."><?= h($old["descripcion"]) ?></textarea>
          </div>

          <div style="margin-top:12px; display:flex; gap:10px; flex-wrap:wrap;">
            <button type="submit">Guardar</button>
            <a class="btnlink" href="?vista=list">Volver</a>
          </div>
        </form>
      </div>

    <?php elseif ($vista === "ver"): ?>

      <h1>Ver ticket</h1>
      <p class="muted">
        <a class="btnlink" href="<?= h($returnFromView) ?>">← Volver</a>
        <?php if ($ticket): ?>
          <a class="btnlink" href="?vista=edit&id=<?= (int)$ticket["id"] ?>&return=<?= rawurlencode($returnFromView) ?>">Editar</a>
        <?php endif; ?>
      </p>

      <?php if ($verError !== ""): ?>
        <div class="error"><?= h($verError) ?></div>
      <?php else: ?>

        <div class="box">
          <div class="muted">Acciones (POST)</div>
          <div style="margin-top:10px;">
            <form class="inline" method="post" action="">
              <input type="hidden" name="accion" value="estado">
              <input type="hidden" name="id" value="<?= (int)$ticket["id"] ?>">
              <input type="hidden" name="estado" value="abierto">
              <input type="hidden" name="return" value="<?= h($selfView) ?>">
              <button type="submit">Abrir</button>
            </form>

            <form class="inline" method="post" action="">
              <input type="hidden" name="accion" value="estado">
              <input type="hidden" name="id" value="<?= (int)$ticket["id"] ?>">
              <input type="hidden" name="estado" value="en_proceso">
              <input type="hidden" name="return" value="<?= h($selfView) ?>">
              <button type="submit">En proceso</button>
            </form>

            <form class="inline" method="post" action="">
              <input type="hidden" name="accion" value="estado">
              <input type="hidden" name="id" value="<?= (int)$ticket["id"] ?>">
              <input type="hidden" name="estado" value="cerrado">
              <input type="hidden" name="return" value="<?= h($selfView) ?>">
              <button type="submit">Cerrar</button>
            </form>

            <form class="inline" method="post" action=""
                  onsubmit="return confirm('¿Borrar ticket #<?= (int)$ticket['id'] ?>?');">
              <input type="hidden" name="accion" value="borrar">
              <input type="hidden" name="id" value="<?= (int)$ticket["id"] ?>">
              <input type="hidden" name="return" value="<?= h($returnFromView) ?>">
              <button type="submit" style="background:var(--danger);">Borrar</button>
            </form>
          </div>
        </div>

        <table>
          <tbody>
            <tr><th>ID</th><td><?= (int)$ticket["id"] ?></td></tr>
            <tr><th>Usuario</th><td><?= h($ticket["minecraft_user"]) ?></td></tr>
            <tr><th>Tipo</th><td><?= h($ticket["tipo"]) ?></td></tr>
            <tr>
              <th>Prioridad</th>
              <td><span class="badge prio-<?= prioClass($ticket["prioridad"]) ?>"><?= h($ticket["prioridad"]) ?></span></td>
            </tr>
            <tr>
              <th>Estado</th>
              <td><span class="badge estado-<?= estadoClass($ticket["estado"]) ?>"><?= h($ticket["estado"]) ?></span></td>
            </tr>
            <tr><th>Creado</th><td><?= h($ticket["creado_en"]) ?></td></tr>
            <tr><th>Actualizado</th><td><?= h($ticket["actualizado_en"]) ?></td></tr>
            <tr><th>Título</th><td><?= h($ticket["titulo"]) ?></td></tr>
            <tr><th>Descripción</th><td><?= nl2br(h($ticket["descripcion"])) ?></td></tr>
          </tbody>
        </table>

      <?php endif; ?>

    <?php elseif ($vista === "edit"): ?>

      <h1>Editar ticket</h1>
      <p class="muted">
        <?php if ($editTicket): ?>
          <a class="btnlink" href="?vista=ver&id=<?= (int)$editTicket["id"] ?>&return=<?= rawurlencode($returnFromEdit) ?>">← Volver</a>
        <?php else: ?>
          <a class="btnlink" href="<?= h($returnFromEdit) ?>">← Volver</a>
        <?php endif; ?>
      </p>

      <?php if ($editError !== ""): ?>
        <div class="error"><?= h($editError) ?></div>
      <?php endif; ?>

      <?php if ($error !== ""): ?>
        <div class="error"><?= h($error) ?></div>
      <?php endif; ?>

      <?php if ($editTicket): ?>
        <div class="box">
          <form method="post" action="<?= h($selfEdit) ?>">
            <input type="hidden" name="accion" value="editar">
            <input type="hidden" name="id" value="<?= (int)$editTicket["id"] ?>">
            <input type="hidden" name="return" value="<?= h($returnFromEdit) ?>">

            <div class="row">
              <div>
                <div class="muted">Minecraft user</div>
                <input name="minecraft_user" maxlength="32" value="<?= h($old["minecraft_user"]) ?>">
              </div>

              <div>
                <div class="muted">Tipo</div>
                <select name="tipo">
                  <option value="ayuda" <?= $old["tipo"]==="ayuda" ? "selected" : "" ?>>ayuda</option>
                  <option value="bug" <?= $old["tipo"]==="bug" ? "selected" : "" ?>>bug</option>
                  <option value="reporte" <?= $old["tipo"]==="reporte" ? "selected" : "" ?>>reporte</option>
                  <option value="sugerencia" <?= $old["tipo"]==="sugerencia" ? "selected" : "" ?>>sugerencia</option>
                </select>
              </div>

              <div>
                <div class="muted">Prioridad</div>
                <select name="prioridad">
                  <option value="baja" <?= $old["prioridad"]==="baja" ? "selected" : "" ?>>baja</option>
                  <option value="media" <?= $old["prioridad"]==="media" ? "selected" : "" ?>>media</option>
                  <option value="alta" <?= $old["prioridad"]==="alta" ? "selected" : "" ?>>alta</option>
                </select>
              </div>
            </div>

            <div style="margin-top:10px;">
              <div class="muted">Título</div>
              <input name="titulo" maxlength="120" style="width:100%;" value="<?= h($old["titulo"]) ?>">
            </div>

            <div style="margin-top:10px;">
              <div class="muted">Descripción</div>
              <textarea name="descripcion"><?= h($old["descripcion"]) ?></textarea>
            </div>

            <div style="margin-top:12px; display:flex; gap:10px; flex-wrap:wrap;">
              <button type="submit">Guardar cambios</button>
              <a class="btnlink" href="?vista=ver&id=<?= (int)$editTicket["id"] ?>&return=<?= rawurlencode($returnFromEdit) ?>">Cancelar</a>
            </div>
          </form>
        </div>
      <?php endif; ?>

    <?php else: ?>

      <h1>Listado de tickets</h1>
      <p class="muted">Listado real desde BD. Búsqueda: <?= $useFullText ? "FULLTEXT" : "LIKE" ?>.</p>

      <div class="box">
        <form method="get" class="row">
          <input type="hidden" name="vista" value="list">

          <div>
            <div class="muted">Buscar</div>
            <input name="q" value="<?= h($q) ?>" placeholder="spawn, robo, plugin...">
          </div>

          <div>
            <div class="muted">Estado</div>
            <select name="estado">
              <option value="" <?= $estado==="" ? "selected" : "" ?>>todos</option>
              <option value="abierto" <?= $estado==="abierto" ? "selected" : "" ?>>abierto</option>
              <option value="en_proceso" <?= $estado==="en_proceso" ? "selected" : "" ?>>en_proceso</option>
              <option value="cerrado" <?= $estado==="cerrado" ? "selected" : "" ?>>cerrado</option>
            </select>
          </div>

          <div>
            <div class="muted">Tipo</div>
            <select name="tipo">
              <option value="" <?= $tipo==="" ? "selected" : "" ?>>todos</option>
              <option value="bug" <?= $tipo==="bug" ? "selected" : "" ?>>bug</option>
              <option value="reporte" <?= $tipo==="reporte" ? "selected" : "" ?>>reporte</option>
              <option value="ayuda" <?= $tipo==="ayuda" ? "selected" : "" ?>>ayuda</option>
              <option value="sugerencia" <?= $tipo==="sugerencia" ? "selected" : "" ?>>sugerencia</option>
            </select>
          </div>

          <div>
            <div class="muted">Orden</div>
            <select name="order">
              <option value="new" <?= $order==="new" ? "selected" : "" ?>>más nuevos</option>
              <option value="old" <?= $order==="old" ? "selected" : "" ?>>más antiguos</option>
              <option value="prio" <?= $order==="prio" ? "selected" : "" ?>>prioridad</option>
            </select>
          </div>

          <div style="align-self:end;">
            <button type="submit">Aplicar</button>
          </div>
        </form>
      </div>

      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Minecraft user</th>
            <th>Tipo</th>
            <th>Título (ver)</th>
            <th>Prioridad</th>
            <th>Estado</th>
            <th>Creado</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$tickets): ?>
            <tr><td colspan="8">No hay tickets con esos filtros.</td></tr>
          <?php else: ?>
            <?php foreach ($tickets as $t): ?>
              <tr>
                <td><?= (int)$t["id"] ?></td>
                <td><?= h($t["minecraft_user"]) ?></td>
                <td><?= h($t["tipo"]) ?></td>
                <td>
                  <a class="btnlink" href="?vista=ver&id=<?= (int)$t["id"] ?>&return=<?= rawurlencode($returnHere) ?>">
                    <?= h($t["titulo"]) ?>
                  </a>
                </td>
                <td>
                  <span class="badge prio-<?= prioClass($t["prioridad"]) ?>"><?= h($t["prioridad"]) ?></span>
                </td>
                <td>
                  <span class="badge estado-<?= estadoClass($t["estado"]) ?>"><?= h($t["estado"]) ?></span>
                </td>
                <td class="muted"><?= h($t["creado_en"]) ?></td>

                <td>
                  <a class="btnlink" href="?vista=edit&id=<?= (int)$t["id"] ?>&return=<?= rawurlencode($returnHere) ?>">Editar</a>

                  <form class="inline" method="post" action="<?= h($returnHere) ?>">
                    <input type="hidden" name="accion" value="estado">
                    <input type="hidden" name="id" value="<?= (int)$t["id"] ?>">
                    <input type="hidden" name="estado" value="cerrado">
                    <input type="hidden" name="return" value="<?= h($returnHere) ?>">
                    <button type="submit">Cerrar</button>
                  </form>

                  <form class="inline" method="post" action="<?= h($returnHere) ?>"
                        onsubmit="return confirm('¿Borrar ticket #<?= (int)$t['id'] ?>?');">
                    <input type="hidden" name="accion" value="borrar">
                    <input type="hidden" name="id" value="<?= (int)$t["id"] ?>">
                    <input type="hidden" name="return" value="<?= h($returnHere) ?>">
                    <button type="submit" style="background:var(--danger);">Borrar</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>

    <?php endif; ?>
  </main>

</body>
</html>
