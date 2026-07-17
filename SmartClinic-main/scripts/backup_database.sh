#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"
mkdir -p backups
STAMP="$(date +%Y%m%d_%H%M%S)"
OUT="backups/smartclinic_backup_${STAMP}.sql"

docker compose exec -T db sh -c 'mysqldump -u root smartclinic_db' > "$OUT"
echo "Respaldo creado: $OUT"
