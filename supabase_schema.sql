-- Esquema para Supabase (PostgreSQL)
-- ReservaPro - Sistema de Gestión de Citas

-- Tipos ENUM personalizados
CREATE TYPE tipo_bloqueo AS ENUM ('vacaciones', 'dia_libre', 'mantenimiento', 'otro');
CREATE TYPE estado_cita AS ENUM ('pendiente', 'confirmada', 'completada', 'cancelada', 'no_asistio');
CREATE TYPE rol_usuario AS ENUM ('admin', 'empleado', 'recepcion');
CREATE TYPE tipo_recordatorio AS ENUM ('email', 'sms', 'whatsapp');

-- Tabla de empresas
CREATE TABLE empresas (
    id SERIAL PRIMARY KEY,
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

-- Tabla de configuración
CREATE TABLE configuracion (
    id SERIAL PRIMARY KEY,
    empresa_id INTEGER NOT NULL REFERENCES empresas(id) ON DELETE CASCADE,
    horario_general TEXT,
    minutos_antelacion_reserva INTEGER DEFAULT 60,
    minutos_antelacion_cancelacion INTEGER DEFAULT 120,
    hora_cierre_reservas_mismo_dia TIME DEFAULT '14:00:00',
    politica_cancelacion TEXT,
    mensaje_bienvenida TEXT
);

-- Tabla de empleados
CREATE TABLE empleados (
    id SERIAL PRIMARY KEY,
    empresa_id INTEGER NOT NULL REFERENCES empresas(id) ON DELETE CASCADE,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE,
    telefono VARCHAR(20),
    foto VARCHAR(255),
    especialidad TEXT,
    descripcion TEXT,
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de servicios
CREATE TABLE servicios (
    id SERIAL PRIMARY KEY,
    empresa_id INTEGER NOT NULL REFERENCES empresas(id) ON DELETE CASCADE,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT,
    duracion_minutos INTEGER NOT NULL,
    precio NUMERIC(10,2) NOT NULL,
    imagen VARCHAR(255),
    icono VARCHAR(50),
    color VARCHAR(7) DEFAULT '#6366f1',
    activo BOOLEAN DEFAULT TRUE,
    requiere_stock BOOLEAN DEFAULT FALSE,
    orden INTEGER DEFAULT 0,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Relación servicios-empleados
CREATE TABLE empleado_servicio (
    id SERIAL PRIMARY KEY,
    empleado_id INTEGER NOT NULL REFERENCES empleados(id) ON DELETE CASCADE,
    servicio_id INTEGER NOT NULL REFERENCES servicios(id) ON DELETE CASCADE,
    UNIQUE (empleado_id, servicio_id)
);

-- Horarios de empleados
CREATE TABLE horarios_empleados (
    id SERIAL PRIMARY KEY,
    empleado_id INTEGER NOT NULL REFERENCES empleados(id) ON DELETE CASCADE,
    dia_semana SMALLINT NOT NULL, -- 0=Domingo, 1=Lunes, ..., 6=Sábado
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    activo BOOLEAN DEFAULT TRUE
);

-- Bloqueos de horario
CREATE TABLE bloqueos_horario (
    id SERIAL PRIMARY KEY,
    empleado_id INTEGER NULL REFERENCES empleados(id) ON DELETE CASCADE,
    fecha_inicio TIMESTAMP NOT NULL,
    fecha_fin TIMESTAMP NOT NULL,
    motivo VARCHAR(255),
    tipo tipo_bloqueo DEFAULT 'otro',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de clientes
CREATE TABLE clientes (
    id SERIAL PRIMARY KEY,
    empresa_id INTEGER NOT NULL REFERENCES empresas(id) ON DELETE CASCADE,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100),
    email VARCHAR(100),
    telefono VARCHAR(20) NOT NULL,
    notas_internas TEXT,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultima_visita TIMESTAMP NULL,
    total_citas INTEGER DEFAULT 0,
    total_no_shows INTEGER DEFAULT 0
);
CREATE INDEX idx_telefono ON clientes(telefono);
CREATE INDEX idx_email ON clientes(email);

-- Tabla de citas
CREATE TABLE citas (
    id SERIAL PRIMARY KEY,
    empresa_id INTEGER NOT NULL REFERENCES empresas(id) ON DELETE CASCADE,
    cliente_id INTEGER NOT NULL REFERENCES clientes(id) ON DELETE CASCADE,
    servicio_id INTEGER NOT NULL REFERENCES servicios(id),
    empleado_id INTEGER NULL REFERENCES empleados(id) ON DELETE SET NULL,
    fecha_hora TIMESTAMP NOT NULL,
    duracion_minutos INTEGER NOT NULL,
    precio NUMERIC(10,2) NOT NULL,
    estado estado_cita DEFAULT 'pendiente',
    notas_cliente TEXT,
    notas_internas TEXT,
    codigo_confirmacion VARCHAR(20) UNIQUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    cancelada_por VARCHAR(50),
    motivo_cancelacion TEXT
);
CREATE INDEX idx_fecha_hora ON citas(fecha_hora);
CREATE INDEX idx_estado ON citas(estado);
CREATE INDEX idx_empleado_fecha ON citas(empleado_id, fecha_hora);

-- Tabla de usuarios
CREATE TABLE usuarios (
    id SERIAL PRIMARY KEY,
    empresa_id INTEGER NOT NULL REFERENCES empresas(id) ON DELETE CASCADE,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    rol rol_usuario DEFAULT 'recepcion',
    empleado_id INTEGER NULL REFERENCES empleados(id) ON DELETE SET NULL,
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (empresa_id, username)
);

-- Tabla de recordatorios
CREATE TABLE recordatorios (
    id SERIAL PRIMARY KEY,
    cita_id INTEGER NOT NULL REFERENCES citas(id) ON DELETE CASCADE,
    tipo tipo_recordatorio NOT NULL,
    enviado BOOLEAN DEFAULT FALSE,
    fecha_envio TIMESTAMP NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
