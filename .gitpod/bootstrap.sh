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

#extension install fubar... maybe wrong date time problem
#/home/gitpod/.composer/vendor/bin/joomla extension:install preview joomlatools-pages --www="${GITPOD_REPO_ROOT}"

mysql -uroot  sites_preview < /workspace/joomlatools-pages/.gitpod/sites_preview.sql

# Create default first page, see https://github.com/joomlatools/joomlatools-pages/wiki/Quick-Start

mkdir -p /workspace/joomlatools-pages/preview/joomlatools-pages/pages/

touch /workspace/joomlatools-pages/preview/joomlatools-pages/pages/hello.html.php

echo "<h1>Hello sexy, where have you been?</h1>" > /workspace/joomlatools-pages/preview/joomlatools-pages/pages/hello.html.php


## This is all going to be have to run on dockerfile or gitpod.yml so when the workspace is rendered it is all good to go

# problem with DATETIME insert
#ERROR 1292 (22007): Incorrect datetime value: '0000-00-00 00:00:00' for column 'checked_out_time' at row 1
#SELECT @@GLOBAL.sql_mode global, @@SESSION.sql_mode session;
#SET sql_mode = '';
#SET GLOBAL sql_mode = '';

#INSERT INTO `j_extensions` (`extension_id`, `package_id`, `name`, `type`, `element`, `folder`, `client_id`, `enabled`, `access`, `protected`, `manifest_cache`, `params`, `custom_data`, `system_data`, `checked_out`, `checked_out_time`, `ordering`, `state`) VALUES ('10003', '0', 'com_pages', 'component', 'com_pages', '', '1', '1', '0', '0', '{\"name\":\"com_pages\",\"type\":\"component\",\"creationDate\":\"January 2020\",\"author\":\"Joomlatools\",\"copyright\":\"Copyright (C) 2018 Timble CVBA (http:\\/\\/www.timble.net)\",\"authorEmail\":\"support@joomlatools.com\",\"authorUrl\":\"www.joomlatools.com\",\"version\":\"0.17.0\",\"description\":\"COM_PAGES_XML_DESCRIPTION\",\"group\":\"\",\"filename\":\"pages\"}', '{}', '', '', '0', '0000-00-00 00:00:00',
#'0', '0');

#UPDATE `j_extensions` SET `enabled` = 1 WHERE `extension_id`  ='10002';