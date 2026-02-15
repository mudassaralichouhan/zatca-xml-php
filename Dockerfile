FROM php:8.4-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    nginx \
    git curl zip unzip \
    libonig-dev libxml2-dev \
    libzip-dev libpq-dev libsqlite3-dev \
    libcurl4-openssl-dev \
    libssh2-1-dev \
    sqlite3 \
    supervisor libpng-dev \
    cron \
    libgmp-dev \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && docker-php-ext-install \
        pdo pdo_pgsql pdo_sqlite mbstring xml curl zip gmp \
    && pecl install ssh2-1.3.1 \
    && docker-php-ext-enable ssh2 \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

# Copy configs
COPY .docker/nginx/default.conf /etc/nginx/sites-available/default
COPY .docker/nginx/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
# COPY .docker/php/conf.d/disable-jit.ini /usr/local/etc/php/conf.d/disable-jit.ini

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
