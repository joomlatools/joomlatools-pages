#!/bin/bash
echo "* Ensure mysql can be resarted easily"
chmod +x /workspace/joomlatools-pages/.gitpod/mysql-restart.sh

#echo "* Add joomla/console to the PATH in the current session"
export PATH=/home/gitpod/.composer/vendor/bin/:$PATH

#ERROR 1292 (22007): Incorrect datetime value: '0000-00-00 00:00:00' for column 'checked_out_time' at row 1
mysql -e "SET GLOBAL sql_mode = 'NO_ENGINE_SUBSTITUTION'; SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION';"

#echo "* Set up a new Joomla site"
joomla site:install preview --mysql-login=root:

#ensure that the componnent can be found, enable and correct state
mysql -uroot  sites_preview < /workspace/joomlatools-pages/.gitpod/sites_preview.sql

mkdir -p /var/www/preview/joomlatools-pages/pages/

cp /workspace/joomlatools-pages/.gitpod/hello.html.php /var/www/preview/joomlatools-pages/pages/hello.html.php


