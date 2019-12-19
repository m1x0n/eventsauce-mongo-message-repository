#!/usr/bin/env bash

# This script might be used for running test and local environment

docker-compose up -d

if ! nc -z -w30 localhost 27017
  then
    echo "Failed to connect to MongoDB on port 27017 within 30 seconds."
fi
