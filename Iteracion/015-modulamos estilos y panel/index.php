<?php
// index.php — Front público: crear ticket (INSERT)

// Iniciar sesión para controlar si hay un admin logueado
session_start();

/* =========================================
   CONFIGURACIÓN DE LA BASE DE DATOS
   ========================================= */
$dbHost = "localhost";
$dbUser = "mcadmin";
$dbPass = "mcadmin123";
$dbName = "mc_server_tickets";

/* =========================================
   FUNCIONES DE AYUDA (HELPERS)
   ========================================= */
// Función para escapar caracteres especiales HTML (evita XSS)
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, "UTF-8"); }

/* =========================================
   LISTAS PERMITIDAS (ALLOWLISTS)
   ========================================= */
// Definimos qué valores son válidos para tipos y prioridades
$tiposOk = ["ayuda","bug","reporte","sugerencia"];
$priosOk = ["baja","media","alta"];

/* =========================================
   DETECTAR SESIÓN DE ADMIN
   ========================================= */
// Comprobamos si existe alguna variable de sesión que indique que es admin
$adminLogged =
  isset($_SESSION["mc_user"]) ||
  isset($_SESSION["user"]) ||
  isset($_SESSION["username"]) ||
  isset($_SESSION["auth"]);

// Definimos el enlace y texto del botón de admin según si está logueado o no
$adminHref = $adminLogged ? "back/panel.php" : "back/login.php";
$adminText = $adminLogged ? "Ir al panel" : "Ir a admin";

/* =========================================
   VARIABLES DE ESTADO DE LA UI
   ========================================= */
$ok = false;       // Para saber si el ticket se creó con éxito
$ticketId = 0;     // Para mostrar el ID del ticket creado
$error = "";       // Para guardar mensajes de error

// Array para repoblar el formulario en caso de error (mantener lo que escribió el usuario)
$old = [
  "minecraft_user" => "",
  "tipo" => "ayuda",
  "prioridad" => "media",
  "titulo" => "",
  "descripcion" => ""
];

/* =========================================
   PROCESAR FORMULARIO (POST)
   ========================================= */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

  // Recogemos y limpiamos los datos del POST
  $old["minecraft_user"] = trim((string)($_POST["minecraft_user"] ?? ""));
  $old["tipo"]           = (string)($_POST["tipo"] ?? "ayuda");
  $old["prioridad"]      = (string)($_POST["prioridad"] ?? "media");
  $old["titulo"]         = trim((string)($_POST["titulo"] ?? ""));
  $old["descripcion"]    = trim((string)($_POST["descripcion"] ?? ""));

  // Validamos que el tipo y prioridad estén en las listas permitidas
  if (!in_array($old["tipo"], $tiposOk, true)) $old["tipo"] = "ayuda";
  if (!in_array($old["prioridad"], $priosOk, true)) $old["prioridad"] = "media";

  // Validaciones básicas de campos obligatorios y longitud
  if ($old["minecraft_user"] === "" || $old["titulo"] === "" || $old["descripcion"] === "") {
    $error = "Rellena usuario, título y descripción.";
  } elseif (mb_strlen($old["minecraft_user"], "UTF-8") > 32) {
    $error = "El usuario de Minecraft no puede pasar de 32 caracteres.";
  } elseif (mb_strlen($old["titulo"], "UTF-8") > 120) {
    $error = "El título no puede pasar de 120 caracteres.";
  } else {
    // Si todo es válido, conectamos a la BD
    $mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
    if ($mysqli->connect_error) {
      $error = "Error DB: " . $mysqli->connect_error;
    } else {
      $mysqli->set_charset("utf8mb4");

      // Preparamos la consulta INSERT (seguridad contra SQL Injection)
      $stmt = $mysqli->prepare("
        INSERT INTO mc_tickets (minecraft_user, tipo, titulo, descripcion, prioridad, estado)
        VALUES (?,?,?,?,?,'abierto')
      ");
      
      if ($stmt === false) {
        $error = "Error preparando INSERT: " . $mysqli->error;
      } else {
        // Vinculamos los parámetros s=string
        $stmt->bind_param(
          "sssss",
          $old["minecraft_user"],
          $old["tipo"],
          $old["titulo"],
          $old["descripcion"],
          $old["prioridad"]
        );

        // Ejecutamos la consulta
        if ($stmt->execute()) {
          $ok = true;
          $ticketId = (int)$mysqli->insert_id; // Obtenemos el ID generado

          // Limpiamos el formulario para que aparezca vacío tras el éxito
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
  <link rel="stylesheet" href="index.css">
</head>
<body>

  <!-- Botón superior para ir al área de administración -->
  <a class="admin-btn" href="<?= h($adminHref) ?>">🛠️ <?= h($adminText) ?></a>

  <div class="wrap">

    <div class="card">
      <h1>Crear ticket</h1>
      <div class="muted">Soporte del servidor Minecraft</div>

      <!-- Mensaje de éxito si se creó el ticket -->
      <?php if ($ok): ?>
        <div class="ok">
          ✅ Ticket creado correctamente. ID: #<?= (int)$ticketId ?>
          <div class="tiny" style="margin-top:6px; font-weight:800;">
            Un admin lo verá en el panel.
          </div>
        </div>
      <?php endif; ?>

      <!-- Mensaje de error si falló algo -->
      <?php if ($error !== ""): ?>
        <div class="error">⚠️ <?= h($error) ?></div>
      <?php endif; ?>
    </div>

    <!-- Formulario de creación de ticket -->
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
