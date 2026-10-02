#!/usr/bin/env bash
# Prepares a fresh container: installs a key if missing, migrates, warms caches.
set -euo pipefail

cd /app

if [ ! -f .env ]; then
    cp .env.example .env
fi

if ! grep -qE '^APP_KEY=.+' .env; then
    echo ">> APP_KEY absent, generation d'une cle..."
    php artisan key:generate --force
fi

if [ "${AUTO_MIGRATE:-true}" = "true" ]; then
    echo ">> migrations..."
    php artisan migrate --force --no-interaction
fi

if [ "${AUTO_SEED:-false}" = "true" ]; then
    echo ">> seed..."
    php artisan db:seed --force --no-interaction
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache

echo ">> pret. Demarrage de l'application."
exec "$@"