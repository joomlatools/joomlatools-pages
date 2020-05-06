#!/bin/bash

set -e

export PATH=/home/gitpod/.composer/vendor/bin/:$PATH

joomla plugin:install joomlatools/console-joomlatools:dev-master

mkdir ~/Projects

echo "* Clone Joomlatools FW"
[ ! -d ~/Projects/joomlatools-framework ] && git clone -b master --depth 1 https://github.com/joomlatools/joomlatools-framework.git ~/Projects/joomlatools-framework
[ ! -d ~/Projects/joomlatools-framework-files ] && git clone -b master --depth 1 https://github.com/joomlatools/joomlatools-framework-files.git ~/Projects/joomlatools-framework-files
[ ! -d ~/Projects/joomlatools-framework-activities ] && git clone -b master --depth 1 https://github.com/joomlatools/joomlatools-framework-activities.git ~/Projects/joomlatools-framework-activities
[ ! -d ~/Projects/joomlatools-framework-scheduler ] && git clone -b master --depth 1 https://github.com/joomlatools/joomlatools-framework-scheduler.git ~/Projects/joomlatools-framework-scheduler
[ ! -d ~/Projects/joomlatools-framework-migrator ] && git clone -b master --depth 1 https://github.com/joomlatools/joomlatools-framework-migrator.git ~/Projects/joomlatools-framework-migrator
[ ! -d ~/Projects/joomlatools-framework-ckeditor ] && git clone -b master --depth 1 https://github.com/joomlatools/joomlatools-framework-ckeditor.git ~/Projects/joomlatools-framework-ckeditor
[ ! -d ~/Projects/joomlatools-framework-tags ] && git clone -b master --depth 1 https://github.com/joomlatools/joomlatools-framework-tags.git ~/Projects/joomlatools-framework-tags

ln -s /workspace/joomlatools-pages ~/Projects/

echo "* Download a new Joomla site"
joomla site:download preview
