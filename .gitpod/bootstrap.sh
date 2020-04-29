#!/bin/bash

## This is all going to be have to run on dockerfile or gitpod.yml so when the workspace is rendered it is all good to go
## problem being first run, the preview bar opens to the side with apache permission errors. This is because the site isn't installed
## it is only installed  after the gitpod has been initialised
## That and it  takes a ridiculous amount of time to wait for the site

# Create the document root
mkdir "${GITPOD_REPO_ROOT}/preview"

# Install Composer dependencies
composer install  --no-interaction

# Install joomla/console
composer global require joomlatools/console --no-interaction

#ERROR 1292 (22007): Incorrect datetime value: '0000-00-00 00:00:00' for column 'checked_out_time' at row 1
mysql -e "SET GLOBAL sql_mode = 'NO_ENGINE_SUBSTITUTION'; SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION';"

# Set up a new Joomla site
/home/gitpod/.composer/vendor/bin/joomla site:download preview --www="${GITPOD_REPO_ROOT}"
/home/gitpod/.composer/vendor/bin/joomla site:install preview --www="${GITPOD_REPO_ROOT}" --mysql-login=root:

# Symlink Joomlatools FW and Pages into the site
composer require joomlatools/framework:3.* --working-dir="${GITPOD_REPO_ROOT}/preview" --ignore-platform-reqs

/home/gitpod/.composer/vendor/bin/joomla extension:symlink preview joomlatools-pages --www="${GITPOD_REPO_ROOT}"  --projects-dir="/workspace"

#extension install fubar... most probably command related I remember:
#joomla site:create testing --symlink=joomlatools-pages,joomlatools-framework
#works out of the box where as extension:install doesn't
/home/gitpod/.composer/vendor/bin/joomla extension:install preview joomlatools-pages --www="${GITPOD_REPO_ROOT}"

#ensure that the componnent can be found, enable and correct state
mysql -uroot  sites_preview < /workspace/joomlatools-pages/.gitpod/sites_preview.sql


mkdir -p /workspace/joomlatools-pages/preview/joomlatools-pages/pages/

cp /workspace/joomlatools-pages/.gitpod/hello.html.php /workspace/joomlatools-pages/preview/joomlatools-pages/pages/hello.html.php


