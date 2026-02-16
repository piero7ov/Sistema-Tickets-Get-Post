<?php
// panel_logic.php — Lógica de negocio del panel

session_start();

/* =========================================
   1. SEGURIDAD (GUARD)
   ========================================= */
// Verificamos si el usuario tiene permiso para estar aquí
if (empty($_SESSION["auth"])) {
  // Si no está logueado, lo mandamos al login
  header("Location: login.php");
  exit;
}

/* =========================================
   2. DETERMINAR VISTA ACTUAL
   ========================================= */
// Panel (SELECT) + Nuevo (INSERT) + Cambiar estado (UPDATE) + Borrar (DELETE) + Ver (GET) + Editar (GET+POST UPDATE)
$vista = isset($_GET["vista"]) ? (string)$_GET["vista"] : "list";

// Lista blanca de vistas permitidas. Si no es válida, forzamos "list"
if (!in_array($vista, ["list","new","ver","edit"], true)) $vista = "list";

/* =========================================
   3. DEFINICIÓN DE LISTAS (Allowlists)
   ========================================= */
// Estados posibles para filtros y lógica
$estadosOk = ["", "abierto", "en_proceso", "cerrado"];
$estadosPostOk = ["abierto", "en_proceso", "cerrado"];

// Tipos de ticket y prioridades
$tiposOk   = ["", "bug", "reporte", "ayuda", "sugerencia"];
$ordersOk  = ["new", "old", "prio"];
$tiposPostOk = ["bug","reporte","ayuda","sugerencia"];
$priosPostOk = ["baja","media","alta"];

/* =========================================
   4. FUNCIONES HELPERS
   ========================================= */
// Escapado HTML para seguridad
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, "UTF-8"); }

// Devuelve la clase CSS según el estado
function estadoClass($e){
  $e = (string)$e;
  if ($e === "en_proceso") return "en-proceso";
  if ($e === "abierto") return "abierto";
  if ($e === "cerrado") return "cerrado";
  return "abierto";
}

// Devuelve la clase CSS según la prioridad
function prioClass($p){
  $p = (string)$p;
  if ($p === "alta") return "alta";
  if ($p === "media") return "media";
  if ($p === "baja") return "baja";
  return "media";
}

/* =========================================
   5. VARIABLES INICIALES
   ========================================= */
$error = "";
// Array para mantener datos del formulario
$old = [
  "minecraft_user" => "",
  "tipo" => "ayuda",
  "prioridad" => "media",
  "titulo" => "",
  "descripcion" => ""
];

/* =========================================
   6. PROCESAMIENTO DE FORMULARIOS (POST)
   ========================================= */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  
  // Identificamos qué acción se quiere realizar
  $accion = isset($_POST["accion"]) ? (string)$_POST["accion"] : "crear";

  // URL de retorno: a dónde ir después de la acción
  $return = isset($_POST["return"]) ? (string)$_POST["return"] : "?vista=list";
  if ($return === "" || strpos($return, "?") !== 0) $return = "?vista=list"; 

  // --- A) CAMBIAR ESTADO (UPDATE) ---
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
    // Redirección POST-Redirect-GET
    header("Location: " . $return);
    exit;
  }

  // --- B) BORRAR TICKET (DELETE) ---
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

  // --- C) EDITAR TICKET (UPDATE) ---
  if ($accion === "editar") {
    $id = isset($_POST["id"]) ? (int)$_POST["id"] : 0;

    // Recoger datos
    $old["minecraft_user"] = trim((string)($_POST["minecraft_user"] ?? ""));
    $old["tipo"]           = (string)($_POST["tipo"] ?? "ayuda");
    $old["prioridad"]      = (string)($_POST["prioridad"] ?? "media");
    $old["titulo"]         = trim((string)($_POST["titulo"] ?? ""));
    $old["descripcion"]    = trim((string)($_POST["descripcion"] ?? ""));

    // Validar
    if ($id <= 0) {
      $error = "ID inválido.";
      $vista = "edit"; // Nos quedamos en la vista edit para mostrar el error
    } elseif ($old["minecraft_user"] === "" || $old["titulo"] === "" || $old["descripcion"] === "") {
      $error = "Rellena usuario, título y descripción.";
      $vista = "edit";
    } else {
      // Normalizar listas
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

      // Al terminar de editar, volvemos a la vista "ver ticket"
      $go = "?" . http_build_query([
        "vista" => "ver",
        "id" => $id,
        "return" => $return
      ]);
      header("Location: " . $go);
      exit;
    }
  }

  // --- D) CREAR TICKET (INSERT) ---
  // Si no es ninguno de los anteriores, asumimos "crear" (accion por defecto)
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

/* =========================================
   7. PREPARAR DATOS PARA LA VISTA (GET)
   ========================================= */

// --- Recoger filtros de la URL ---
$q      = isset($_GET["q"]) ? trim((string)$_GET["q"]) : "";
$estado = isset($_GET["estado"]) ? (string)$_GET["estado"] : "";
$tipo   = isset($_GET["tipo"]) ? (string)$_GET["tipo"] : "";
$order  = isset($_GET["order"]) ? (string)$_GET["order"] : "new";

// Validar filtros
if (!in_array($estado, $estadosOk, true)) $estado = "";
if (!in_array($tipo, $tiposOk, true)) $tipo = "";
if (!in_array($order, $ordersOk, true)) $order = "new";

// Construir URL de retorno para mantener los filtros activos
$returnHere = "?" . http_build_query([
  "vista"  => "list",
  "q"      => $q,
  "estado" => $estado,
  "tipo"   => $tipo,
  "order"  => $order
]);

/* --- A) LÓGICA PARA VISTA 'LIST' --- */
$tickets = [];
$useFullText = false;

if ($vista === "list") {
  $mysqli = new mysqli("localhost", "mcadmin", "mcadmin123", "mc_server_tickets");
  if ($mysqli->connect_error) die("Error DB: " . $mysqli->connect_error);
  $mysqli->set_charset("utf8mb4");

  $where  = [];
  $params = [];
  $types  = "";

  // 1. Filtro de búsqueda (FullText)
  if ($q !== "") {
    $useFullText = true;
    $where[] = "MATCH(titulo, descripcion) AGAINST (? IN NATURAL LANGUAGE MODE)";
    $params[] = $q;
    $types .= "s";
  }

  // 2. Filtros exactos
  if ($estado !== "") { $where[] = "estado = ?"; $params[] = $estado; $types .= "s"; }
  if ($tipo   !== "") { $where[] = "tipo = ?";   $params[] = $tipo;   $types .= "s"; }

  // 3. Montar Query
  $sql = "SELECT id, minecraft_user, tipo, titulo, prioridad, estado, creado_en
          FROM mc_tickets";
  if ($where) $sql .= " WHERE " . implode(" AND ", $where);

  // 4. Ordenación
  if ($order === "old")       $sql .= " ORDER BY creado_en ASC";
  elseif ($order === "prio")  $sql .= " ORDER BY FIELD(prioridad,'alta','media','baja'), creado_en DESC";
  else                        $sql .= " ORDER BY creado_en DESC";

  $stmt = $mysqli->prepare($sql);

  // Fallback a LIKE si la búsqueda FULLTEXT falla (por ej. tabla MyISAM vs InnoDB o config)
  if ($stmt === false && $q !== "") {
    $useFullText = false;
    // ... lógica de fallback con LIKE ...
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

/* --- B) LÓGICA PARA VISTA 'VER' --- */
$ticket = null;
$verError = "";
$returnFromView = "?vista=list";
$selfView = "?vista=list";

if ($vista === "ver") {
  $idVer = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

  // Calcular URL de retorno
  if (isset($_GET["return"])) {
    $tmp = (string)$_GET["return"];
    if ($tmp !== "" && strpos($tmp, "?") === 0) $returnFromView = $tmp;
  } else {
    $returnFromView = $returnHere;
  }

  // Obtener ticket
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

/* --- C) LÓGICA PARA VISTA 'EDIT' --- */
$editTicket = null;
$editError = "";
$returnFromEdit = "?vista=list";
$selfEdit = "?vista=list";

if ($vista === "edit") {
  // ID puede venir por GET (al entrar) o POST (si hubo error al guardar)
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

    // Si encontramos el ticket y no hay un error previo de validación (POST),
    // rellenamos el formulario con los datos de BD.
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
