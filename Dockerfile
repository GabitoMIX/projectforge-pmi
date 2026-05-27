FROM php:8.2-apache

# ProjectForge PMI necesita:
# - Apache + PHP para ejecutar la aplicación.
# - pdo_mysql para conectar PHP con MySQL/MariaDB usando PDO.
# - gd para validar/procesar imágenes y logos.
# - rewrite/headers para permitir .htaccess y cabeceras básicas.

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql gd \
    && a2enmod rewrite headers \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# La carpeta pública real del proyecto es public/.
# Esto evita exponer app/, database/, docs/ y otros archivos internos.
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!<Directory /var/www/>!<Directory /var/www/html/public/>!g' /etc/apache2/apache2.conf \
    && sed -ri -e 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf

WORKDIR /var/www/html

COPY . /var/www/html

# Permisos para carga de logos.
RUN mkdir -p /var/www/html/public/uploads/logos \
    && chown -R www-data:www-data /var/www/html/public/uploads \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && find /var/www/html -type f -exec chmod 644 {} \;

EXPOSE 80

CMD ["apache2-foreground"]
