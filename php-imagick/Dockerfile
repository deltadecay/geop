FROM php:8.4.3-cli-alpine3.20

# Some imagick formats have been separated into own packages:
# https://pkgs.alpinelinux.org/packages?name=imagemagick-*&branch=v3.20&repo=&arch=aarch64&origin=&flagged=&maintainer=

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

RUN docker-php-ext-enable imagick
## No need to add pdo_sqlite, it is already installed
#RUN docker-php-ext-install pdo pdo_sqlite
# Install pdo_mysql
RUN docker-php-ext-install pdo_mysql

# Clean up to reduce image size
RUN apk del git gcc g++ make autoconf pkgconfig imagemagick-dev && \
    rm -rf /var/cache/apk/* /tmp/imagick

    