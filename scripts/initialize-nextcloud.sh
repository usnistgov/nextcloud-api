#!/bin/bash

# Change ownership and permissions of the config directory
chown -R www-data:www-data /var/www/html/config
chmod 700 /var/www/html/config

cd /var/www/html

echo " #### Updating Nextcloud configuration..."

# echo ${NEXTCLOUD_ADMIN_USER}
# echo ${NEXTCLOUD_ADMIN_PASSWORD}
# echo ${DB_HOST}:${DB_PORT}
# echo ${MARIADB_DATABASE}
# echo ${MARIADB_USER}
# echo ${MARIADB_PASSWORD}

# if [ ! -f "config/config.php" ]; then

sudo -u www-data php -dmemory_limit=512M occ maintenance:install \
--database "mysql" \
--database-host "${DB_HOST}:${DB_PORT}" \
--database-name "${MARIADB_DATABASE}" \
--database-user "${MARIADB_USER}" \
--database-pass "${MARIADB_PASSWORD}" \
--admin-user "${NEXTCLOUD_ADMIN_USER}" \
--admin-pass "${NEXTCLOUD_ADMIN_PASSWORD}"
# fi

echo "Downloading and enabling SSO & SAML app..."

# Enable the SSO & SAML app
sudo -u www-data php -dmemory_limit=512M occ app:install user_saml
sudo -u www-data php -dmemory_limit=512M occ app:enable user_saml

echo "SSO & SAML have been configured."

echo "Configuring for use behind the proxy server"
sudo -u www-data php -dmemory_limit=512M \
                 occ config:system:set overwritewebroot --value=/fm/nc
sudo -u www-data php -dmemory_limit=512M \
                 occ config:system:set overwriteprotocol --value=https

IFS=:; set -o noglob; IDX=0
for DOMAIN in $NEXTCLOUD_TRUSTED_DOMAINS""; do
    sudo -u www-data php -dmemory_limit=512M \
                     occ config:system:set trusted_domains $IDX --value=$DOMAIN
    IDX=$((IDX+1))
done

Nextcloud_adminuser_semaphore=/var/www/html/ADMIN_USER_CREATED
echo $? : `date` > $Nextcloud_adminuser_semaphore
echo "Nextcloud initialization complete"
