<?php
// SELECT (listado real) + formulario "Nuevo" + INSERT por POST

$vista = isset($_GET["vista"]) ? (string)$_GET["vista"] : "list";
if ($vista !== "list" && $vista !== "new") $vista = "list";

/* ========= allowlists ========= */
$estadosOk = ["", "abierto", "en_proceso", "cerrado"];
$tiposOk   = ["", "bug", "reporte", "ayuda", "sugerencia"];
$ordersOk  = ["new", "old", "prio"];
$tiposPostOk = ["bug","reporte","ayuda","sugerencia"];
$priosPostOk = ["baja","media","alta"];

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
   POST: INSERT (solo crear ticket)
   ========================================================= */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  // Recoger inputs
  $old["minecraft_user"] = trim((string)($_POST["minecraft_user"] ?? ""));
  $old["tipo"]           = (string)($_POST["tipo"] ?? "ayuda");
  $old["prioridad"]      = (string)($_POST["prioridad"] ?? "media");
  $old["titulo"]         = trim((string)($_POST["titulo"] ?? ""));
  $old["descripcion"]    = trim((string)($_POST["descripcion"] ?? ""));

  // Validación mínima
  if ($old["minecraft_user"] === "" || $old["titulo"] === "" || $old["descripcion"] === "") {
    $error = "Rellena usuario, título y descripción.";
    $vista = "new";
  } else {
    // Normalizar valores con allowlist
    if (!in_array($old["tipo"], $tiposPostOk, true)) $old["tipo"] = "ayuda";
    if (!in_array($old["prioridad"], $priosPostOk, true)) $old["prioridad"] = "media";

    // Conexión
    $mysqli = new mysqli("localhost", "mcadmin", "mcadmin123", "mc_server_tickets");
    if ($mysqli->connect_error) die("Error DB: " . $mysqli->connect_error);
    $mysqli->set_charset("utf8mb4");

    // INSERT (estado siempre empieza en 'abierto')
    $stmt = $mysqli->prepare("
      INSERT INTO mc_tickets (minecraft_user, tipo, titulo, descripcion, prioridad, estado)
      VALUES (?,?,?,?,?,'abierto')
    ");
    if ($stmt === false) die("Error preparando INSERT: " . $mysqli->error);

    $stmt->bind_param("sssss", $old["minecraft_user"], $old["tipo"], $old["titulo"], $old["descripcion"], $old["prioridad"]);
    $stmt->execute();

    // Redirect al listado (patrón POST->Redirect->GET)
    header("Location: ?vista=list");
    exit;
  }
}

/* =========================================================
   GET: filtros (solo list)
   ========================================================= */
$q      = isset($_GET["q"]) ? trim((string)$_GET["q"]) : "";
$estado = isset($_GET["estado"]) ? (string)$_GET["estado"] : "";
$tipo   = isset($_GET["tipo"]) ? (string)$_GET["tipo"] : "";
$order  = isset($_GET["order"]) ? (string)$_GET["order"] : "new";

if (!in_array($estado, $estadosOk, true)) $estado = "";
if (!in_array($tipo, $tiposOk, true)) $tipo = "";
if (!in_array($order, $ordersOk, true)) $order = "new";

/* =========================================================
   SELECT: cargar tickets (solo vista=list)
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

  // FULLTEXT si hay q
  if ($q !== "") {
    $useFullText = true;
    $where[] = "MATCH(titulo, descripcion) AGAINST (? IN NATURAL LANGUAGE MODE)";
    $params[] = $q;
    $types .= "s";
  }

  if ($estado !== "") {
    $where[] = "estado = ?";
    $params[] = $estado;
    $types .= "s";
  }

  if ($tipo !== "") {
    $where[] = "tipo = ?";
    $params[] = $tipo;
    $types .= "s";
  }

  $sql = "SELECT id, minecraft_user, tipo, titulo, prioridad, estado, creado_en
          FROM mc_tickets";
  if ($where) $sql .= " WHERE " . implode(" AND ", $where);

  if ($order === "old") {
    $sql .= " ORDER BY creado_en ASC";
  } elseif ($order === "prio") {
    $sql .= " ORDER BY FIELD(prioridad,'alta','media','baja'), creado_en DESC";
  } else {
    $sql .= " ORDER BY creado_en DESC";
  }

  $stmt = $mysqli->prepare($sql);

  // Fallback LIKE si FULLTEXT falla
  if ($stmt === false && $q !== "") {
    $useFullText = false;

    $where  = [];
    $params = [];
    $types  = "";

    $where[] = "(titulo LIKE ? OR descripcion LIKE ?)";
    $like = "%".$q."%";
    $params[] = $like;
    $params[] = $like;
    $types .= "ss";

    if ($estado !== "") { $where[] = "estado = ?"; $params[] = $estado; $types .= "s"; }
    if ($tipo   !== "") { $where[] = "tipo = ?";   $params[] = $tipo;   $types .= "s"; }

    $sql = "SELECT id, minecraft_user, tipo, titulo, prioridad, estado, creado_en
            FROM mc_tickets
            WHERE " . implode(" AND ", $where);

    if ($order === "old") {
      $sql .= " ORDER BY creado_en ASC";
    } elseif ($order === "prio") {
      $sql .= " ORDER BY FIELD(prioridad,'alta','media','baja'), creado_en DESC";
    } else {
      $sql .= " ORDER BY creado_en DESC";
    }

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
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Tickets Minecraft</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body{ font-family: system-ui, -apple-system, "Segoe UI", sans-serif; margin:0; padding:16px; }
    nav a{ margin-right:10px; }
    table{ width:100%; border-collapse:collapse; margin-top:12px; }
    th,td{ border:1px solid #ddd; padding:8px; text-align:left; vertical-align:top; }
    th{ background:#f2f2f2; }
    .muted{ color:#666; font-size:12px; }
    .row{ display:flex; gap:10px; flex-wrap:wrap; margin-top:10px; }
    input,select,textarea{ padding:6px; }
    textarea{ width:100%; min-height:110px; }
    .box{ border:1px solid #ddd; padding:12px; margin-top:12px; }
    .error{ border:1px solid #b91c1c; background:#fee2e2; padding:10px; margin-top:10px; }
  </style>
</head>
<body>

  <nav>
    <strong>Tickets MC</strong>
    <a href="?vista=list">Listado</a>
    <a href="?vista=new">Nuevo</a>
  </nav>

  <main>
    <?php if ($vista === "new"): ?>

      <h1>Nuevo ticket</h1>
      <p class="muted">Este formulario ya guarda por POST (INSERT).</p>

      <?php if ($error !== ""): ?>
        <div class="error"><?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?></div>
      <?php endif; ?>

      <div class="box">
        <form method="post" action="?vista=new">
          <div class="row">
            <div>
              <div class="muted">Minecraft user</div>
              <input name="minecraft_user" maxlength="32"
                     value="<?= htmlspecialchars($old["minecraft_user"], ENT_QUOTES, "UTF-8") ?>"
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
                   value="<?= htmlspecialchars($old["titulo"], ENT_QUOTES, "UTF-8") ?>"
                   placeholder="Ej: Se bugueó el spawn">
          </div>

          <div style="margin-top:10px;">
            <div class="muted">Descripción</div>
            <textarea name="descripcion" placeholder="Describe el problema..."><?= htmlspecialchars($old["descripcion"], ENT_QUOTES, "UTF-8") ?></textarea>
          </div>

          <div style="margin-top:10px;">
            <button type="submit">Guardar</button>
            <a href="?vista=list">Volver</a>

          </div>
        </form>
      </div>

    <?php else: ?>

      <h1>Listado de tickets</h1>
      <p class="muted">Listado real desde BD. Búsqueda: <?= $useFullText ? "FULLTEXT" : "LIKE" ?>.</p>

      <!-- Filtros GET -->
      <form method="get" class="row">
        <input type="hidden" name="vista" value="list">

        <div>
          <div class="muted">Buscar</div>
          <input name="q" value="<?= htmlspecialchars($q, ENT_QUOTES, "UTF-8") ?>" placeholder="spawn, robo, plugin...">
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

      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Minecraft user</th>
            <th>Tipo</th>
            <th>Título</th>
            <th>Prioridad</th>
            <th>Estado</th>
            <th>Creado</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$tickets): ?>
            <tr><td colspan="7">No hay tickets con esos filtros.</td></tr>
          <?php else: ?>
            <?php foreach ($tickets as $t): ?>
              <tr>
                <td><?= (int)$t["id"] ?></td>
                <td><?= htmlspecialchars((string)$t["minecraft_user"], ENT_QUOTES, "UTF-8") ?></td>
                <td><?= htmlspecialchars((string)$t["tipo"], ENT_QUOTES, "UTF-8") ?></td>
                <td><?= htmlspecialchars((string)$t["titulo"], ENT_QUOTES, "UTF-8") ?></td>
                <td><?= htmlspecialchars((string)$t["prioridad"], ENT_QUOTES, "UTF-8") ?></td>
                <td><?= htmlspecialchars((string)$t["estado"], ENT_QUOTES, "UTF-8") ?></td>
                <td><?= htmlspecialchars((string)$t["creado_en"], ENT_QUOTES, "UTF-8") ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>

    <?php endif; ?>
  </main>

</body>
</html>
