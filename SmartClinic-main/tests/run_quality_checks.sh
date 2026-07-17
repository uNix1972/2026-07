#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

echo "Validando sintaxis PHP..."
while IFS= read -r -d '' file; do
  php -l "$file" > /dev/null
done < <(find . -path './vendor' -prune -o -name '*.php' -print0)

echo "Ejecutando pruebas rápidas..."
php tests/run_quality_checks.php
