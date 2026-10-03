#!/usr/bin/env bash
# Produção: prepara o banco e os caches antes de subir o Apache.
set -euo pipefail

[[ "${APP_KEY:-}" == base64:* ]] || { echo "APP_KEY não definida no .env. Rode scripts/instalar-producao.sh." >&2; exit 1; }

artisan() { runuser -u www-data -- php artisan "$@" --no-interaction; }

artisan migrate --force
artisan db:seed --class=PapeisSeeder --force   # só cria os papéis que faltam (RN-25/RN-41)
artisan optimize                               # config, rotas, views e eventos em cache

exec "$@"
