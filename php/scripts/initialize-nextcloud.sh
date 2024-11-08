#!/bin/bash
# set -x

# Change ownership and permissions of the config directory
chown -R $OAR_OP_USER:$OAR_OP_USER /var/www/html/config
chmod 700 /var/www/html/config

cd /var/www/html

echo " #### Updating Nextcloud configuration..."

# Wait for MariaDB to be fully up
countdown=15  # about a minute
opts=(-u $MARIADB_USER -p$MARIADB_PASSWORD -h $DB_HOST $MARIADB_DATABASE -e 'select 1;')

mariadb "${opts[@]}" > /dev/null 2>&1 || {
    echo "Waiting for MariaDB Server to come up..."
    until mariadb "${opts[@]}" > /dev/null 2>&1; do
        sleep 4
        let countdown-=1
        if [ $countdown -le 0 ]; then
            echo "Waiting for config server timing out!"
            exit 1
        fi
    done
}
echo MariaDB Server is ready\; installing Nextcloud

sudo -u $OAR_OP_USER php -dmemory_limit=512M occ maintenance:install \
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
sudo -u $OAR_OP_USER php -dmemory_limit=512M occ app:install user_saml
sudo -u $OAR_OP_USER php -dmemory_limit=512M occ app:enable user_saml

echo "SSO & SAML have been configured."

echo "Configuring for use behind the proxy server"
sudo -u $OAR_OP_USER php -dmemory_limit=512M \
                     occ config:system:set overwritewebroot --value=/fm/nc
sudo -u $OAR_OP_USER php -dmemory_limit=512M \
                     occ config:system:set overwriteprotocol --value=https
# Set overwrite.cli.url to ensure nextcloud use correct base url
sudo -u $OAR_OP_USER php -dmemory_limit=512M \
                     occ config:system:set overwrite.cli.url --value="https://localhost/fm/nc"
sudo -u $OAR_OP_USER php -dmemory_limit=512M \
     occ config:system:set overwritehost --value="localhost"
# Set trusted_proxies
sudo -u $OAR_OP_USER php -dmemory_limit=512M \
                     occ config:system:set trusted_proxies 0 --value="nginxreverseproxy"
sudo -u $OAR_OP_USER php -dmemory_limit=512M \
     occ config:system:set overwriteport --value="443"

IFS=:; set -o noglob; IDX=0
for DOMAIN in $NEXTCLOUD_TRUSTED_DOMAINS""; do
    sudo -u $OAR_OP_USER php -dmemory_limit=512M \
                         occ config:system:set trusted_domains $IDX --value=$DOMAIN
    IDX=$((IDX+1))
done

Nextcloud_adminuser_semaphore=/var/www/html/data/ADMIN_USER_CREATED
echo $? : `date` > $Nextcloud_adminuser_semaphore
echo "Nextcloud initialization complete"
