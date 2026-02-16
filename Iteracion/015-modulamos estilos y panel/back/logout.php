<?php
// logout.php — Cerrar sesión de manera segura

session_start();

/* =========================================
   1. LIMPIAR VARIABLES DE SESIÓN
   ========================================= */
$_SESSION = [];

/* =========================================
   2. BORRAR LA COOKIE DE SESIÓN
   ========================================= */
// Si se usan cookies para propagar la sesión (lo normal),
// hay que decirle al navegador que la cookie ya no vale.
if (ini_get("session.use_cookies")) {
  $p = session_get_cookie_params();
  setcookie(
    session_name(), // Nombre de la cookie (ej: PHPSESSID)
    "",             // Valor vacío
    time() - 42000, // Fecha en el pasado para que caduque ya
    $p["path"],
    $p["domain"],
    $p["secure"],
    $p["httponly"]
  );
}

/* =========================================
   3. DESTRUIR LA SESIÓN EN EL SERVIDOR
   ========================================= */
session_destroy();

/* =========================================
   4. REDRIGIR AL LOGIN
   ========================================= */
header("Location: login.php");
exit;
