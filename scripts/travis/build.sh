#!/bin/bash

set -e

echo "Set environment variables"
source $TRAVIS_BUILD_DIR/scripts/travis/env.sh

echo $PKG_NAME

cd $TRAVIS_BUILD_DIR/scripts/build && ./pack.sh
mv com_$(echo $CURRENT_REPO)_installer.zip $PKG_NAME
~/gdrive list --refresh-token $GDRIVE_REFRESH_TOKEN --query "'$GDRIVE_DIR' in parents and mimeType != 'application/vnd.google-apps.folder' and name = '$PKG_NAME'" --no-header | while read line ; do ID=$(echo $line | cut -d ' ' -f1 | xargs); ~/gdrive delete --refresh-token $GDRIVE_REFRESH_TOKEN $ID; done
~/gdrive upload --refresh-token $GDRIVE_REFRESH_TOKEN --parent $GDRIVE_DIR $PKG_NAME