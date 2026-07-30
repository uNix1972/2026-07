-- Corrige textos de "funciones" (nombres/descripciones de permisos) que
-- quedaron guardados con doble codificación UTF-8 (mojibake). Viene del
-- push de Juanra "mejora de accesos por roles". Es idempotente: el WHERE
-- solo toca filas que todavia tienen el patron de bytes corrupto, asi que
-- se puede correr mas de una vez sin problema.

UPDATE funciones
SET funcionNombre = CONVERT(
    BINARY(CONVERT(funcionNombre USING latin1))
    USING utf8mb4
)
WHERE
    HEX(funcionNombre) LIKE '%C383C2%'
    OR HEX(funcionNombre) LIKE '%C382C2%';

UPDATE funciones
SET funcionDescripcion = CONVERT(
    BINARY(CONVERT(funcionDescripcion USING latin1))
    USING utf8mb4
)
WHERE
    HEX(funcionDescripcion) LIKE '%C383C2%'
    OR HEX(funcionDescripcion) LIKE '%C382C2%';
