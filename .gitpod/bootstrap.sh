#!/bin/bash

## This is all going to be have to run on dockerfile or gitpod.yml so when the workspace is rendered it is all good to go
## problem being first run, the preview bar opens to the side with apache permission errors. This is because the site isn't installed
## it is only installed  after the gitpod has been initialised
## That and it  takes a ridiculous amount of time to wait for the site

# Create the document root
mkdir "/var/www/preview"

# Install Composer dependencies
composer install  --no-interaction

# Install joomla/console
composer global require joomlatools/console --no-interaction

# Add joomla/console to the PATH in the current session
export PATH=/home/gitpod/.composer/vendor/bin/:$PATH

# Make sure PATH is always updated
echo "export PATH=/home/gitpod/.composer/vendor/bin/:$PATH" >> ~/.bashrc

# Install the joomlatools/console-joomlatools helper plugin
joomla plugin:install joomlatools/console-joomlatools:dev-master

# Clone Joomlatools FW
git clone -b master -–depth 1 https://github.com/joomlatools/joomlatools-framework.git /workspace/joomlatools-framework

#ERROR 1292 (22007): Incorrect datetime value: '0000-00-00 00:00:00' for column 'checked_out_time' at row 1
mysql -e "SET GLOBAL sql_mode = 'NO_ENGINE_SUBSTITUTION'; SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION';"

# Set up a new Joomla site
joomla site:download preview
joomla site:install preview --mysql-login=root: --symlink=joomlatools-pages --projects-dir="/workspace"

#ensure that the componnent can be found, enable and correct state
mysql -uroot  sites_preview < /workspace/joomlatools-pages/.gitpod/sites_preview.sql

mkdir -p /var/www/preview/joomlatools-pages/pages/

cp /workspace/joomlatools-pages/.gitpod/hello.html.php /var/www/preview/joomlatools-pages/pages/hello.html.php


