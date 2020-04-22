#!/bin/bash

# Create the document root
mkdir "${GITPOD_REPO_ROOT}/preview"

# Install Composer dependencies
composer install  --no-interaction

# Install joomla/console
composer global require joomlatools/console --no-interaction

# Set up a new Joomla site
/home/gitpod/.composer/vendor/bin/joomla site:download preview --www="${GITPOD_REPO_ROOT}"
/home/gitpod/.composer/vendor/bin/joomla site:install preview --www="${GITPOD_REPO_ROOT}" --mysql-login=root:

# Symlink Joomlatools FW and Pages into the site

# Create default first page, see https://github.com/joomlatools/joomlatools-pages/wiki/Quick-Start