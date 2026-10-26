# Production runtime: PHP 8.3 + nginx + supervisor (Laravel 12).
# Build: docker compose build / docker compose up --build
FROM php:8.3-fpm-alpine3.20

COPY ./startup.sh /startup

RUN chmod +x /startup && apk update \
    && apk --no-cache add openssh git oniguruma oniguruma-dev

# Utilities
RUN apk --no-cache add \
    nginx \
    ca-certificates \
    supervisor \
    bash \
    pcre-dev ${PHPIZE_DEPS} \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer

# Install php extensions (pinned for reproducibility)
RUN apk --no-cache add \
    # mbstring
    && docker-php-ext-install mbstring \
    # intl
    && apk add icu-dev && docker-php-ext-install -j$(nproc) intl \
    # zip
    && apk add zlib-dev libzip libzip-dev && docker-php-ext-install -j$(nproc) zip \
    # imagick
    && apk add imagemagick-dev \
    && pecl channel-update pecl.php.net \
    && yes | pecl install imagick-3.8.0 \
    && docker-php-ext-enable imagick \
    # mysql + sqlite (sqlite for queue/cache portability)
    && docker-php-ext-install mysqli && docker-php-ext-install pdo_mysql pdo_sqlite \
    # redis
    && apk add zstd-dev && yes | pecl install igbinary-3.2.16 redis-6.2.0 \
    && docker-php-ext-enable igbinary redis \
    # sockets, pcntl, bcmath, opcache
    && docker-php-ext-install sockets pcntl bcmath opcache

# Clean up
RUN rm -rf /etc/nginx/conf.d/default.conf \
    && rm -rf /tmp/* /var/cache/apk/* /var/lib/apk/* /etc/apk/cache/* \
    && apk del pcre-dev ${PHPIZE_DEPS}

# Configure
COPY ./config/nginx/nginx.conf /etc/nginx/nginx.conf
COPY ./config/nginx/default.conf /etc/nginx/default-server.conf
COPY ./config/supervisor/queue.conf /etc/supervisor/queue.conf
COPY ./config/supervisor/supervisor.conf /etc/supervisor/supervisord.conf

# Set working directory
WORKDIR /var/www

# Set volumes
VOLUME /var/www

# Expose port 80
EXPOSE 80

# Start container process
CMD ["/startup"]
