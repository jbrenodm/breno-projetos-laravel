#!/usr/bin/env bash
#
# Copia de segurança do banco de produção (Docker) para a pasta backups/.
# Guarda os 30 backups mais recentes.
#
# Uso:      scripts/backup-producao.sh
# Restaurar (SUBSTITUI os dados atuais):
#   docker compose exec -T db sh -c 'pg_restore -U "$POSTGRES_USER" -d "$POSTGRES_DB" --clean --if-exists' < backups/ARQUIVO.dump

set -euo pipefail

cd "$(dirname "$0")/.."

mkdir -p backups
arquivo="backups/controle-projetos-$(date +%Y%m%d-%H%M%S).dump"

docker compose exec -T db sh -c 'pg_dump -U "$POSTGRES_USER" -d "$POSTGRES_DB" -Fc' > "$arquivo" \
    || { rm -f "$arquivo"; echo "Erro: backup não foi feito. O sistema está rodando? (docker compose ps)" >&2; exit 1; }

ls -1t backups/controle-projetos-*.dump | tail -n +31 | xargs -r rm --

echo "Backup salvo em $arquivo ($(du -h "$arquivo" | cut -f1))."
