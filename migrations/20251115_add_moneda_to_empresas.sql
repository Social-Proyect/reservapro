-- Agregar campo moneda a la tabla empresas
ALTER TABLE empresas ADD COLUMN moneda VARCHAR(10) NOT NULL DEFAULT '$' AFTER email;