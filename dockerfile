FROM php:7.1

RUN apt-get update -y && \
    apt-get install -y sendmail \
    libzip-dev \
    libxml2-dev \
    libmcrypt-dev

RUN docker-php-ext-install \
    zip \
    xml \
    mcrypt

# COPY ./php.user.ini /usr/local/etc/php/conf.d/php.user.ini

ENV COMPOSER_MEMORY_LIMIT -1

WORKDIR /var/www/html

COPY --from=composer:1 /usr/bin/composer /usr/bin/composer
