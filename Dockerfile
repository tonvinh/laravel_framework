FROM serversideup/php:8.5-fpm

USER root

# Install missing extensions: bcmath, gd, intl
RUN apt-get update -qq \
 && apt-get install -y --no-install-recommends \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libicu-dev \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j$(nproc) bcmath gd intl \
 && apt-get clean \
 && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# PHP config
COPY docker/php/php.ini /usr/local/etc/php/conf.d/app.ini

USER www-data

EXPOSE 9000

CMD ["php-fpm"]
