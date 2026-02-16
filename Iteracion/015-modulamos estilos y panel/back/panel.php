<?php
/**
 * panel.php — Controlador principal del panel de administración.
 * Este archivo actúa como "orquestador": carga la lógica, la estructura 
 * visual principal y decide qué vista interna mostrar según la navegación.
 */

// 1. Cargar la lógica de negocio 
// (Control de sesión, conexión a BD, operaciones CRUD y filtros)
require_once "inc/panel_logic.php";

// 2. Cargar la parte superior de la plantilla HTML 
// (<!doctype>, <head>, sidebar y apertura de <main>)
require_once "inc/header.php";

/* ---------------------------------------------------------
   SISTEMA DE VISTAS
   Dependiendo de la variable $vista (calculada en panel_logic.php),
   incluimos el archivo de vista correspondiente.
   --------------------------------------------------------- */

if ($vista === "new") {
  // Vista: Formulario para crear un nuevo ticket
  require "views/view_new.php";

} elseif ($vista === "ver") {
  // Vista: Detalle completo de un ticket (incluye botones de acción)
  require "views/view_ver.php";

} elseif ($vista === "edit") {
  // Vista: Formulario para editar un ticket existente
  require "views/view_edit.php";

} else {
  // Vista por defecto: Listado (tabla) de tickets con filtros
  require "views/view_list.php";
}

// 3. Cargar el cierre de la plantilla HTML
// (Cierre de <main>, scripts si los hubiera, </body></html>)
require_once "inc/footer.php";
?>
