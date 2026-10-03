# Produção: Apache + PHP 8.5 com o código dentro da imagem (sem volume do projeto).
# Montada no próprio servidor a partir do git clone — ver README, "Produção com Docker".
FROM php:8.5-apache

ENV TZ=America/Sao_Paulo

RUN apt-get update && apt-get install -y --no-install-recommends \
        unzip libpq-dev libicu-dev libzip-dev \
 && docker-php-ext-install pdo_pgsql pgsql intl zip bcmath \
 && rm -rf /var/lib/apt/lists/* \
 && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
 && a2enmod rewrite headers

COPY docker/producao/php.ini "$PHP_INI_DIR/conf.d/zz-producao.ini"
COPY docker/producao/apache.conf /etc/apache2/sites-available/000-default.conf
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/app

# Dependências primeiro: só são reinstaladas quando o composer.lock muda.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --no-autoloader --prefer-dist

COPY . .
RUN mkdir -p storage/app/public storage/framework/cache storage/framework/sessions storage/framework/views storage/logs \
 && composer dump-autoload --no-dev --optimize --no-interaction \
 && chown -R www-data:www-data storage bootstrap/cache

COPY docker/producao/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80
ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]
