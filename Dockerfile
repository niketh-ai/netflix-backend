FROM php:8.1-apache

# Install PostgreSQL support
RUN docker-php-ext-install pdo pdo_pgsql

# Copy application files
COPY . /var/www/html/

# Set working directory
WORKDIR /var/www/html

# Start Apache
CMD ["apache2-foreground"]
