#!/bin/sh

set -eux

today=$(date +'%Y-%m-%d')

docker build --file=docker/Dockerfile . \
  --push \
  --tag toyama4649/kirameki-php-cli:latest \
  --tag toyama4649/kirameki-php-cli:"${today}"
