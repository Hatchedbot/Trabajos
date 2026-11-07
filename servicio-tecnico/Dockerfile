# Imagen de PHP con Apache para el sistema de servicio técnico
FROM php:8.2-apache

# Extensión para conectarse a MySQL/MariaDB
RUN docker-php-ext-install pdo_mysql

# Zona horaria de PHP
RUN echo "date.timezone=America/Bogota" > /usr/local/etc/php/conf.d/zona-horaria.ini

COPY . /var/www/html/
RUN mkdir -p /var/www/html/datos && chown -R www-data:www-data /var/www/html/datos
