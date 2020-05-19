
# Turn off command traces while dealing with the private key
set +x

# Get the encrypted private key from the repo settings
echo $TIMBLEDEPLOY_PRIVATE_KEY_BASE64_ENCODED | base64 --decode > ~/.ssh/id_rsa
chmod 600 ~/.ssh/id_rsa

# anyone can read the build log, so it MUST NOT contain any sensitive data
set -x

# add github's public key
echo "|1|qPmmP7LVZ7Qbpk7AylmkfR0FApQ=|WUy1WS3F4qcr3R5Sc728778goPw= ssh-rsa AAAAB3NzaC1yc2EAAAABIwAAAQEAq2A7hRGmdnm9tUDbO9IDSwBK6TbQa+PXYPCPy6rbTrTtw7PHkccKrpp0yVhp5HdEIcKr6pLlVDBfOLX9QUsyCOV0wzfjIJNlGEYsdlLJizHhbn2mUjvSAHQqZETYP81eFzLQNnPHt4EVVUh7VfDESU84KezmD5QlWpXLmvU31/yMf+Se8xhHTvKSCZIFImWwoG6mbUoWf9nzpIoaSjB+weqqUUmpaaasXVal72J+UX2B+2RPW3RcT0eOzQgqlJL3RKrTJvdsjE3JEAvGq3lGHSZXy28G3skua2SmVi/w4yCE6gbODqnTWlg7+wC604ydGXA8VJiS5ap43JXiUFFAaQ==" >> ~/.ssh/known_hosts


# Create variables
build_dir=$(pwd)
payload_dir=$build_dir/installer/payload

framework_location=remote
framework_branch=v3.4.2

component_include=false

# Clean up
rm -rf installer
rm -f com_pages_installer.zip
rm -f com_pages.zip
rm -f joomlatools-framework.zip

# build pages
phing -verbose

# build framework
[ ! -d ../../../joomlatools-framework ] && git clone -b $framework_branch https://github.com/joomlatools/joomlatools-framework.git ../../../joomlatools-framework
cd ../../../joomlatools-framework && phing -verbose -Dframework.location=$framework_location -Dframework.branch=$framework_branch -Dcomponent.include=$component_include && mv joomlatools-framework.zip $build_dir/joomlatools-framework.zip
cd $build_dir

# clone installer
git clone --depth 1 --branch master git@github.com:joomlatools/joomlatools-extension-installer.git $build_dir/installer
rm -rf $build_dir/installer/.git
rm -f $build_dir/installer/.gitignore
rm -f $build_dir/installer/README.md
rm -f $build_dir/installer/LICENSE

# create payload
mkdir $payload_dir
cp manifest.json $payload_dir/manifest.json
unzip -q com_pages.zip -d $payload_dir/pages
unzip -q joomlatools-framework.zip -d $payload_dir/framework

# zip it all up
zip -q -r com_pages_installer.zip installer

# clean leftovers
rm -rf installer
rm -f com_pages.zip
rm -f joomlatools-framework.zip