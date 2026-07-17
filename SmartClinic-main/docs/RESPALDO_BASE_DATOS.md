# Respaldo y restauración de base de datos - SmartClinic

Este documento deja evidencia de mantenimiento para la versión Docker/local del proyecto.

## Crear respaldo

Desde la raíz del proyecto:

```bash
docker compose exec db sh -c 'mysqldump -u root smartclinic_db' > backups/smartclinic_backup_$(date +%Y%m%d_%H%M%S).sql
```

También se puede usar el script incluido:

```bash
bash scripts/backup_database.sh
```

El respaldo se guardará en la carpeta `backups`.

## Restaurar respaldo

```bash
bash scripts/restore_database.sh backups/NOMBRE_DEL_RESPALDO.sql
```

## Recomendación de uso académico

Antes de presentar o modificar datos importantes, crear un respaldo. Esto permite recuperar pacientes, médicos, citas, usuarios y configuración si ocurre un error durante pruebas.
