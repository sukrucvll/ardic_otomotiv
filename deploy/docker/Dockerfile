# İki uygulama için ortak imaj. Derleme bağlamı depo köküdür; hangi uygulamanın
# derleneceği APP argümanıyla seçilir (personel-sistemi | egitim-portali).
FROM php:8.4-apache

ARG APP

# Resmi PHP imajında pdo_sqlite, mbstring, curl ve openssl hazır gelir
RUN apt-get update \
    && apt-get install -y --no-install-recommends unzip \
    && rm -rf /var/lib/apt/lists/* \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && a2enmod rewrite headers \
    && echo "ServerName localhost" > /etc/apache2/conf-available/servername.conf \
    && a2enconf servername

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY deploy/docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY deploy/docker/entrypoint.sh /usr/local/bin/entrypoint

WORKDIR /var/www/html

# Bağımlılıklar ayrı katmanda: yalnızca composer.lock değişince yeniden indirilir
COPY ${APP}/composer.json ${APP}/composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY ${APP}/ ./
RUN composer dump-autoload --optimize --no-dev \
    && chmod +x /usr/local/bin/entrypoint \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 80
ENTRYPOINT ["entrypoint"]
