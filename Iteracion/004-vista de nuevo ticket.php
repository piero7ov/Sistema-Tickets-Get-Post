<?php
// Listado real (SELECT) + Vista "Nuevo" (solo formulario, NO guarda aún)

$vista = isset($_GET["vista"]) ? (string)$_GET["vista"] : "list";
if ($vista !== "list" && $vista !== "new") $vista = "list";

/* ========= GET filtros (solo en list) ========= */
$q      = isset($_GET["q"]) ? trim((string)$_GET["q"]) : "";
$estado = isset($_GET["estado"]) ? (string)$_GET["estado"] : "";
$tipo   = isset($_GET["tipo"]) ? (string)$_GET["tipo"] : "";
$order  = isset($_GET["order"]) ? (string)$_GET["order"] : "new";

$estadosOk = ["", "abierto", "en_proceso", "cerrado"];
$tiposOk   = ["", "bug", "reporte", "ayuda", "sugerencia"];
$ordersOk  = ["new", "old", "prio"];

if (!in_array($estado, $estadosOk, true)) $estado = "";
if (!in_array($tipo, $tiposOk, true)) $tipo = "";
if (!in_array($order, $ordersOk, true)) $order = "new";

/* ========= Cargar tickets (solo si vista=list) ========= */
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

  if ($stmt === false) die("Error preparando SQL: " . $mysqli->error);

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
  </style>
</head>
<body>

  <nav>
    <strong>Tickets MC</strong>
    <a href="index.php?vista=list">Listado</a>
    <a href="index.php?vista=new">Nuevo</a>
  </nav>

  <main>
    <?php if ($vista === "new"): ?>

      <h1>Nuevo ticket</h1>
      <p class="muted">formulario solo de maqueta.</p>

      <div class="box">
        <form method="post" action="#">
          <div class="row">
            <div>
              <div class="muted">Minecraft user</div>
              <input name="minecraft_user" maxlength="32" placeholder="Ej: PieroDev">
            </div>

            <div>
              <div class="muted">Tipo</div>
              <select name="tipo">
                <option value="ayuda">ayuda</option>
                <option value="bug">bug</option>
                <option value="reporte">reporte</option>
                <option value="sugerencia">sugerencia</option>
              </select>
            </div>

            <div>
              <div class="muted">Prioridad</div>
              <select name="prioridad">
                <option value="baja">baja</option>
                <option value="media" selected>media</option>
                <option value="alta">alta</option>
              </select>
            </div>
          </div>

          <div style="margin-top:10px;">
            <div class="muted">Título</div>
            <input name="titulo" maxlength="120" style="width:100%;" placeholder="Ej: Se bugueó el spawn">
          </div>

          <div style="margin-top:10px;">
            <div class="muted">Descripción</div>
            <textarea name="descripcion" placeholder="Describe el problema..."></textarea>
          </div>

          <div style="margin-top:10px;">
            <button type="submit">Guardar (aún no)</button>
            <a href="index.php?vista=list">Volver</a>
          </div>
        </form>
      </div>

    <?php else: ?>

      <h1>Listado de tickets</h1>
      <p class="muted">listado real desde BD. Búsqueda: <?= $useFullText ? "FULLTEXT" : "LIKE" ?>.</p>

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
