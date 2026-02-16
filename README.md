# Sistema-Tickets-Get-Post

Sistema simple de **tickets** en **PHP + MySQL** pensado para practicar **GET y POST**:

- **Front público**: creación de tickets (POST → INSERT).
- **Back / Panel admin**: login + listado con filtros (GET) + acciones (POST → UPDATE/DELETE) + vistas (GET).

---

## ✅ Tecnologías

- PHP (mysqli)
- MySQL / MariaDB
- HTML + CSS

---

## 📁 Estructura del proyecto

```

/
├── index.php
├── index.css
├── img/
│   └── fondo_mc.png
├── back/
│   ├── login.php
│   ├── logout.php
│   ├── panel.php
│   ├── css/
│   │   ├── login.css
│   │   └── panel.css
│   ├── img/
│   │   └── fondo_mc.png
│   ├── inc/
│   │   ├── header.php
│   │   ├── footer.php
│   │   └── panel_logic.php
│   └── views/
│       ├── view_list.php
│       ├── view_new.php
│       ├── view_ver.php
│       └── view_edit.php
└── util/
└── tabla usuarios.sql

````

---

## 🚀 Puesta en marcha (local)

### 1) Coloca el proyecto en tu servidor local
Ejemplo con XAMPP:
- Copia la carpeta del proyecto dentro de `htdocs/`
- Inicia **Apache** y **MySQL**

### 2) Crea la base de datos
Crea una BD llamada:

- `mc_server_tickets`

### 3) Crea tablas

#### 3.1) Tabla de tickets (mc_tickets)
> En el proyecto se usa esta tabla con estos campos: `id, minecraft_user, tipo, titulo, descripcion, prioridad, estado, creado_en, actualizado_en`.
> Además, el panel intenta usar FULLTEXT y, si no está disponible, hace fallback a LIKE.

SQL sugerido:

```sql
CREATE TABLE IF NOT EXISTS mc_tickets (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  minecraft_user VARCHAR(32) NOT NULL,
  tipo ENUM('ayuda','bug','reporte','sugerencia') NOT NULL DEFAULT 'ayuda',
  titulo VARCHAR(120) NOT NULL,
  descripcion TEXT NOT NULL,
  prioridad ENUM('baja','media','alta') NOT NULL DEFAULT 'media',
  estado ENUM('abierto','en_proceso','cerrado') NOT NULL DEFAULT 'abierto',
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_estado (estado),
  KEY idx_tipo (tipo),
  KEY idx_prio (prioridad),
  KEY idx_creado (creado_en),
  FULLTEXT KEY ft_titulo_desc (titulo, descripcion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
````

#### 3.2) Tabla de usuarios (mc_users)

Ejecuta el archivo:

* `util/tabla usuarios.sql`

Incluye un usuario admin de ejemplo:

* usuario: `piero7ov`
* contraseña: `piero7ov`
  (guardada como SHA-256 en BD)

---

## 🔧 Configuración de conexión a BD

Las credenciales están **hardcodeadas** en estos archivos:

* `index.php` (variables `$dbHost, $dbUser, $dbPass, $dbName`)
* `back/login.php` (new mysqli con host/user/pass/db)
* `back/inc/panel_logic.php` (new mysqli con host/user/pass/db)

Por defecto se usa:

* host: `localhost`
* user: `mcadmin`
* pass: `mcadmin123`
* db: `mc_server_tickets`

Si tu entorno usa otras credenciales, cámbialas en esos 3 archivos.

---

## 🧭 Cómo se usa

### Front (público)

* **Crear ticket:** `index.php`

  * Envía el formulario por **POST**
  * Inserta el ticket y muestra el **ID** generado
  * Botón arriba a la derecha para ir al admin

### Back (admin)

* **Login:** `back/login.php`
* **Panel:** `back/panel.php`
* **Logout:** `back/logout.php`

---

## 🔁 Flujo GET / POST (lo importante del proyecto)

### GET (navegación + filtros)

El panel usa `GET` para:

* cambiar vistas: `?vista=list|new|ver|edit`
* filtrar listado: `q`, `estado`, `tipo`, `order`

Ejemplos:

* `back/panel.php?vista=list`
* `back/panel.php?vista=list&q=spawn&estado=abierto&tipo=bug&order=prio`
* `back/panel.php?vista=ver&id=12`
* `back/panel.php?vista=edit&id=12`

### POST (acciones sobre datos)

El panel usa `POST` para:

* crear ticket (`accion=crear`)
* editar ticket (`accion=editar`)
* cambiar estado (`accion=estado`)
* borrar ticket (`accion=borrar`)

Estas acciones las procesa:

* `back/inc/panel_logic.php`

---

## 👨‍💻 Desarrollado por

**Piero Olivares · PieroDev**



