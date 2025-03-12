FROM php:8.3-cli

RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip \
    libzip-dev \
    libpq-dev \
    && docker-php-ext-install pdo_mysql zip

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN curl -sS https://get.symfony.com/cli/installer | bash \
    && mv /root/.symfony5/bin/symfony /usr/local/bin/symfony

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --ignore-platform-reqs

COPY . .
RUN composer dump-autoload --optimize

# права
RUN chmod -R 777 var/

# Внимание! Встроенный сервер! Не рекомендуется для запуска в проде!
# И лучше выбрать правильный день если уж надо в прод ;) --> https://deployhoroscope.ru/
CMD ["symfony", "server:start", "--no-tls", "--allow-http", "--listen-ip=0.0.0.0", "--port=8000"]
