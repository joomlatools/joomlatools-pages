#!/bin/bash

set -e

#cp /workspace/joomlatools-pages/.gitpod/joomlatools-pages.theia-workspace /home/gitpod/.theia/recentworkspace.json

echo "* Ensure mysql can be restarted easily"
chmod +x /workspace/joomlatools-pages/.gitpod/mysql-restart.sh

echo "* (Re)installing Joomla preview site"
joomla site:install preview --overwrite --drop --mysql-login=root: --symlink=joomlatools-pages,joomlatools-framework --projects-dir="/home/gitpod/Projects"

echo "* Updating database"
mysql -uroot  sites_preview < /workspace/joomlatools-pages/.gitpod/sites_preview.sql

echo "* Adding Joomlatools Pages content"
mkdir -p /var/www/preview/joomlatools-pages/pages/

cp /workspace/joomlatools-pages/.gitpod/config.php /var/www/preview/joomlatools-pages/pages/config.php
cp /workspace/joomlatools-pages/.gitpod/index.html.php /var/www/preview/joomlatools-pages/pages/index.html.php

echo "* Symlinking Joomlatools-pages content to editor workspace"
ln -fs /var/www/preview/joomlatools-pages/* /workspace/joomlatools-pages/content/

echo "* Launch preview pane"
gp preview $(gp url 8080)