FROM alpine:3.17 AS base

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN set -xe \
    && apk add --update --no-cache \
    ca-certificates \
    curl \
    php81 \
    php81-cli \
    php81-bcmath \
    php81-common \
    php81-curl \
    php81-dom \
    php81-exif \
    php81-fileinfo \
    php81-fpm \
    php81-gd \
    php81-iconv \
    php81-intl \
    php81-json \
    php81-openssl \
    php81-opcache \
    php81-mbstring \
    php81-pdo_mysql \
    php81-phar \
    php81-session \
    php81-tokenizer \
    php81-xml \
    php81-zip

COPY docker/00_docker.ini /etc/php81/conf.d/
COPY docker/pool.conf /etc/php81/php-fpm.d/www.conf

WORKDIR /srv

# Application
FROM base AS app

EXPOSE 9000
CMD [ "/usr/sbin/php-fpm81", "-F" ]
