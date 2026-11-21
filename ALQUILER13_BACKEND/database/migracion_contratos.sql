-- Ajustes para una base ya creada con la version anterior de database.sql.
-- Agrega la columna de devolucion, el indice por estado y cambia las llaves foraneas a RESTRICT.
USE alquiler_vehiculos_db;

ALTER TABLE reservas ADD COLUMN fecha_devolucion_real DATE NULL AFTER fecha_fin;
ALTER TABLE vehiculos ADD INDEX idx_vehiculos_estado (estado);

ALTER TABLE reservas DROP FOREIGN KEY reservas_ibfk_1;
ALTER TABLE reservas DROP FOREIGN KEY reservas_ibfk_2;
ALTER TABLE reservas ADD CONSTRAINT reservas_ibfk_1 FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE RESTRICT;
ALTER TABLE reservas ADD CONSTRAINT reservas_ibfk_2 FOREIGN KEY (vehiculo_id) REFERENCES vehiculos(id) ON DELETE RESTRICT;
