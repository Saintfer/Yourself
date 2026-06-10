CREATE DATABASE IF NOT EXISTS yourself_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE yourself_db;
CREATE TABLE IF NOT EXISTS usuarios (
    id                   INT AUTO_INCREMENT PRIMARY KEY,
    nombre               VARCHAR(100)  NOT NULL,
    email                VARCHAR(150)  NOT NULL UNIQUE,
    telefono             VARCHAR(20)   DEFAULT NULL,
    password_hash        VARCHAR(255)  NOT NULL,           
    avatar_letra         CHAR(1)       DEFAULT 'U',        
    dias_activos         INT           DEFAULT 0,
    total_conversaciones INT           DEFAULT 0,
    fecha_registro       TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    ultima_sesion        TIMESTAMP     NULL DEFAULT NULL,
    ultimo_acceso        TIMESTAMP     NULL DEFAULT NULL,  
    activo               TINYINT(1)    DEFAULT 1,          
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS diario (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id  INT          NOT NULL,
    mood        ENUM('genial','bien','normal','triste','muymal') NOT NULL DEFAULT 'normal',
    texto       TEXT         DEFAULT NULL,              
    fecha       DATE         NOT NULL,
    hora        TIME         DEFAULT NULL,
    fecha_hora  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY uq_diario_usuario_fecha (usuario_id, fecha),  
    INDEX idx_diario_usuario (usuario_id),
    INDEX idx_diario_fecha (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS conversaciones (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id  INT             NOT NULL,
    sesion_id   VARCHAR(32)     NOT NULL DEFAULT '',   
    rol         ENUM('user','assistant') NOT NULL,
    mensaje     TEXT            NOT NULL,
    fecha_hora  TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_conv_usuario (usuario_id),
    INDEX idx_conv_sesion  (sesion_id),
    INDEX idx_conv_fecha   (fecha_hora)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS emociones (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id  INT          NOT NULL,
    valor       TINYINT      NOT NULL CHECK (valor BETWEEN 1 AND 5),
    mood_label  VARCHAR(20)  DEFAULT NULL,
    fecha       DATE         NOT NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY uq_emocion_usuario_fecha (usuario_id, fecha),
    INDEX idx_emociones_usuario (usuario_id),
    INDEX idx_emociones_fecha (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- ───────────────────────────────────────────────
-- Usuarios de demostración (contraseña: demo1234)
--   natalia@example.com  /  demo1234
--   juan@example.com     /  demo1234
-- ───────────────────────────────────────────────
INSERT IGNORE INTO usuarios (nombre, email, telefono, password_hash, avatar_letra, dias_activos, total_conversaciones) VALUES
('Natalia Bolaños', 'natalia@example.com', '3001234567',
 '$2y$12$viSR6HYp.6dvWVdkS05E5ecudsh9IwRumPI3Qi.2H2VSKl2FINcF.',
 'N', 7, 3),
('Juan Fernando', 'juan@example.com', '3009876543',
 '$2y$12$54AtqSHnUSjAOuySuYVbbuHTq8u.VJuGiZNSH7Novp/8qyD8pRLn.',
 'J', 5, 2);
INSERT IGNORE INTO diario (usuario_id, mood, texto, fecha, hora) VALUES
(1, 'bien',   'Hoy fue un buen día, terminé mis tareas y hablé con mis amigos.', CURDATE() - INTERVAL 6 DAY, '20:00:00'),
(1, 'normal', 'Un día tranquilo, sin muchas novedades.', CURDATE() - INTERVAL 5 DAY, '21:00:00'),
(1, 'triste', 'Me fue mal en el parcial de matemáticas. Me siento frustrada.', CURDATE() - INTERVAL 4 DAY, '19:30:00'),
(1, 'bien',   'Hablé con Nix y me ayudó a ver las cosas diferente.', CURDATE() - INTERVAL 3 DAY, '20:15:00'),
(1, 'genial', 'Hoy saqué buena nota! Me siento orgullosa.', CURDATE() - INTERVAL 2 DAY, '18:45:00'),
(1, 'bien',   'Fin de semana tranquilo con la familia.', CURDATE() - INTERVAL 1 DAY, '22:00:00'),
(1, 'bien',   'Empezando la semana con energía.', CURDATE(), '07:30:00');
INSERT IGNORE INTO emociones (usuario_id, valor, mood_label, fecha) VALUES
(1, 4, 'bien',   CURDATE() - INTERVAL 6 DAY),
(1, 3, 'normal', CURDATE() - INTERVAL 5 DAY),
(1, 2, 'triste', CURDATE() - INTERVAL 4 DAY),
(1, 4, 'bien',   CURDATE() - INTERVAL 3 DAY),
(1, 5, 'genial', CURDATE() - INTERVAL 2 DAY),
(1, 4, 'bien',   CURDATE() - INTERVAL 1 DAY),
(1, 4, 'bien',   CURDATE());
CREATE OR REPLACE VIEW v_resumen_semanal AS
SELECT
    u.id             AS usuario_id,
    u.nombre,
    COUNT(d.id)      AS checkins_semana,
    ROUND(AVG(e.valor), 2) AS promedio_emocional,
    MAX(d.fecha)     AS ultimo_checkin
FROM usuarios u
LEFT JOIN diario d ON u.id = d.usuario_id
    AND d.fecha >= CURDATE() - INTERVAL 7 DAY
LEFT JOIN emociones e ON u.id = e.usuario_id
    AND e.fecha >= CURDATE() - INTERVAL 7 DAY
GROUP BY u.id, u.nombre;
CREATE OR REPLACE VIEW v_emociones_semana AS
SELECT
    e.usuario_id,
    e.fecha,
    e.valor,
    e.mood_label,
    DATE_FORMAT(e.fecha, '%a') AS dia_abrev
FROM emociones e
WHERE e.fecha >= CURDATE() - INTERVAL 6 DAY
ORDER BY e.usuario_id, e.fecha ASC;
SELECT
    TABLE_NAME          AS 'Tabla',
    TABLE_ROWS          AS 'Filas',
    TABLE_COMMENT       AS 'Descripción'
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'yourself_db'
ORDER BY TABLE_NAME;
ALTER TABLE usuarios
  ADD COLUMN IF NOT EXISTS ultimo_acceso TIMESTAMP NULL DEFAULT NULL
    AFTER ultima_sesion;
ALTER TABLE conversaciones
  ADD COLUMN IF NOT EXISTS sesion_id VARCHAR(32) NOT NULL DEFAULT ''
    AFTER usuario_id;
ALTER TABLE conversaciones
  ADD INDEX IF NOT EXISTS idx_conv_sesion (sesion_id);
