FROM php:8.2-apache

# Install MariaDB server and client
RUN apt-get update && apt-get install -y mariadb-server mariadb-client && rm -rf /var/lib/apt/lists/*

# Install PDO MySQL extension and OPcache
RUN docker-php-ext-install pdo pdo_mysql opcache

# Enable Apache mod_rewrite, plus compression and caching headers
RUN a2enmod rewrite deflate expires headers

# Production PHP settings (output buffering, no on-page errors, OPcache)
COPY docker/php-prod.ini /usr/local/etc/php/conf.d/zz-nuahn.ini

# Compression + browser caching for static assets
COPY docker/apache-performance.conf /etc/apache2/conf-available/nuahn-performance.conf
RUN a2enconf nuahn-performance

# Allow .htaccess overrides
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

WORKDIR /var/www/html

# Copy all app files
COPY . .

# Remove local db.php — generated at runtime
RUN rm -f config/db.php

# Ensure uploads dir is writable
RUN mkdir -p uploads/jobs && chown -R www-data:www-data /var/www/html

# Copy entrypoint
COPY docker-entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
