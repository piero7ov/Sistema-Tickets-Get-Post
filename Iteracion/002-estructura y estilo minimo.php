<?php
$vista = isset($_GET["vista"]) ? (string)$_GET["vista"] : "list";
if ($vista !== "list") $vista = "list";
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
    input,select{ padding:6px; }
  </style>
</head>

<body>

  <nav>
    <strong>Tickets MC</strong>
    <a href="?vista=list">Listado</a>
  </nav>

  <main>
    <!-- =========================
         VISTA: LISTADO
         ========================= -->
    <h1>Listado de tickets</h1>

    <!-- Filtros GET -->
    <form method="get" class="row">
      <input type="hidden" name="vista" value="list">

      <div>
        <label class="muted">Buscar</label><br>
        <input name="q" placeholder="spawn, robo, plugin...">
      </div>

      <div>
        <label class="muted">Estado</label><br>
        <select name="estado">
          <option value="">todos</option>
          <option value="abierto">abierto</option>
          <option value="en_proceso">en_proceso</option>
          <option value="cerrado">cerrado</option>
        </select>
      </div>

      <div>
        <label class="muted">Tipo</label><br>
        <select name="tipo">
          <option value="">todos</option>
          <option value="bug">bug</option>
          <option value="reporte">reporte</option>
          <option value="ayuda">ayuda</option>
          <option value="sugerencia">sugerencia</option>
        </select>
      </div>

      <div>
        <label class="muted">Orden</label><br>
        <select name="order">
          <option value="new">más nuevos</option>
          <option value="old">más antiguos</option>
          <option value="prio">prioridad</option>
        </select>
      </div>

      <div style="align-self:end;">
        <button type="submit">Aplicar</button>
      </div>
    </form>

    <!-- Tabla estática -->
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
        <tr>
          <td>1</td>
          <td>PieroDev</td>
          <td>bug</td>
          <td>Se bugueó el spawn</td>
          <td>alta</td>
          <td>abierto</td>
          <td>2026-02-16 12:00</td>
        </tr>
        <tr>
          <td>2</td>
          <td>Flavia</td>
          <td>ayuda</td>
          <td>No puedo reclamar mi casa</td>
          <td>media</td>
          <td>en_proceso</td>
          <td>2026-02-16 12:05</td>
        </tr>
      </tbody>
    </table>

  </main>

</body>
</html>
