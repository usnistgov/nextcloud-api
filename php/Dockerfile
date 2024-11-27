FROM nextcloud:28-apache

RUN apt-get update && \
  apt-get -y upgrade && \
  apt-get install -y zip unzip curl &&\
  apt-get install -y build-essential software-properties-common locales \
                     ca-certificates numactl \
                     python3 python3-pip python3-dev python3-yaml uwsgi uwsgi-src \
                     byobu curl git htop man unzip vim wget &&  \
                     smbclient php-smbclient && \
  rm -rf /var/lib/apt/lists/*
RUN locale-gen en_US; locale-gen en_US.UTF-8

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install mysqli extension
RUN docker-php-ext-install mysqli

# Create the oarop user that runtime systems should run on by default.
# This user's UID must be set to 1000 to ensure alignment with the oarop
# user on the host system.  
#
RUN sed --in-place -e '/CREATE_MAIL_SPOOL/ s/=yes/=no/' /etc/default/useradd
ARG opuser=oarop
ARG opuid=1000
RUN groupadd --gid $opuid $opuser && \
    useradd -m --comment "OAR Operations" --shell /bin/bash \
            --gid $opuid --uid $opuid $opuser
ENV OAR_OP_USER $opuser
ENV APACHE_RUN_USER $opuser
ENV APACHE_RUN_GROUP $opuser
RUN mv /var/spool/cron/crontabs/www-data /var/spool/cron/crontabs/$opuser

# Create 'api' directory
RUN mkdir /app
COPY nextcloud-api.zip  /app/
RUN  unzip  /app/nextcloud-api.zip -d /app 

RUN  mkdir /var/www/api
RUN  cp -R /app/nextcloud-api/* /var/www/api/ 
# Copy API into the image within 'api' directory
# COPY ./nextcloud-api/ /var/www/api/

# Move to api directory to install dependencies
WORKDIR /var/www/api

# Install dependencies
RUN composer install
RUN apt-get update && apt-get install -y sudo mariadb-client

# Enable URL rewriting and headers
RUN a2enmod rewrite headers

# Disable SSL module (handled by nginx reverse proxy)
RUN a2dismod ssl

# Copy the Apache configuration
COPY 000-default.conf /etc/apache2/sites-available/000-default.conf
COPY ports.conf /etc/apache2/ports.conf
COPY initialize-nextcloud.sh /var/www/api/scripts/initialize-nextcloud.sh

# Add ServerName directive
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Adjust permissions
RUN chown -R $opuser:$opuser /var/www

# RUN sed -i -e '/set -eu/ s/eu/eux/' /entrypoint.sh
# COPY entrypoint.sh.dbg /entrypoint.sh
COPY isready.sh /docker-entrypoint-hooks.d/before-starting/

# Return to root directory
WORKDIR /var/www/html

# Adjust scripts permissions
RUN chmod +x /var/www/api/scripts/*

#Start Apache in the foreground (for the running container)
CMD ["apache2-foreground"]
