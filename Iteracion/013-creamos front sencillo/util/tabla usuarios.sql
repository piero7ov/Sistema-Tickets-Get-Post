-- Crear tabla de usuarios (muy simple)
CREATE TABLE IF NOT EXISTS mc_users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username VARCHAR(32) NOT NULL,
  pass_sha256 CHAR(64) NOT NULL,
  role ENUM('admin','staff') NOT NULL DEFAULT 'admin',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert admin (usuario: piero7ov | pass: piero7ov)
INSERT INTO mc_users (username, pass_sha256, role)
VALUES ('piero7ov', SHA2('piero7ov', 256), 'admin');
