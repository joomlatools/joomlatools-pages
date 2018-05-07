
export CURRENT_REPO=$(echo $TRAVIS_REPO_SLUG | awk -F'/' '{print $2}' | awk -F'-' '{print $2}')
export CURRENT_BRANCH=$(echo $TRAVIS_BRANCH | sed 's/\//-/g')
export PKG_SUFFIX=$(if [[ $CURRENT_BRANCH =~ ^[0-9] ]]; then echo $CURRENT_BRANCH; else echo _$CURRENT_BRANCH; fi;)
export PKG_NAME=com_$CURRENT_REPO$PKG_SUFFIX.zip
