FROM php:8.1-apache

# Instalar extensiones
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Cambiar Apache para usar el puerto de Render
RUN sed -i 's/80/${PORT}/g' /etc/apache2/ports.conf \
 && sed -i 's/:80>/:${PORT}>/g' /etc/apache2/sites-available/000-default.conf

# Copiar proyecto
COPY . /var/www/html/

# Permisos
RUN chown -R www-data:www-data /var/www/html

EXPOSE ${PORT}
0
FROM php:8.1-apache

# Instalar extensiones
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Cambiar Apache para usar el puerto de Render
RUN sed -i 's/80/${PORT}/g' /etc/apache2/ports.conf \
 && sed -i 's/:80>/:${PORT}>/g' /etc/apache2/sites-available/000-default.conf

# Copiar proyecto
COPY . /var/www/html/

# Permisos
RUN chown -R www-data:www-data /var/www/html

EXPOSE ${PORT}
0
