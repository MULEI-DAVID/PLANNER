FROM php:8.2-apache

RUN docker-php-eixt-install mysqli pdo pdo_mysl

RUN a2enmod rewrite

COPY . /var/wwww/html/



RUN chown -R www-data:www-data /var/www/html
