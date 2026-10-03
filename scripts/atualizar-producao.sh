#!/usr/bin/env bash
#
# Atualiza o sistema em produção (Docker) com a última versão do GitHub.
#   1. faz backup do banco   2. baixa o código   3. remonta a imagem e reinicia (as migrations rodam sozinhas)
#
# Uso: scripts/atualizar-producao.sh

# Entre chaves: o bash lê o script inteiro antes de rodar, então o "git pull" pode atualizá-lo sem problema.
{
set -euo pipefail

cd "$(dirname "$0")/.."

echo "1/3 Backup do banco..."
scripts/backup-producao.sh

echo
echo "2/3 Baixando a versão nova..."
git pull --ff-only

echo
echo "3/3 Remontando a imagem e reiniciando..."
docker compose up -d --build
docker image prune -f >/dev/null

echo
echo "Pronto! Acompanhe a subida com: docker compose logs -f app"
exit
}
