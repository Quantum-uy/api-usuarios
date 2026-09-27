FROM php:8.2-apache

RUN docker-php-ext-install mysqli \
    && a2enmod rewrite

COPY api-usuarios/ /var/www/html/
COPY auth.php /var/www/auth.php
COPY php-sessions.ini /usr/local/etc/php/conf.d/sessions.ini

RUN mkdir -p /var/lib/php/sessions && chown -R www-data:www-data /var/www/html /var/lib/php/sessions

EXPOSE 80
