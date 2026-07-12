-- SQLite schema for ReservaPro
PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS empresas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre TEXT NOT NULL,
    logo TEXT,
    direccion TEXT,
    telefono TEXT,
    email TEXT,
    moneda TEXT DEFAULT '$',
    color_primario TEXT DEFAULT '#6366f1',
    color_secundario TEXT DEFAULT '#8b5cf6',
    zona_horaria TEXT DEFAULT 'America/Mexico_City',
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS configuracion (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    empresa_id INTEGER NOT NULL,
    horario_general TEXT,
    minutos_antelacion_reserva INTEGER DEFAULT 60,
    minutos_antelacion_cancelacion INTEGER DEFAULT 120,
    hora_cierre_reservas_mismo_dia TEXT DEFAULT '14:00:00',
    politica_cancelacion TEXT,
    mensaje_bienvenida TEXT,
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS empleados (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    empresa_id INTEGER NOT NULL,
    nombre TEXT NOT NULL,
    apellido TEXT NOT NULL,
    email TEXT UNIQUE,
    telefono TEXT,
    foto TEXT,
    especialidad TEXT,
    descripcion TEXT,
    activo INTEGER DEFAULT 1,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS servicios (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    empresa_id INTEGER NOT NULL,
    nombre TEXT NOT NULL,
    descripcion TEXT,
    duracion_minutos INTEGER NOT NULL,
    precio REAL NOT NULL,
    imagen TEXT,
    icono TEXT,
    color TEXT DEFAULT '#6366f1',
    activo INTEGER DEFAULT 1,
    requiere_stock INTEGER DEFAULT 0,
    orden INTEGER DEFAULT 0,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS empleado_servicio (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    empleado_id INTEGER NOT NULL,
    servicio_id INTEGER NOT NULL,
    FOREIGN KEY (empleado_id) REFERENCES empleados(id) ON DELETE CASCADE,
    FOREIGN KEY (servicio_id) REFERENCES servicios(id) ON DELETE CASCADE,
    UNIQUE (empleado_id, servicio_id)
);

CREATE TABLE IF NOT EXISTS horarios_empleados (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    empleado_id INTEGER NOT NULL,
    dia_semana INTEGER NOT NULL,
    hora_inicio TEXT NOT NULL,
    hora_fin TEXT NOT NULL,
    activo INTEGER DEFAULT 1,
    FOREIGN KEY (empleado_id) REFERENCES empleados(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS bloqueos_horario (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    empleado_id INTEGER,
    fecha_inicio DATETIME NOT NULL,
    fecha_fin DATETIME NOT NULL,
    motivo TEXT,
    tipo TEXT DEFAULT 'otro',
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (empleado_id) REFERENCES empleados(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS clientes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    empresa_id INTEGER NOT NULL,
    nombre TEXT NOT NULL,
    apellido TEXT,
    email TEXT,
    telefono TEXT NOT NULL,
    password TEXT,
    notas_internas TEXT,
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    ultima_visita DATETIME,
    total_citas INTEGER DEFAULT 0,
    total_no_shows INTEGER DEFAULT 0,
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_clientes_telefono ON clientes(telefono);
CREATE INDEX IF NOT EXISTS idx_clientes_email ON clientes(email);

CREATE TABLE IF NOT EXISTS citas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    empresa_id INTEGER NOT NULL,
    cliente_id INTEGER NOT NULL,
    servicio_id INTEGER NOT NULL,
    empleado_id INTEGER,
    fecha_hora DATETIME NOT NULL,
    duracion_minutos INTEGER NOT NULL,
    precio REAL NOT NULL,
    estado TEXT DEFAULT 'pendiente',
    notas_cliente TEXT,
    notas_internas TEXT,
    codigo_confirmacion TEXT UNIQUE,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    cancelada_por TEXT,
    motivo_cancelacion TEXT,
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
    FOREIGN KEY (servicio_id) REFERENCES servicios(id),
    FOREIGN KEY (empleado_id) REFERENCES empleados(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_citas_fecha_hora ON citas(fecha_hora);
CREATE INDEX IF NOT EXISTS idx_citas_estado ON citas(estado);
CREATE INDEX IF NOT EXISTS idx_citas_empleado_fecha ON citas(empleado_id, fecha_hora);

CREATE TABLE IF NOT EXISTS usuarios (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    empresa_id INTEGER NOT NULL,
    username TEXT NOT NULL,
    password TEXT NOT NULL,
    nombre TEXT NOT NULL,
    email TEXT,
    rol TEXT DEFAULT 'recepcion',
    empleado_id INTEGER,
    activo INTEGER DEFAULT 1,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
    FOREIGN KEY (empleado_id) REFERENCES empleados(id) ON DELETE SET NULL,
    UNIQUE (empresa_id, username)
);

CREATE TABLE IF NOT EXISTS recordatorios (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    cita_id INTEGER NOT NULL,
    tipo TEXT NOT NULL,
    enviado INTEGER DEFAULT 0,
    fecha_envio DATETIME,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cita_id) REFERENCES citas(id) ON DELETE CASCADE
);

BEGIN TRANSACTION;

INSERT INTO empresas (nombre, logo, direccion, telefono, email, moneda) VALUES
('Mi Negocio', NULL, 'Calle Principal #123, Colonia Centro', '+52 123 456 7890', 'contacto@minegocio.com', '$');

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

INSERT INTO empleado_servicio (empleado_id, servicio_id) VALUES
(1, 1), (1, 2),
(2, 3), (2, 4),
(3, 1), (3, 5);

INSERT INTO horarios_empleados (empleado_id, dia_semana, hora_inicio, hora_fin) VALUES
(1, 1, '09:00:00', '18:00:00'),
(1, 2, '09:00:00', '18:00:00'),
(1, 3, '09:00:00', '18:00:00'),
(1, 4, '09:00:00', '18:00:00'),
(1, 5, '09:00:00', '18:00:00'),
(1, 6, '09:00:00', '15:00:00'),
(2, 1, '09:00:00', '18:00:00'),
(2, 2, '09:00:00', '18:00:00'),
(2, 3, '09:00:00', '18:00:00'),
(2, 4, '09:00:00', '18:00:00'),
(2, 5, '09:00:00', '18:00:00'),
(2, 6, '09:00:00', '15:00:00'),
(3, 1, '09:00:00', '18:00:00'),
(3, 2, '09:00:00', '18:00:00'),
(3, 3, '09:00:00', '18:00:00'),
(3, 4, '09:00:00', '18:00:00'),
(3, 5, '09:00:00', '18:00:00'),
(3, 6, '09:00:00', '15:00:00');

INSERT INTO usuarios (empresa_id, username, password, nombre, rol) VALUES
(1, 'admin', '$2y$10$u1QwQwQwQwQwQwQwQwQeQwQwQwQwQwQwQwQwQwQwQwQwQwQwQwQwQwQwQw', 'Administrador', 'admin');

CREATE VIEW IF NOT EXISTS vista_citas_completa AS
SELECT 
    c.id,
    c.fecha_hora,
    c.duracion_minutos,
    c.precio,
    c.estado,
    c.notas_cliente,
    c.codigo_confirmacion,
    cl.nombre || ' ' || IFNULL(cl.apellido, '') as cliente_nombre,
    cl.telefono as cliente_telefono,
    cl.email as cliente_email,
    s.nombre as servicio_nombre,
    e.nombre || ' ' || e.apellido as empleado_nombre,
    e.id as empleado_id
FROM citas c
INNER JOIN clientes cl ON c.cliente_id = cl.id
INNER JOIN servicios s ON c.servicio_id = s.id
LEFT JOIN empleados e ON c.empleado_id = e.id;

COMMIT;
