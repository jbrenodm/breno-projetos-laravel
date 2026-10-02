#!/usr/bin/env bash
# =============================================================================
# breno-projetos-laravel — preparação do ambiente de desenvolvimento
# Alvo: Ubuntu Server 26.04 LTS (PHP 8.5, PostgreSQL 18). Funciona também no 24.04.
#
# Uso (dentro da pasta do projeto):   bash scripts/setup-ubuntu.sh
# Variáveis opcionais: DB_PASSWORD=... (evita a pergunta)  PULAR_APT=1 (não instala pacotes)
# Pode ser executado mais de uma vez com segurança.
# =============================================================================
set -euo pipefail

DB_NAME="breno_projetos_laravel"
DB_TEST_NAME="breno_projetos_testing"
DB_USER="breno"

verde()  { printf '\n\033[1;32m==> %s\033[0m\n' "$*"; }
amarelo(){ printf '\033[1;33m%s\033[0m\n' "$*"; }

cd "$(dirname "$0")/.."
[[ -f artisan ]] || { echo "Execute a partir da pasta do projeto (onde está o arquivo artisan)."; exit 1; }

# -----------------------------------------------------------------------------
verde "1/6 Instalando pacotes do sistema (PHP, Composer, PostgreSQL, Git)"
if [[ "${PULAR_APT:-0}" != "1" ]]; then
    sudo apt-get update -y
    sudo apt-get install -y \
        git unzip curl ca-certificates \
        php-cli php-pgsql php-sqlite3 php-mbstring php-xml php-curl php-zip php-intl php-bcmath \
        composer postgresql postgresql-contrib
fi

php -v | head -1
composer --version 2>/dev/null | head -1
psql --version

# -----------------------------------------------------------------------------
verde "2/6 Configurando o PostgreSQL"
sudo systemctl enable --now postgresql 2>/dev/null || sudo service postgresql start

if [[ -z "${DB_PASSWORD:-}" ]]; then
    amarelo "Escolha uma senha para o usuário '${DB_USER}' do PostgreSQL."
    amarelo "Use apenas letras e números (evita problemas de escape no .env)."
    read -r -s -p "Senha: " DB_PASSWORD; echo
fi
[[ "$DB_PASSWORD" =~ ^[A-Za-z0-9]{8,}$ ]] || { echo "Senha inválida: use 8+ caracteres, só letras e números."; exit 1; }

# sudo-rs (padrão no 26.04) aceita "sudo -u postgres"; evitamos "sudo -i" que falhava no ambiente antigo.
sudo -u postgres psql -v ON_ERROR_STOP=1 -q <<SQL
DO \$\$
BEGIN
    IF NOT EXISTS (SELECT FROM pg_roles WHERE rolname = '${DB_USER}') THEN
        CREATE ROLE ${DB_USER} LOGIN PASSWORD '${DB_PASSWORD}' CREATEDB;
    ELSE
        ALTER ROLE ${DB_USER} WITH LOGIN PASSWORD '${DB_PASSWORD}' CREATEDB;
    END IF;
END
\$\$;
SQL

for db in "$DB_NAME" "$DB_TEST_NAME"; do
    if ! sudo -u postgres psql -tAc "SELECT 1 FROM pg_database WHERE datname='${db}'" | grep -q 1; then
        sudo -u postgres createdb -O "$DB_USER" "$db"
        echo "Banco ${db} criado."
    else
        echo "Banco ${db} já existe."
    fi
done

# -----------------------------------------------------------------------------
verde "3/6 Configurando o .env"
[[ -f .env ]] || cp .env.example .env

definir_env() { # chave valor
    if grep -qE "^$1=" .env; then sed -i "s|^$1=.*|$1=$2|" .env; else echo "$1=$2" >> .env; fi
}
definir_env DB_CONNECTION pgsql
definir_env DB_HOST 127.0.0.1
definir_env DB_PORT 5432
definir_env DB_DATABASE "$DB_NAME"
definir_env DB_USERNAME "$DB_USER"
definir_env DB_PASSWORD "$DB_PASSWORD"
definir_env APP_LOCALE pt_BR
definir_env APP_TIMEZONE America/Sao_Paulo

# -----------------------------------------------------------------------------
verde "4/6 Instalando dependências PHP (composer install)"
composer install --no-interaction
grep -qE '^APP_KEY=base64' .env || php artisan key:generate

# -----------------------------------------------------------------------------
verde "5/6 Criando as tabelas e dados de exemplo"
amarelo "ATENÇÃO: migrate:fresh apaga e recria todas as tabelas do banco ${DB_NAME}."
read -r -p "Recriar o banco agora? [s/N] " resp
if [[ "${resp,,}" == "s" ]]; then
    php artisan migrate:fresh --seed
else
    php artisan migrate --seed
fi

# -----------------------------------------------------------------------------
verde "6/6 Rodando a suíte de testes"
php artisan test

IP=$(hostname -I | awk '{print $1}')
verde "Pronto!"
cat <<MSG
Para subir o sistema:
    php artisan serve --host=0.0.0.0 --port=8000

E abra no navegador do Windows:  http://${IP}:8000

Usuários de exemplo (senha: password): ana.am@breno.local, paula.pv@breno.local, ...
MSG
