/* =========================================================
   MODELO DE DATOS: Sistema de Tickets (Minecraft)
   ========================================================= */

/* ---------------------------------------------------------
   1) CREAR BASE DE DATOS
   --------------------------------------------------------- */
CREATE DATABASE IF NOT EXISTS mc_server_tickets
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

/* ---------------------------------------------------------
   2) CREAR USUARIO PARA ESTA BD
   --------------------------------------------------------- */
CREATE USER IF NOT EXISTS 'mcadmin'@'localhost' IDENTIFIED BY 'mcadmin123';

/* Dar permisos SOLO a esta base de datos (buena práctica) */
GRANT ALL PRIVILEGES ON mc_server_tickets.* TO 'mcadmin'@'localhost';

FLUSH PRIVILEGES;

/* ---------------------------------------------------------
   3) SELECCIONAR LA BASE DE DATOS
   --------------------------------------------------------- */
USE mc_server_tickets;

/* ---------------------------------------------------------
   4) CREAR TABLA PRINCIPAL: mc_tickets
   - id: clave primaria autoincremental
   - minecraft_user: nick del jugador (hasta 32)
   - tipo: categoría del ticket (bug, reporte, ayuda, sugerencia)
   - prioridad: baja / media / alta
   - estado: abierto / en_proceso / cerrado
   - creado_en: fecha de creación automática
   - actualizado_en: se actualiza solo al modificar el registro
   --------------------------------------------------------- */
CREATE TABLE IF NOT EXISTS mc_tickets (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

  minecraft_user VARCHAR(32) NOT NULL,  -- nick del jugador
  tipo ENUM('bug','reporte','ayuda','sugerencia') NOT NULL DEFAULT 'ayuda',

  titulo VARCHAR(120) NOT NULL,
  descripcion TEXT NOT NULL,

  prioridad ENUM('baja','media','alta') NOT NULL DEFAULT 'media',
  estado ENUM('abierto','en_proceso','cerrado') NOT NULL DEFAULT 'abierto',

  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  /* Índices para que los filtros vayan rápidos (estado/tipo/prioridad/usuario) */
  INDEX idx_estado (estado),
  INDEX idx_tipo (tipo),
  INDEX idx_prioridad (prioridad),
  INDEX idx_user (minecraft_user)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* ---------------------------------------------------------
   4.1) FULLTEXT para búsqueda por texto
   - Útil para un "buscador" rápido en titulo+descripcion..
   --------------------------------------------------------- */
ALTER TABLE mc_tickets
  ADD FULLTEXT KEY ft_texto (titulo, descripcion);

/* ---------------------------------------------------------
   5) DATOS DE PRUEBA (INSERTS)
   - Para que al abrir tu panel ya tengas tickets
   --------------------------------------------------------- */
INSERT INTO mc_tickets (minecraft_user, tipo, titulo, descripcion, prioridad, estado)
VALUES
('PieroDev', 'bug', 'Se bugueó el spawn', 'Al entrar aparezco dentro de un bloque y muero.', 'alta', 'abierto'),
('Flavia', 'ayuda', 'No puedo reclamar mi casa', 'Puse el cofre pero el plugin no me deja reclamar la zona.', 'media', 'en_proceso'),
('Alex', 'reporte', 'Posible robo en almacén', 'Faltan diamantes del cofre común. Revisar logs.', 'alta', 'abierto'),
('Nico', 'sugerencia', 'Agregar warp al mercado', 'Sería útil un /warp market cerca del spawn.', 'baja', 'cerrado');

/* =========================================================
   6) CONSULTAS DE PRUEBA
   ========================================================= */

/* 6.1) Ver todos los tickets (los más nuevos primero) */
SELECT * FROM mc_tickets
ORDER BY creado_en DESC;

/* 6.2) Filtrar por estado (ej: abiertos) */
SELECT id, minecraft_user, tipo, titulo, prioridad, estado, creado_en
FROM mc_tickets
WHERE estado = 'abierto'
ORDER BY creado_en DESC;

/* 6.3) Filtrar por tipo (ej: reportes) */
SELECT id, minecraft_user, tipo, titulo, prioridad, estado, creado_en
FROM mc_tickets
WHERE tipo = 'reporte'
ORDER BY creado_en DESC;

/* 6.4) Ordenar por prioridad (alta primero) */
SELECT id, minecraft_user, tipo, titulo, prioridad, estado, creado_en
FROM mc_tickets
ORDER BY FIELD(prioridad, 'alta','media','baja'), creado_en DESC;

/* 6.5) Buscar por texto (OPCIÓN A: FULLTEXT)
   - Si activaste el FULLTEXT (ft_texto), esto es lo ideal
*/
SELECT id, minecraft_user, tipo, titulo, prioridad, estado, creado_en
FROM mc_tickets
WHERE MATCH(titulo, descripcion) AGAINST ('spawn' IN NATURAL LANGUAGE MODE)
ORDER BY creado_en DESC;

/* 6.6) Buscar por texto (OPCIÓN B: LIKE)
   - Si NO tienes FULLTEXT, usa LIKE (más compatible, pero más lento)
*/
SELECT id, minecraft_user, tipo, titulo, prioridad, estado, creado_en
FROM mc_tickets
WHERE titulo LIKE '%spawn%' OR descripcion LIKE '%spawn%'
ORDER BY creado_en DESC;

/* =========================================================
   7) CONSULTAS DE PRUEBA
   - Estas son acciones típicas que tu panel hará con POST
   ========================================================= */

/* 7.1) Cambiar estado de un ticket (ej: id=1 -> en_proceso) */
UPDATE mc_tickets
SET estado = 'en_proceso'
WHERE id = 1;

/* 7.2) Cerrar ticket (ej: id=1 -> cerrado) */
UPDATE mc_tickets
SET estado = 'cerrado'
WHERE id = 1;

/* 7.3) Borrar ticket (ej: id=4) */
DELETE FROM mc_tickets
WHERE id = 4;
