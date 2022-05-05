#!/bin/bash
set -ex;

while ! mysqladmin ping -h"${WORDPRESS_DB_HOST:-mysql}" --silent; do
    sleep 1
done

wp core install --path=/var/www/html --url=http://localhost:8888 --title='WordPress Devcontainer' --admin_user=admin --admin_password=password --admin_email=wordpress@example.com --skip-email;
# wp plugin activate --path=/var/www/html $(wp plugin list --field=name | grep -v -e akismet -e hello)