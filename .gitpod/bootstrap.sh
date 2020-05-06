#!/bin/bash

set -e

#cp /workspace/joomlatools-pages/.gitpod/joomlatools-pages.theia-workspace /home/gitpod/.theia/recentworkspace.json

echo "* Installing Joomla database"
joomla database:install preview --drop --mysql-login=root:
mysql -uroot sites_preview < /workspace/joomlatools-pages/.gitpod/sites_preview.sql

echo "* (Re)installing Joomlatools Pages into the preview site"
joomla extension:symlink preview joomlatools-framework joomlatools-pages --projects-dir="/home/gitpod/Projects"
joomla extension:install preview all

echo "* Setting up Joomlatools-pages content"
ln -fs /var/www/preview/joomlatools-pages/pages/* /workspace/joomlatools-pages/content/

echo "* Launch preview pane"
gp preview $(gp url 8080)