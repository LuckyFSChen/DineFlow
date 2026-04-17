#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

cd "$ROOT_DIR"

echo "[1/5] Pulling latest code..."
git pull

echo "[2/5] Running database migrations..."
php artisan migrate

echo "[3/5] Building frontend assets..."
npm run build

echo "[4/5] Optimizing Laravel caches..."
php artisan optimize

echo "[5/5] Restarting queue workers..."
php artisan queue:restart

echo "Done. Deployment steps completed successfully."
