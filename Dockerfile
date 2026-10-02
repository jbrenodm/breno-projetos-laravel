FROM ubuntu:22.04

ENV DEBIAN_FRONTEND=noninteractive TZ=America/Sao_Paulo

RUN apt-get update && apt-get install -y software-properties-common curl git unzip ca-certificates \
 && add-apt-repository -y ppa:ondrej/php && apt-get update \
 && apt-get install -y php8.3-cli php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip \
    php8.3-bcmath php8.3-intl php8.3-pgsql php8.3-gd php8.3-readline \
 && curl -fsSL https://deb.nodesource.com/setup_22.x | bash - && apt-get install -y nodejs \
 && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
 && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/app
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]