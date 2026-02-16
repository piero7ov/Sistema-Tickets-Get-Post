<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Tickets Minecraft</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="css/panel.css">
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
