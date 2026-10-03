#!/usr/bin/env bash
# Prepara o projeto (montado em /var/www/app) antes de subir o servidor.
set -euo pipefail

[[ -f .env ]] || { echo "Arquivo .env não encontrado. Copie o .env.example antes de subir o container." >&2; exit 1; }
[[ -f vendor/autoload.php ]] || composer install --no-interaction
grep -qE '^APP_KEY=base64' .env || php artisan key:generate --no-interaction

php artisan migrate --force --no-interaction

exec "$@"
