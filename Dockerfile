# syntax=docker/dockerfile:1

FROM php:8.2-cli-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libzip-dev \
    && docker-php-ext-install zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

COPY . .

RUN chmod +x /app/bin/serve \
    && test -d /app/public \
    && test -f /app/public/router.php

ENV PORT=8080
EXPOSE 8080

# Absolute entrypoint — ignores process cwd and Railway start-command path quirks.
CMD ["/app/bin/serve"]
