#!/bin/bash

set -e

#cp /workspace/joomlatools-pages/.gitpod/joomlatools-pages.theia-workspace /home/gitpod/.theia/recentworkspace.json

echo "* (Re)installing Joomlatools Pages into the preview site"
joomla extension:symlink preview joomlatools-framework joomlatools-pages --projects-dir="/home/gitpod/Projects"

echo "* Updating database"
mysql -uroot sites_preview < /workspace/joomlatools-pages/.gitpod/sites_preview.sql

echo "* Symlinking Joomlatools-pages content to editor workspace"
ln -fs /var/www/preview/joomlatools-pages/* /workspace/joomlatools-pages/content/

echo "* Launch preview pane"
gp preview $(gp url 8080)