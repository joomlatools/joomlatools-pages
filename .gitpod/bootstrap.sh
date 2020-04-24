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
composer require joomlatools/framework:3.* --working-dir="${GITPOD_REPO_ROOT}/preview" --ignore-platform-reqs

/home/gitpod/.composer/vendor/bin/joomla extension:symlink preview joomlatools-pages --www="${GITPOD_REPO_ROOT}"  --projects-dir="/workspace"
/home/gitpod/.composer/vendor/bin/joomla extension:install preview joomlatools-pages --www="${GITPOD_REPO_ROOT}"

# Create default first page, see https://github.com/joomlatools/joomlatools-pages/wiki/Quick-Start

mkdir -p /workspace/joomlatools-pages/preview/joomlatools-pages/pages/

touch /workspace/joomlatools-pages/preview/joomlatools-pages/pages/hello.html.php

echo "<h1>Hello sexy, where have you been?</h1>" > /workspace/joomlatools-pages/preview/joomlatools-pages/pages/hello.html.php