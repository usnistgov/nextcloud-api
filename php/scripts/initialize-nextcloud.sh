#!/bin/bash

# Change ownership and permissions of the config directory
chown -R www-data:www-data /var/www/html/config
chmod 700 /var/www/html/config

cd /var/www/html

echo "Installing Nextcloud..."

if [ ! -f "config/config.php" ]; then
    sudo -u www-data php -dmemory_limit=512M occ maintenance:install \
    --database "mysql" \
    --database-host "${DB_HOST}:${DB_PORT}" \
    --database-name "${MARIADB_DATABASE}" \
    --database-user "${MARIADB_USER}" \
    --database-pass "${MARIADB_PASSWORD}" \
    --admin-user "${NEXTCLOUD_ADMIN_USER}" \
    --admin-pass "${NEXTCLOUD_ADMIN_PASSWORD}"
fi

echo "Downloading and enabling SSO & SAML app..."

# Enable the SSO & SAML app
sudo -u www-data php -dmemory_limit=512M occ app:install user_saml
sudo -u www-data php -dmemory_limit=512M occ app:enable user_saml

echo "SSO & SAML have been configured."
