#!/bin/bash

## This is all going to be have to run on dockerfile or gitpod.yml so when the workspace is rendered it is all good to go
## problem being first run, the preview bar opens to the side with apache permission errors. This is because the site isn't installed
## it is only installed  after the gitpod has been initialised

# Create the document root
mkdir "${GITPOD_REPO_ROOT}/preview"

# Install Composer dependencies
composer install  --no-interaction

# Install joomla/console
composer global require joomlatools/console --no-interaction

#ERROR 1292 (22007): Incorrect datetime value: '0000-00-00 00:00:00' for column 'checked_out_time' at row 1
mysql -uroot  sites_preview < /workspace/joomlatools-pages/.gitpod/set_globals.sql

# Set up a new Joomla site
/home/gitpod/.composer/vendor/bin/joomla site:download preview --www="${GITPOD_REPO_ROOT}"
/home/gitpod/.composer/vendor/bin/joomla site:install preview --www="${GITPOD_REPO_ROOT}" --mysql-login=root:

# Symlink Joomlatools FW and Pages into the site
composer require joomlatools/framework:3.* --working-dir="${GITPOD_REPO_ROOT}/preview" --ignore-platform-reqs

/home/gitpod/.composer/vendor/bin/joomla extension:symlink preview joomlatools-pages --www="${GITPOD_REPO_ROOT}"  --projects-dir="/workspace"

#extension install fubar... maybe wrong date time problem
/home/gitpod/.composer/vendor/bin/joomla extension:install preview joomlatools-pages --www="${GITPOD_REPO_ROOT}"

mkdir -p /workspace/joomlatools-pages/preview/joomlatools-pages/pages/

cp /workspace/joomlatools-pages/.gitpod/hello.html.php /workspace/joomlatools-pages/preview/joomlatools-pages/pages/hello.html.php


