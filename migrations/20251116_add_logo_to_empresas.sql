-- Agregar campo logo a la tabla empresas
ALTER TABLE empresas ADD COLUMN logo VARCHAR(255) NULL AFTER moneda;