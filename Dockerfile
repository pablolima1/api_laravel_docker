FROM php:8.4-fpm-alpine

ARG user=pablolima 
ARG uid=1000

WORKDIR /var/www/html

RUN apk add --no-cache \
    bash \
    git \
    unzip \
    curl \
    libzip-dev \
    libpng-dev \
    libxml2-dev \
    zlib-dev \
    oniguruma-dev \
    curl-dev \
    shadow \
    autoconf \
    gcc \
    g++ \
    make \
    musl-dev

RUN docker-php-ext-install \
    zip \
    pdo \
    pdo_mysql \
    mbstring \
    fileinfo \
    xml \
    dom \
    curl

RUN pecl install redis \
    && docker-php-ext-enable redis

COPY --from=node:lts-alpine /usr/local/ /usr/local/

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN addgroup -g $uid $user \
    && adduser -D -u $uid -G $user -h /home/$user $user \
    && addgroup $user www-data \
    && addgroup $user root

EXPOSE 9000

CMD ["php-fpm"]