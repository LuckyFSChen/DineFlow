#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

cd "$ROOT_DIR"

echo "[1/6] Pulling latest code..."
git pull

echo "[2/6] Running database migrations..."
php artisan migrate

echo "[3/6] Building frontend assets..."
npm run build

echo "[4/6] Optimizing Laravel caches..."
php artisan optimize

echo "[5/6] Restarting Reverb server..."
php artisan reverb:restart

echo "[6/6] Restarting queue workers..."
php artisan queue:restart

echo "Done. Deployment steps completed successfully."
