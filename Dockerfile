# Red Hat OpenShift Compatible PHP Containerfile
# Base image: Official PHP 8.2 Apache
FROM php:8.2-apache

# Install PDO extensions for MySQL and PostgreSQL
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev \
    libzip-dev \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Configure Apache for Red Hat OpenShift non-root execution (Port 8080)
RUN sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf \
    && sed -i 's/:80/:8080/' /etc/apache2/sites-available/000-default.conf \
    && sed -i 's/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Set working directory and copy application source
WORKDIR /var/www/html
COPY . /var/www/html/

# Ensure OpenShift arbitrary UID compatibility (group 0 permissions)
RUN chown -R 1001:0 /var/www/html /var/log/apache2 /var/run/apache2 \
    && chmod -R g=u /var/www/html /var/log/apache2 /var/run/apache2

# Expose non-privileged HTTP port
EXPOSE 8080

# Run as non-root user (OpenShift standard UID)
USER 1001

CMD ["apache2-foreground"]
