#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

cd "$ROOT_DIR"

echo "[1/4] Running database migrations..."
php artisan migrate

echo "[2/4] Building frontend assets..."
npm run build

echo "[3/4] Optimizing Laravel caches..."
php artisan optimize

echo "[4/4] Restarting queue workers..."
php artisan queue:restart

echo "Done. Deployment steps completed successfully."
