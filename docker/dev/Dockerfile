# Local dev/test image: PHP 8.3 + extensions + composer + pcov.
# Build: docker build -f docker/dev/Dockerfile -t joszomszedsag-dev .
# Test:  docker run --rm -v .:/app -w /app joszomszedsag-dev vendor/bin/phpunit
FROM php:8.3-cli

RUN apt-get update && apt-get install -y --no-install-recommends \
    git curl unzip libzip-dev libicu-dev libonig-dev libsqlite3-dev sqlite3 \
    && docker-php-ext-install -j$(nproc) mbstring intl zip pdo pdo_sqlite pdo_mysql bcmath pcntl sockets \
    && pecl install pcov \
    && docker-php-ext-enable pcov \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_HOME=/tmp/composer \
    PHP_CLI_SERVER_WORKERS=1

WORKDIR /app
