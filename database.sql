-- Base de datos para Sistema de Reservas
-- ReservaPro - Sistema de Gestión de Citas

CREATE DATABASE IF NOT EXISTS reservapro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE reservapro;

-- Tabla de empresas (multi-tenant)
CREATE TABLE IF NOT EXISTS empresas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(255) NOT NULL,
    logo VARCHAR(255),
    direccion TEXT,
    telefono VARCHAR(20),
    email VARCHAR(100),
    color_primario VARCHAR(7) DEFAULT '#6366f1',
    color_secundario VARCHAR(7) DEFAULT '#8b5cf6',
    zona_horaria VARCHAR(50) DEFAULT 'America/Mexico_City',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS configuracion (
    id INT PRIMARY KEY AUTO_INCREMENT,
    empresa_id INT NOT NULL,
    horario_general TEXT,
    minutos_antelacion_reserva INT DEFAULT 60,
    minutos_antelacion_cancelacion INT DEFAULT 120,
    hora_cierre_reservas_mismo_dia TIME DEFAULT '14:00:00',
    politica_cancelacion TEXT,
    mensaje_bienvenida TEXT,
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS empleados (
    id INT PRIMARY KEY AUTO_INCREMENT,
    empresa_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE,
    telefono VARCHAR(20),
    foto VARCHAR(255),
    especialidad TEXT,
    descripcion TEXT,
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS servicios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    empresa_id INT NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT,
    duracion_minutos INT NOT NULL,
    precio DECIMAL(10, 2) NOT NULL,
    imagen VARCHAR(255),
    icono VARCHAR(50),
    color VARCHAR(7) DEFAULT '#6366f1',
    activo BOOLEAN DEFAULT TRUE,
    requiere_stock BOOLEAN DEFAULT FALSE,
    orden INT DEFAULT 0,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
);

-- Tabla de relación servicios-empleados (qué empleado puede hacer qué servicio)
CREATE TABLE IF NOT EXISTS empleado_servicio (
    id INT PRIMARY KEY AUTO_INCREMENT,
    empleado_id INT NOT NULL,
    servicio_id INT NOT NULL,
    FOREIGN KEY (empleado_id) REFERENCES empleados(id) ON DELETE CASCADE,
    FOREIGN KEY (servicio_id) REFERENCES servicios(id) ON DELETE CASCADE,
    UNIQUE KEY (empleado_id, servicio_id)
);

-- Tabla de horarios de empleados
CREATE TABLE IF NOT EXISTS horarios_empleados (
    id INT PRIMARY KEY AUTO_INCREMENT,
    empleado_id INT NOT NULL,
    dia_semana TINYINT NOT NULL, -- 0=Domingo, 1=Lunes, ..., 6=Sábado
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    activo BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (empleado_id) REFERENCES empleados(id) ON DELETE CASCADE
);

-- Tabla de bloqueos de horario (vacaciones, días libres, etc.)
CREATE TABLE IF NOT EXISTS bloqueos_horario (
    id INT PRIMARY KEY AUTO_INCREMENT,
    empleado_id INT NULL, -- NULL = bloqueo general para todos
    fecha_inicio DATETIME NOT NULL,
    fecha_fin DATETIME NOT NULL,
    motivo VARCHAR(255),
    tipo ENUM('vacaciones', 'dia_libre', 'mantenimiento', 'otro') DEFAULT 'otro',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (empleado_id) REFERENCES empleados(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS clientes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    empresa_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100),
    email VARCHAR(100),
    telefono VARCHAR(20) NOT NULL,
    notas_internas TEXT,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultima_visita DATETIME NULL,
    total_citas INT DEFAULT 0,
    total_no_shows INT DEFAULT 0,
    INDEX idx_telefono (telefono),
    INDEX idx_email (email),
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS citas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    empresa_id INT NOT NULL,
    cliente_id INT NOT NULL,
    servicio_id INT NOT NULL,
    empleado_id INT NULL, -- NULL = cualquier empleado disponible
    fecha_hora DATETIME NOT NULL,
    duracion_minutos INT NOT NULL,
    precio DECIMAL(10, 2) NOT NULL,
    estado ENUM('pendiente', 'confirmada', 'completada', 'cancelada', 'no_asistio') DEFAULT 'pendiente',
    notas_cliente TEXT,
    notas_internas TEXT,
    codigo_confirmacion VARCHAR(20) UNIQUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    cancelada_por VARCHAR(50), -- 'cliente', 'admin', 'sistema'
    motivo_cancelacion TEXT,
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
    FOREIGN KEY (servicio_id) REFERENCES servicios(id),
    FOREIGN KEY (empleado_id) REFERENCES empleados(id) ON DELETE SET NULL,
    INDEX idx_fecha_hora (fecha_hora),
    INDEX idx_estado (estado),
    INDEX idx_empleado_fecha (empleado_id, fecha_hora)
);

CREATE TABLE IF NOT EXISTS usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    empresa_id INT NOT NULL,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    rol ENUM('admin', 'empleado', 'recepcion') DEFAULT 'recepcion',
    empleado_id INT NULL,
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
    FOREIGN KEY (empleado_id) REFERENCES empleados(id) ON DELETE SET NULL,
    UNIQUE KEY (empresa_id, username)
);

-- Tabla de recordatorios enviados
CREATE TABLE IF NOT EXISTS recordatorios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cita_id INT NOT NULL,
    tipo ENUM('email', 'sms', 'whatsapp') NOT NULL,
    enviado BOOLEAN DEFAULT FALSE,
    fecha_envio DATETIME NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cita_id) REFERENCES citas(id) ON DELETE CASCADE
);


-- Insertar empresa de ejemplo
INSERT INTO empresas (nombre, logo, direccion, telefono, email) VALUES
('Mi Negocio', NULL, 'Calle Principal #123, Colonia Centro', '+52 123 456 7890', 'contacto@minegocio.com');

-- Insertar configuración inicial para la empresa 1
INSERT INTO configuracion (
    empresa_id,
    horario_general,
    minutos_antelacion_reserva,
    minutos_antelacion_cancelacion,
    hora_cierre_reservas_mismo_dia,
    politica_cancelacion,
    mensaje_bienvenida
) VALUES (
    1,
    'Lunes a Viernes: 9:00 AM - 7:00 PM\nSábados: 9:00 AM - 3:00 PM',
    60,
    120,
    '14:00:00',
    'Las cancelaciones deben realizarse con al menos 2 horas de anticipación.',
    '¡Bienvenido! Reserve su cita en menos de 60 segundos.'
);

INSERT INTO empleados (empresa_id, nombre, apellido, email, telefono, especialidad, descripcion) VALUES
(1, 'Juan', 'Pérez', 'juan@minegocio.com', '123-456-7890', 'Cortes y Tintes', 'Experto en colorimetría con 10 años de experiencia'),
(1, 'María', 'González', 'maria@minegocio.com', '123-456-7891', 'Manicure y Pedicure', 'Especialista en uñas acrílicas y diseños personalizados'),
(1, 'Carlos', 'Rodríguez', 'carlos@minegocio.com', '123-456-7892', 'Barbería y Afeitado', 'Maestro barbero tradicional');

INSERT INTO servicios (empresa_id, nombre, descripcion, duracion_minutos, precio, icono, orden) VALUES
(1, 'Corte de Cabello', 'Corte profesional con lavado incluido', 45, 250.00, '✂️', 1),
(1, 'Tinte Completo', 'Tinte de cabello con productos de alta calidad', 120, 800.00, '🎨', 2),
(1, 'Manicure', 'Arreglo completo de uñas de manos', 30, 150.00, '💅', 3),
(1, 'Pedicure', 'Arreglo completo de uñas de pies', 45, 200.00, '👣', 4),
(1, 'Barba y Bigote', 'Recorte y perfilado de barba', 30, 150.00, '🧔', 5),
(1, 'Masaje Relajante', 'Masaje terapéutico de cuerpo completo', 60, 500.00, '💆', 6);

-- Asignar servicios a empleados
INSERT INTO empleado_servicio (empleado_id, servicio_id) VALUES
(1, 1), (1, 2), -- Juan: Corte y Tinte
(2, 3), (2, 4), -- María: Manicure y Pedicure
(3, 1), (3, 5); -- Carlos: Corte y Barba

-- Horarios de empleados (Lunes a Viernes 9:00-18:00, Sábado 9:00-15:00)
INSERT INTO horarios_empleados (empleado_id, dia_semana, hora_inicio, hora_fin) VALUES
-- Juan
(1, 1, '09:00:00', '18:00:00'), -- Lunes
(1, 2, '09:00:00', '18:00:00'), -- Martes
(1, 3, '09:00:00', '18:00:00'), -- Miércoles
(1, 4, '09:00:00', '18:00:00'), -- Jueves
(1, 5, '09:00:00', '18:00:00'), -- Viernes
(1, 6, '09:00:00', '15:00:00'), -- Sábado
-- María
(2, 1, '09:00:00', '18:00:00'),
(2, 2, '09:00:00', '18:00:00'),
(2, 3, '09:00:00', '18:00:00'),
(2, 4, '09:00:00', '18:00:00'),
(2, 5, '09:00:00', '18:00:00'),
(2, 6, '09:00:00', '15:00:00'),
-- Carlos
(3, 1, '09:00:00', '18:00:00'),
(3, 2, '09:00:00', '18:00:00'),
(3, 3, '09:00:00', '18:00:00'),
(3, 4, '09:00:00', '18:00:00'),
(3, 5, '09:00:00', '18:00:00'),
(3, 6, '09:00:00', '15:00:00');

-- Crear usuario admin por defecto (password: admin123, hash generado con password_hash)
INSERT INTO usuarios (empresa_id, username, password, nombre, rol) VALUES
(1, 'admin', '$2y$10$u1QwQwQwQwQwQwQwQwQwQeQwQwQwQwQwQwQwQwQwQwQwQwQwQwQwQwQwQwQw', 'Administrador', 'admin');

-- Vistas útiles para reportes
CREATE OR REPLACE VIEW vista_citas_completa AS
SELECT 
    c.id,
    c.fecha_hora,
    c.duracion_minutos,
    c.precio,
    c.estado,
    c.notas_cliente,
    c.codigo_confirmacion,
    CONCAT(cl.nombre, ' ', IFNULL(cl.apellido, '')) as cliente_nombre,
    cl.telefono as cliente_telefono,
    cl.email as cliente_email,
    s.nombre as servicio_nombre,
    CONCAT(e.nombre, ' ', e.apellido) as empleado_nombre,
    e.id as empleado_id
FROM citas c
INNER JOIN clientes cl ON c.cliente_id = cl.id
INNER JOIN servicios s ON c.servicio_id = s.id
LEFT JOIN empleados e ON c.empleado_id = e.id;
