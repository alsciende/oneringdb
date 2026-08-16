FROM php:8.3-alpine

RUN apk update && apk add --no-cache \
           bash bash-completion autoconf make

RUN apk add --no-cache \
           postgresql-dev \
       && docker-php-ext-install -j$(nproc) pgsql \
       && docker-php-ext-install -j$(nproc) pdo_pgsql

RUN docker-php-ext-install -j$(nproc) intl opcache

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN curl -sS https://get.symfony.com/cli/installer | bash
RUN mv /root/.symfony5/bin/symfony /usr/local/bin/symfony

WORKDIR /var/www/html
