#!/bin/bash

## This is all going to be have to run on dockerfile or gitpod.yml so when the workspace is rendered it is all good to go
## problem being first run, the preview bar opens to the side with apache permission errors. This is because the site isn't installed
## it is only installed  after the gitpod has been initialised
## That and it  takes a ridiculous amount of time to wait for the site

echo "restart mysql after docker config change"
chmod +x /workspace/joomlatools-pages/.gitpod/mysql-restart.sh

echo "* Create the document root"
mkdir -p "/var/www/preview"

echo "* Install joomla/console"
composer global require joomlatools/console --no-interaction

echo "* Add joomla/console to the PATH in the current session"
export PATH=/home/gitpod/.composer/vendor/bin/:$PATH

echo "* Make sure PATH is always updated"
echo "* export PATH=/home/gitpod/.composer/vendor/bin/:$PATH" >> ~/.bashrc

echo "* Install the joomlatools/console-joomlatools helper plugin"
joomla plugin:install joomlatools/console-joomlatools:dev-master

echo "* Clone Joomlatools FW"
[ ! -d /workspacejoomlatools-framework-files ] && git clone -b master --depth 1 https://github.com/joomlatools/joomlatools-framework-files.git /workspacejoomlatools-framework-files
[ ! -d /workspacejoomlatools-framework-activities ] && git clone -b master --depth 1 https://github.com/joomlatools/joomlatools-framework-activities.git /workspacejoomlatools-framework-activities
[ ! -d /workspacejoomlatools-framework-scheduler ] && git clone -b master --depth 1 https://github.com/joomlatools/joomlatools-framework-scheduler.git /workspacejoomlatools-framework-scheduler
[ ! -d /workspacejoomlatools-framework-migrator ] && git clone -b master --depth 1 https://github.com/joomlatools/joomlatools-framework-migrator.git /workspacejoomlatools-framework-migrator
[ ! -d /workspacejoomlatools-framework-ckeditor ] && git clone -b master --depth 1 https://github.com/joomlatools/joomlatools-framework-ckeditor.git /workspacejoomlatools-framework-ckeditor
[ ! -d /workspacejoomlatools-framework-tags ] && git clone -b master --depth 1 https://github.com/joomlatools/joomlatools-framework-tags.git /workspacejoomlatools-framework-tags

#ERROR 1292 (22007): Incorrect datetime value: '0000-00-00 00:00:00' for column 'checked_out_time' at row 1
mysql -e "SET GLOBAL sql_mode = 'NO_ENGINE_SUBSTITUTION'; SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION';"

echo "* Set up a new Joomla site"
joomla site:download preview
joomla site:install preview --mysql-login=root: --symlink=joomlatools-pages,joomlatools-framework --projects-dir="/workspace"

#ensure that the componnent can be found, enable and correct state
mysql -uroot  sites_preview < /workspace/joomlatools-pages/.gitpod/sites_preview.sql

mkdir -p /var/www/preview/joomlatools-pages/pages/

cp /workspace/joomlatools-pages/.gitpod/hello.html.php /var/www/preview/joomlatools-pages/pages/hello.html.php


