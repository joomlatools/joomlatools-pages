#!/bin/bash
echo "* Ensure mysql can be resarted easily"
chmod +x /workspace/joomlatools-pages/.gitpod/mysql-restart.sh

#echo "* Add joomla/console to the PATH in the current session"
export PATH=/home/gitpod/.composer/vendor/bin/:$PATH

#ERROR 1292 (22007): Incorrect datetime value: '0000-00-00 00:00:00' for column 'checked_out_time' at row 1
mysql -e "SET GLOBAL sql_mode = 'NO_ENGINE_SUBSTITUTION'; SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION';"

echo "* Clone Joomlatools FW"
[ ! -d /workspace/joomlatools-framework ] && git clone -b master --depth 1 https://github.com/joomlatools/joomlatools-framework.git /workspace/joomlatools-framework
[ ! -d /workspace/joomlatools-framework-files ] && git clone -b master --depth 1 https://github.com/joomlatools/joomlatools-framework-files.git /workspace/joomlatools-framework-files
[ ! -d /workspace/joomlatools-framework-activities ] && git clone -b master --depth 1 https://github.com/joomlatools/joomlatools-framework-activities.git /workspace/joomlatools-framework-activities
[ ! -d /workspace/joomlatools-framework-scheduler ] && git clone -b master --depth 1 https://github.com/joomlatools/joomlatools-framework-scheduler.git /workspace/joomlatools-framework-scheduler
[ ! -d /workspace/joomlatools-framework-migrator ] && git clone -b master --depth 1 https://github.com/joomlatools/joomlatools-framework-migrator.git /workspace/joomlatools-framework-migrator
[ ! -d /workspace/joomlatools-framework-ckeditor ] && git clone -b master --depth 1 https://github.com/joomlatools/joomlatools-framework-ckeditor.git /workspace/joomlatools-framework-ckeditor
[ ! -d /workspace/joomlatools-framework-tags ] && git clone -b master --depth 1 https://github.com/joomlatools/joomlatools-framework-tags.git /workspace/joomlatools-framework-tags


#echo "* Set up a new Joomla site"
joomla site:install preview --mysql-login=root: --symlink=joomlatools-pages,joomlatools-framework --projects-dir="/workspace"

#ensure that the componnent can be found, enable and correct state
mysql -uroot  sites_preview < /workspace/joomlatools-pages/.gitpod/sites_preview.sql

mkdir -p /var/www/preview/joomlatools-pages/pages/

cp /workspace/joomlatools-pages/.gitpod/hello.html.php /var/www/preview/joomlatools-pages/pages/hello.html.php

#ensure that the componnent can be found, enable and correct state
mysql -uroot  sites_preview < /workspace/joomlatools-pages/.gitpod/sites_preview.sql

mkdir -p /var/www/preview/joomlatools-pages/pages/

cp /workspace/joomlatools-pages/.gitpod/hello.html.php /var/www/preview/joomlatools-pages/pages/hello.html.php


