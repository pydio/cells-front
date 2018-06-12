#!/bin/sh

# Use a given Build name or it's just a CI build
if [ "$#" -ne 1 ]; then
     BUILD_NAME="ci"
else
     BUILD_NAME="$1"
fi

echo "Building Cells Front $BUILD_NAME"

# Remove previous src folder
rm -rf src

# Clone or update Git Repo
if [ -d "cells-front" ]; then
        cd cells-front
        git pull
        cd ..
else
        git clone https://github.com/pydio/cells-front.git
fi
cp -R cells-front src

# Copy to src and read commit ID into a variable
cd src
COMMIT=`git rev-parse --short HEAD`
DATE=`date +%Y-%m-%d`
echo "Commit ID: $COMMIT - Date: $DATE"

sed -i "s/##REVISION##/$COMMIT/g" conf/*
sed -i "s/##VERSION_DATE##/$DATE/g" conf/*
sed -i "s/##VERSION_NUMBER##/$BUILD_NAME/g" conf/*

# Compile Sources
echo "Running Composer in core and plugins"
find . -maxdepth 3 -name composer.json -execdir composer install \;

echo "Running Grunt in core and plugins"
find . -maxdepth 3 -name Gruntfile.js -execdir bash -c "npm install && grunt" \;

# Remove some specific files
echo "Removing node_modules and .gitignore files"
rm -rf .git
find . -maxdepth 3 -name .gitignore -execdir bash -c "rm .gitignore" \;
find . -maxdepth 3 -name node_modules -execdir bash -c "rm -rf node_modules" \;

echo "Removing symlink in gui.ajax"
rm -f plugins/gui.ajax/res/themes/common/css/mui/less

echo "Removing greedy folders"
find . -type d -path "./plugins/*/res/js" -exec rm -r "{}" \;
find . -type d -path "./plugins/*/res/react" -exec rm -r "{}" \;
find . -name ".DS_Store" -exec rm "{}" \;
rm -rf plugins/res/build/ckeditor/samples
rm -rf core/vendor/dapphp/securimage/audio


echo "Removing dist folder"
rm -rf dist

# Create Archive Now
echo "Creating Archive Now"
cd ..
mkdir -p tar
mv src front
tar -czf "tar/cells-front-$COMMIT.tar.gz" "front"

echo "Succesfully created tar/cells-front-$COMMIT.tar.gz"