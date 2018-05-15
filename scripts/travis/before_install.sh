#!/bin/bash

set -e

echo "Install Phing"
pear channel-discover pear.phing.info
pear install phing/phing
phpenv rehash

echo "Download gdrive"
wget -O ~/gdrive "https://docs.google.com/uc?id=0B3X9GlR6EmbnQ0FtZmJJUXEyRTA&export=download"
chmod +x ~/gdrive; sync;

echo "Set environment variables"
source $TRAVIS_BUILD_DIR/scripts/travis/env.sh

echo "Disable XDebug"
phpenv config-rm xdebug.ini

echo "Add Pear to include path"
echo "include_path = \""$(php -r 'echo get_include_path();')":"$(pear config-get php_dir)"\"" >> ~/.phpenv/versions/$(phpenv version-name)/etc/php.ini
