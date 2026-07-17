#!/usr/bin/env bash
set -euo pipefail

if [ "$#" -ne 1 ]; then
  echo "Uso: bash scripts/restore_database.sh backups/archivo.sql"
  exit 1
fi

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"
BACKUP_FILE="$1"

if [ ! -f "$BACKUP_FILE" ]; then
  echo "No existe el archivo: $BACKUP_FILE"
  exit 1
fi

docker compose exec -T db sh -c 'mysql -u root smartclinic_db' < "$BACKUP_FILE"
echo "Base de datos restaurada desde: $BACKUP_FILE"
