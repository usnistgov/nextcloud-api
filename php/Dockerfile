FROM nextcloud:28-apache

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install mysqli extension
RUN docker-php-ext-install mysqli

# Create 'api' directory
RUN mkdir /var/www/api

# Copy API into the image within 'api' directory
COPY ./ /var/www/api/

# Move to api directory to install dependencies
WORKDIR /var/www/api

# Install dependencies
RUN composer install
RUN apt-get update && apt-get install -y sudo

# Enable URL rewriting, headers and SSL
RUN a2enmod rewrite headers ssl

# Copy the Apache configuration
COPY 000-default.conf /etc/apache2/sites-available/000-default.conf
COPY ports.conf /etc/apache2/ports.conf

# Add ServerName directive
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Adjust permissions
RUN chown -R www-data:www-data /var/www/

# Enable SSL module
RUN a2enmod ssl

# Return to root directory
WORKDIR /var/www/html

# Adjust scripts permissions
RUN chmod +x /var/www/api/scripts/*

# Start Apache in the foreground (for the running container)
CMD ["apache2-foreground"]