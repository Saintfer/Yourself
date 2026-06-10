FROM php:8.2-cli

# Instalar extensión mysqli para MySQL
RUN docker-php-ext-install mysqli

# Copiar todo el proyecto
COPY . /var/www/html/

WORKDIR /var/www/html

# Servidor PHP integrado (sin Apache, sin conflictos)
CMD php -S 0.0.0.0:${PORT:-80}
