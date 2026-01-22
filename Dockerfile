FROM php:8.1-apache

RUN docker-php-ext-install mysqli pdo pdo_mysql

# Apache en 8080 (Render-friendly)
RUN sed -i 's/80/8080/g' /etc/apache2/ports.conf \
 && sed -i 's/:80>/:8080>/g' /etc/apache2/sites-available/000-default.conf

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html

EXPOSE 8080

