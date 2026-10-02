#!/usr/bin/env bash
#
# Recria o banco do zero para começar a cadastrar dados reais.
#   - APAGA TODOS OS DADOS do banco configurado no .env (projetos, atividades, clientes, usuários...)
#   - recria as tabelas e os papéis (account_manager, pre_vendas, admin_geral)
#   - cria o primeiro Admin Geral do Sistema (a senha é pedida no terminal)
#
# Uso:
#   scripts/iniciar-do-zero.sh                          # pergunta e-mail e nome
#   scripts/iniciar-do-zero.sh email@empresa.com "Seu Nome"
#   scripts/iniciar-do-zero.sh --producao ...           # obrigatório se APP_ENV=production
#
# Depois, os demais usuários são cadastrados pelo Admin na tela Usuários.

set -euo pipefail

cd "$(dirname "$0")/.."

vermelho=$'\e[31m'; verde=$'\e[32m'; amarelo=$'\e[33m'; negrito=$'\e[1m'; normal=$'\e[0m'
erro() { echo "${vermelho}Erro:${normal} $*" >&2; exit 1; }

producao=false
if [[ "${1:-}" == "--producao" ]]; then producao=true; shift; fi

command -v php >/dev/null || erro "PHP não encontrado."
[[ -f .env ]] || erro "Arquivo .env não encontrado. Copie o .env.example e configure o banco antes."
[[ -d vendor ]] || erro "Dependências não instaladas. Rode 'composer install' antes."

config() { php artisan tinker --no-interaction --execute="echo config('$1');" 2>/dev/null | tail -n 1; }

ambiente="$(config app.env)"
conexao="$(config database.default)"
banco="$(config "database.connections.$conexao.database")"
host="$(config "database.connections.$conexao.host")"

[[ -n "$banco" ]] || erro "Não foi possível ler o banco configurado. Verifique o .env (DB_DATABASE)."

if [[ "$ambiente" == "production" && "$producao" != true ]]; then
    erro "APP_ENV=production. Se é isso mesmo, rode de novo com --producao."
fi

email="${1:-}"
nome="${2:-}"
[[ -n "$email" ]] || read -r -p "E-mail do Admin Geral do Sistema: " email
[[ -n "$nome" ]] || read -r -p "Nome do Admin Geral do Sistema: " nome
[[ -n "$email" && -n "$nome" ]] || erro "Informe o e-mail e o nome do Admin."

echo
echo "${negrito}${vermelho}ATENÇÃO: todos os dados serão APAGADOS.${normal}"
echo "  Banco:     ${negrito}$banco${normal} ($conexao em ${host:-local}, ambiente: $ambiente)"
echo "  Admin:     $nome <$email>"
echo
read -r -p "Para confirmar, digite o nome do banco ($banco): " confirmacao
[[ "$confirmacao" == "$banco" ]] || erro "Confirmação diferente do nome do banco. Nada foi alterado."

echo
echo "${amarelo}1/3${normal} Recriando as tabelas e os papéis..."
php artisan migrate:fresh --seed --seeder=PapeisSeeder --force --no-interaction

echo
echo "${amarelo}2/3${normal} Criando o Admin Geral do Sistema (mínimo 8 caracteres, com letras e números)..."
for tentativa in 1 2 3; do
    if php artisan usuarios:criar-admin "$email" "$nome"; then
        break
    fi
    if [[ $tentativa -eq 3 ]]; then
        erro "O Admin não foi criado. O banco já está vazio: rode 'php artisan usuarios:criar-admin $email \"$nome\"' para tentar de novo."
    fi
    echo "${amarelo}Tente de novo ($((tentativa + 1))/3).${normal}"
done

echo
echo "${amarelo}3/3${normal} Limpando caches..."
php artisan optimize:clear --no-interaction >/dev/null

echo
echo "${verde}Pronto!${normal} Banco '$banco' recriado. Entre no sistema com $email."
