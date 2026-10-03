#!/usr/bin/env bash
#
# Instala o sistema num servidor com Docker (produção).
#   - cria o .env a partir do .env.producao.example (APP_KEY e senha do banco gerados na hora)
#   - monta a imagem, sobe o sistema e o PostgreSQL e cria o primeiro Admin Geral do Sistema
#
# Uso (na pasta do projeto, depois do git clone):
#   scripts/instalar-producao.sh
#
# Pode rodar de novo sem perder dados: o .env existente é mantido.

set -euo pipefail

cd "$(dirname "$0")/.."

vermelho=$'\e[31m'; verde=$'\e[32m'; amarelo=$'\e[33m'; negrito=$'\e[1m'; normal=$'\e[0m'
erro() { echo "${vermelho}Erro:${normal} $*" >&2; exit 1; }

command -v docker >/dev/null || erro "Docker não encontrado. Instale o Docker antes."
docker compose version >/dev/null 2>&1 || erro "Docker Compose não encontrado (comando 'docker compose')."
docker info >/dev/null 2>&1 || erro "Sem permissão para usar o Docker. Adicione seu usuário ao grupo docker
  (sudo usermod -aG docker \$USER), saia e entre de novo no servidor e rode este script outra vez."

# Porta ocupada por outro programa? (sem o "ss" instalado, considera livre)
porta_em_uso() { ss -Hltn "sport = :$1" 2>/dev/null | grep -q .; }
porta_livre_a_partir_de() { local p="$1"; while porta_em_uso "$p"; do p=$((p + 1)); done; echo "$p"; }
definir_porta() {
    sed -i "s|^APP_PORTA=.*|APP_PORTA=$1|" .env
    sed -i "s|^\(APP_URL=http://[^:/]*\):[0-9]*|\1:$1|" .env   # só troca a porta: mantém o endereço
}

if [[ ! -f .env ]]; then
    cp .env.producao.example .env

    ip="$(hostname -I 2>/dev/null | awk '{print $1}')"
    sed -i "s|^APP_KEY=.*|APP_KEY=base64:$(head -c 32 /dev/urandom | base64)|" .env
    sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=$(tr -dc 'A-Za-z0-9' </dev/urandom | head -c 32)|" .env
    [[ -n "$ip" ]] && sed -i "s|^APP_URL=http://IP-DO-SERVIDOR|APP_URL=http://$ip|" .env
    definir_porta "$(porta_livre_a_partir_de 8000)"
    chmod 600 .env

    echo "${verde}.env criado${normal} com chave e senha do banco geradas. Endereço: $(grep '^APP_URL=' .env | cut -d= -f2-)"
else
    echo "${amarelo}.env já existe${normal}: mantido como está."
    grep -q '^COMPOSE_FILE=docker-compose.producao.yml' .env \
        || erro "O .env não é de produção (falta COMPOSE_FILE=docker-compose.producao.yml). Compare com o .env.producao.example."
fi

porta="$(grep '^APP_PORTA=' .env | cut -d= -f2-)"
porta="${porta:-8000}"

# Se a porta está ocupada e não é pelo próprio sistema, oferece a próxima livre.
if porta_em_uso "$porta" && ! docker compose ps --status running -q app 2>/dev/null | grep -q .; then
    sugestao="$(porta_livre_a_partir_de "$porta")"
    read -r -p "A porta $porta já está em uso por outro programa. Usar a porta $sugestao? [S/n] " resposta
    [[ "${resposta:-S}" =~ ^[Ss]$ ]] || erro "Escolha uma porta livre em APP_PORTA e APP_URL no .env e rode este script de novo."
    definir_porta "$sugestao"
    porta="$sugestao"
    echo "Porta trocada para ${negrito}$porta${normal}. Endereço: $(grep '^APP_URL=' .env | cut -d= -f2-)"
fi

echo
echo "${amarelo}1/3${normal} Montando a imagem e subindo os containers (a primeira vez demora alguns minutos)..."
docker compose up -d --build

echo
echo "${amarelo}2/3${normal} Aguardando o sistema responder na porta $porta..."
for _ in $(seq 1 60); do
    if curl -fs -o /dev/null "http://127.0.0.1:$porta/login"; then
        break
    fi
    sleep 2
done
curl -fs -o /dev/null "http://127.0.0.1:$porta/login" \
    || erro "O sistema não respondeu. Veja o que aconteceu com: docker compose logs app"

echo
admins="$(docker compose exec -T db sh -c 'psql -U "$POSTGRES_USER" -d "$POSTGRES_DB" -tA' <<'SQL'
select count(*) from users u
join role_user ru on ru.user_id = u.id
join roles r on r.id = ru.role_id
where r.nome = 'admin_geral' and u.ativo;
SQL
)"
if [[ "$admins" == "0" ]]; then
    echo "${amarelo}3/3${normal} Criando o Admin Geral do Sistema (mínimo 8 caracteres, com letras e números)..."
    read -r -p "E-mail do Admin Geral do Sistema: " email
    read -r -p "Nome do Admin Geral do Sistema: " nome
    docker compose exec -u www-data app php artisan usuarios:criar-admin "$email" "$nome" \
        || erro "O Admin não foi criado. Rode este script de novo para tentar outra vez."
else
    echo "${amarelo}3/3${normal} Admin Geral do Sistema já existe: nada a fazer."
fi

echo
echo "${verde}Pronto!${normal} Acesse ${negrito}$(grep '^APP_URL=' .env | cut -d= -f2-)${normal}"
