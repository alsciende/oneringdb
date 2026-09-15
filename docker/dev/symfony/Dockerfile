FROM php:8.5-alpine

RUN apk update && apk add --no-cache \
           bash bash-completion

RUN apk add --no-cache \
           postgresql-dev \
       && docker-php-ext-install pgsql \
       && docker-php-ext-install pdo_pgsql

RUN docker-php-ext-install intl

RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS linux-headers \
    && pecl channel-update pecl.php.net \
    && pecl install xdebug \
    && apk del .build-deps

RUN docker-php-ext-enable xdebug

ENV XDEBUG_MODE=coverage

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN curl -sS https://get.symfony.com/cli/installer | bash
RUN mv /root/.symfony5/bin/symfony /usr/local/bin/symfony

WORKDIR /var/www/html
