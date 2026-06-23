FROM php:8.5.7-cli-alpine3.24

# Some imagick formats have been separated into own packages:
# https://pkgs.alpinelinux.org/packages?name=imagemagick-*&branch=v3.24&repo=&arch=aarch64&origin=&flagged=&maintainer=

# Install dependencies and development tools
RUN apk add --update --no-cache \
    git \
    imagemagick \
    imagemagick-jpeg \
    imagemagick-svg \
    imagemagick-tiff \
    imagemagick-webp \
    imagemagick-heic \
    imagemagick-jxl \
    imagemagick-dev \
    zlib-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libwebp-dev \
    gcc \
    g++ \
    make \
    autoconf \
    pkgconfig

# Clone Imagick repository and compile the extension
RUN git clone https://github.com/Imagick/imagick.git --depth 1 /tmp/imagick && \
    cd /tmp/imagick && \
    phpize && \
    ./configure && \
    make && \
    make install

RUN docker-php-ext-install calendar && docker-php-ext-configure calendar
RUN docker-php-ext-enable imagick
## No need to add pdo_sqlite, it is already installed
#RUN docker-php-ext-install pdo pdo_sqlite
# Install pdo_mysql
RUN docker-php-ext-install pdo_mysql

# GD requires zlib, linpng, libjpeg, freetype2, libwebp 
RUN set -eux; \
    docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp; \
    docker-php-ext-install exif gd

# Clean up to reduce image size
RUN apk del git gcc g++ make autoconf pkgconfig imagemagick-dev zlib-dev libpng-dev libjpeg-turbo-dev freetype-dev libwebp-dev && \
    rm -rf /var/cache/apk/* /tmp/imagick 


