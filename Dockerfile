FROM dunglas/frankenphp:php8.4-alpine

ENV SERVER_NAME=":80"
ENV OCTANE_SERVER="frankenphp"

RUN install-php-extensions \
    pdo_pgsql \
    redis \
    bcmath \
    opcache \
    pcntl \
    zip \
    gd

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install --no-interaction --optimize-autoloader --no-scripts

COPY . .

RUN chown -R root:root /app \
    && chmod -R 775 /app/storage \
    && chmod -R 775 /app/bootstrap/cache


EXPOSE 80 443 443/udp 2019


COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]

CMD ["php", "artisan", "octane:start", "--server=frankenphp", "--host=0.0.0.0", "--port=80", "--admin-port=2019", "--workers=auto"]