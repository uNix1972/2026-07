-- Datos de prueba: 25 médicos adicionales para probar la paginación y el
-- buscador de la pantalla Médicos. Los N° de colegiatura empiezan en
-- MED-00200 para no chocar con los que ya tienes (MED-00123 a MED-00127).
--
-- Se usan los IDs de especialidad directamente (en vez de buscarlos por
-- nombre) porque el intento anterior con subconsultas por nombre falló:
-- el cliente de mysql en tu terminal no está leyendo/escribiendo en
-- utf8mb4, así que los acentos de "Pediatría"/"Ginecología" no coincidían
-- con lo que hay guardado. Estos IDs son los que confirmaste tú mismo:
--   1 = Medicina General
--   2 = Pediatría
--   3 = Ginecología
--
-- Cómo aplicarlo (PowerShell, con Docker corriendo):
--   Get-Content seed_medicos_test.sql -Raw | docker exec -i smartclinic_db mysql -uroot smartclinic_db

INSERT INTO medico (especialidad_id, nombres, apellidos, num_colegiatura, telefono)
VALUES
    (1, 'Carlos Andres', 'Mendoza', 'MED-00200', '+502 4777-6001'),
    (2, 'Laura Patricia', 'Flores', 'MED-00201', '+502 4777-6002'),
    (3, 'Jorge Luis', 'Castillo', 'MED-00202', '+502 4777-6003'),
    (1, 'Ana Cecilia', 'Reyes', 'MED-00203', '+502 4777-6004'),
    (2, 'Roberto Ivan', 'Salinas', 'MED-00204', '+502 4777-6005'),
    (3, 'Diana Marcela', 'Guzman', 'MED-00205', '+502 4777-6006'),
    (1, 'Fernando Jose', 'Aguilar', 'MED-00206', '+502 4777-6007'),
    (2, 'Patricia Elena', 'Carcamo', 'MED-00207', '+502 4777-6008'),
    (3, 'Manuel Alejandro', 'Torres', 'MED-00208', '+502 4777-6009'),
    (1, 'Gabriela Sofia', 'Nunez', 'MED-00209', '+502 4777-6010'),
    (2, 'Luis Fernando', 'Zelaya', 'MED-00210', '+502 4777-6011'),
    (3, 'Karla Vanessa', 'Rodriguez', 'MED-00211', '+502 4777-6012'),
    (1, 'Oscar Danilo', 'Martinez', 'MED-00212', '+502 4777-6013'),
    (2, 'Silvia Carolina', 'Lopez', 'MED-00213', '+502 4777-6014'),
    (3, 'Edgar Alberto', 'Cruz', 'MED-00214', '+502 4777-6015'),
    (1, 'Melissa Julissa', 'Paz', 'MED-00215', '+502 4777-6016'),
    (2, 'Ricardo Antonio', 'Ferrera', 'MED-00216', '+502 4777-6017'),
    (3, 'Claudia Ivette', 'Bonilla', 'MED-00217', '+502 4777-6018'),
    (1, 'Sergio Enrique', 'Maldonado', 'MED-00218', '+502 4777-6019'),
    (2, 'Rosa Isela', 'Chavez', 'MED-00219', '+502 4777-6020'),
    (3, 'Alexander Josue', 'Vindel', 'MED-00220', '+502 4777-6021'),
    (1, 'Yesenia Marisol', 'Ordonez', 'MED-00221', '+502 4777-6022'),
    (2, 'Ivan Alexander', 'Pineda', 'MED-00222', '+502 4777-6023'),
    (3, 'Wendy Carolina', 'Sabillon', 'MED-00223', '+502 4777-6024'),
    (1, 'Douglas Estuardo', 'Amaya', 'MED-00224', '+502 4777-6025');
