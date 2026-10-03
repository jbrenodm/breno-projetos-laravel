# Ambiente de desenvolvimento: PHP 8.5 (mesma versão da VM — ver docs/REQUISITOS.md §8).
FROM php:8.5-cli

ENV TZ=America/Sao_Paulo

RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libpq-dev libicu-dev libzip-dev \
 && docker-php-ext-install pdo_pgsql pgsql intl zip bcmath pcntl \
 && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Usuário com o mesmo UID/GID do dono do projeto na VM: arquivos criados pelo
# container (vendor, storage, logs) continuam editáveis fora dele.
ARG UID=1000
ARG GID=1000
RUN groupadd -g ${GID} app && useradd -m -u ${UID} -g ${GID} -s /bin/bash app

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

USER app
WORKDIR /var/www/app

EXPOSE 8000
ENTRYPOINT ["entrypoint.sh"]
# --no-reload: sem ele o "artisan serve" descarta as variáveis do docker-compose.yml
# e relê o .env (DB_HOST=127.0.0.1). Alterou o .env? Rode "docker compose restart app".
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000", "--no-reload"]
