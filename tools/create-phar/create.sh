#!/bin/bash

# Install Box2 and put it in the path
# https://github.com/box-project/box2

# Fix default open files limit
ulimit -n 10000

# Build phar file
box build

mv gtfs-merger.phar gtfs-merger
chmod 744 gtfs-merger
