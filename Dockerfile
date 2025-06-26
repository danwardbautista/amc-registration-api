FROM composer:2.8 AS builder

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction
COPY . .
RUN composer dump-autoload --optimize

FROM php:8.2-fpm-alpine

RUN apk add --no-cache \
      libpng-dev \
      libjpeg-turbo-dev \
      freetype-dev \
      oniguruma-dev \
      libxml2-dev \
      libzip-dev \
      curl-dev \
      icu-dev \
      icu-data-full \
      zip \
      unzip \
      git \
      autoconf \
      g++ \
      make \
    && docker-php-ext-configure gd --with-jpeg --with-freetype \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j$(nproc) \
         pdo_mysql \
         mbstring \
         exif \
         pcntl \
         bcmath \
         gd \
         zip \
         curl \
         intl \
         opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del autoconf g++ make \
    && rm -rf /var/cache/apk/* /tmp/*

RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
    && echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
    && echo "opcache.max_accelerated_files=20000" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
    && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini

WORKDIR /var/www/html

COPY --from=builder /app /var/www/html

RUN chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]