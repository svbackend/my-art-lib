FROM php:7.2-apache

MAINTAINER Valentyn Saik

COPY . /srv/app
COPY .docker/vhost.conf /etc/apache2/sites-available/000-default.conf
COPY .docker/supervisord.conf /etc/supervisord.conf

WORKDIR /srv/app

RUN apt-get update
RUN a2enmod rewrite
RUN a2enmod headers

# ZIP + ZLIB
RUN apt-get install -y \
        supervisor \
        libzip-dev \
        zip \
  && docker-php-ext-configure zip --with-libzip \
  && docker-php-ext-install zip

RUN apt-get install -y libwebp-dev libjpeg62-turbo-dev libpng-dev libxpm-dev libfreetype6-dev
RUN docker-php-ext-configure gd --with-gd --with-webp-dir --with-jpeg-dir --with-png-dir --with-zlib-dir --with-xpm-dir --with-freetype-dir \
    && docker-php-ext-install gd

RUN apt-get install -y libpq-dev \
    && mkdir -p /usr/src/php/ext/redis \
    && curl -L https://github.com/phpredis/phpredis/archive/4.0.2.tar.gz | tar xvz -C /usr/src/php/ext/redis --strip 1 \
    && echo 'redis' >> /usr/src/php-available-exts \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install redis mbstring pdo pdo_pgsql pgsql \
    && chown -R www-data:www-data /srv/app