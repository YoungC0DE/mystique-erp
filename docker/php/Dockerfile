FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
    bash \
    git \
    unzip \
    icu-dev \
    libzip-dev \
    oniguruma-dev \
    libsodium-dev \
    $PHPIZE_DEPS \
    linux-headers

RUN docker-php-ext-install \
    pdo_mysql \
    bcmath \
    intl \
    zip \
    opcache \
    pcntl \
    sodium

# 6.3.0 no PECL pode falhar com "REST dependency information" — versão fixa.
RUN pecl install redis-6.2.0 && docker-php-ext-enable redis

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY entrypoint.sh /usr/local/bin/entrypoint
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint \
    && chmod +x /usr/local/bin/entrypoint

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint"]
CMD ["php-fpm"]
