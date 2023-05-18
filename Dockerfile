FROM php:8.2.5-apache

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy API into the image
COPY ./ /var/www/html/

# Install dependencies
RUN composer install

# Enable URL rewriting and SSL
RUN a2enmod rewrite ssl

# Copy the Apache configuration
COPY 000-default.conf /etc/apache2/sites-available/000-default.conf

# Adjust permissions
RUN chown -R www-data:www-data /var/www/html/
