FROM php:8.2-apache

# Instalar extensión mysqli para MySQL
RUN docker-php-ext-install mysqli

# Copiar todo el proyecto
COPY . /var/www/html/

# Habilitar mod_rewrite (por si usas .htaccess)
RUN a2enmod rewrite
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Railway inyecta PORT automáticamente — Apache lo usa al arrancar
CMD sed -i "s/Listen 80/Listen ${PORT:-80}/" /etc/apache2/ports.conf && \
    sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT:-80}>/" /etc/apache2/sites-enabled/000-default.conf && \
    apache2-foreground
