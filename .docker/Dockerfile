FROM php:7.2-apache

MAINTAINER Valentyn Saik

COPY . /srv/app
COPY .docker/vhost.conf /etc/apache2/sites-available/000-default.conf
#COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /srv/app

RUN apt-get update

RUN a2enmod rewrite

RUN apt-get install -y libpq-dev \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install mbstring pdo pdo_pgsql pgsql \
    && chown -R www-data:www-data /srv/app