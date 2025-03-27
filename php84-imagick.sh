#!/bin/sh

docker run -it --rm --name php84-imagick -v "$PWD":/usr/src/myapp -w /usr/src/myapp php:8.4.3-imagick php $@
