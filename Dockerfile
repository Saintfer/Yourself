FROM php:8.2-apache

# Instalar extensión mysqli para MySQL
RUN docker-php-ext-install mysqli

# Copiar todo el proyecto
COPY . /var/www/html/

# Habilitar mod_rewrite (por si usas .htaccess)
RUN a2enmod rewrite
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

EXPOSE 80
