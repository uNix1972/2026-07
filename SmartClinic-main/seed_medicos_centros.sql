-- Asigna los 25 médicos de prueba (MED-00200 a MED-00224) repartidos
-- entre TODOS tus centros de salud activos, en vez de meterlos todos al
-- mismo. No necesita que me digas cuántos centros tienes ni sus nombres:
-- cuenta tus centros activos y les va rotando uno por uno (médico 1 al
-- centro 1, médico 2 al centro 2, ... y si hay más médicos que centros,
-- vuelve a empezar por el centro 1).
--
-- Por qué no choca con nada:
--  - Empieza borrando cualquier asignación previa de estos 25 médicos
--    (por si ya corriste la versión anterior del script, que los metía
--    a todos en un solo centro) para que el script se pueda correr las
--    veces que quieras sin duplicar ni fallar por la llave única.
--  - El consultorio de cada uno es "TEST-200".."TEST-224": un valor por
--    médico, nunca repetido, así que nunca puede chocar con la llave
--    única (centro_salud_id, consultorio) sin importar en qué centro
--    caiga cada quien.
--  - Solo toca medico_centro_salud (no toca médicos, centros ni citas).
--
-- Cómo aplicarlo (PowerShell, con Docker corriendo):
--   Get-Content seed_medicos_centros.sql -Raw | docker exec -i smartclinic_db mysql -uroot smartclinic_db

DELETE mcs FROM medico_centro_salud mcs
JOIN medico m ON m.id = mcs.medico_id
WHERE m.num_colegiatura BETWEEN 'MED-00200' AND 'MED-00224';

INSERT INTO medico_centro_salud (medico_id, centro_salud_id, consultorio, estado)
SELECT m.id,
       (SELECT ranked.id FROM (SELECT id, ROW_NUMBER() OVER (ORDER BY id) AS rn FROM centro_salud WHERE estado = 'ACT') ranked
        WHERE ranked.rn = ((idx.n MOD (SELECT COUNT(*) FROM centro_salud WHERE estado = 'ACT')) + 1)),
       CONCAT('TEST-', 200 + idx.n),
       'ACT'
FROM medico m
JOIN (
    SELECT 0 AS n, 'MED-00200' AS colegiatura UNION ALL
    SELECT 1, 'MED-00201' UNION ALL
    SELECT 2, 'MED-00202' UNION ALL
    SELECT 3, 'MED-00203' UNION ALL
    SELECT 4, 'MED-00204' UNION ALL
    SELECT 5, 'MED-00205' UNION ALL
    SELECT 6, 'MED-00206' UNION ALL
    SELECT 7, 'MED-00207' UNION ALL
    SELECT 8, 'MED-00208' UNION ALL
    SELECT 9, 'MED-00209' UNION ALL
    SELECT 10, 'MED-00210' UNION ALL
    SELECT 11, 'MED-00211' UNION ALL
    SELECT 12, 'MED-00212' UNION ALL
    SELECT 13, 'MED-00213' UNION ALL
    SELECT 14, 'MED-00214' UNION ALL
    SELECT 15, 'MED-00215' UNION ALL
    SELECT 16, 'MED-00216' UNION ALL
    SELECT 17, 'MED-00217' UNION ALL
    SELECT 18, 'MED-00218' UNION ALL
    SELECT 19, 'MED-00219' UNION ALL
    SELECT 20, 'MED-00220' UNION ALL
    SELECT 21, 'MED-00221' UNION ALL
    SELECT 22, 'MED-00222' UNION ALL
    SELECT 23, 'MED-00223' UNION ALL
    SELECT 24, 'MED-00224'
) idx ON idx.colegiatura = m.num_colegiatura;
