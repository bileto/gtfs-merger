#!/bin/bash

# Install Box2 and put it in the path
# https://github.com/box-project/box2

# Fix default open files limit
ulimit -n 10000

# Set current hash as build name into config
BUILD=`git rev-parse HEAD`
sed -i.bak "s/build: null/build: \"$BUILD\"/g" ../../src/config.neon
rm ../../src/config.neon.bak

# Build phar file
php -c ./../../custom-php.ini -f ./../../vendor/bin/box build -v \
&& mv gtfs-merger.phar gtfs-merger \
&& chmod 744 gtfs-merger

# Revert changes in config
git checkout -- ../../src/config.neon
