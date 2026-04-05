-- Agregar campo password (hash) a la tabla clientes
ALTER TABLE clientes ADD COLUMN password VARCHAR(255) NULL AFTER telefono;