# Stage 1: Build frontend assets (Vite + Tailwind CSS v4)
FROM node:20-alpine AS node-builder
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

# Stage 2: Install Composer production dependencies
FROM composer:2.8 AS php-builder
WORKDIR /app
COPY composer*.json ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-plugins \
    --no-scripts \
    --prefer-dist

# Stage 3: Production environment (PHP 8.4 + Nginx + Supervisor)
FROM php:8.4-fpm-alpine
WORKDIR /var/www/html

# Install system dependencies (including packages required by DomPDF and Maatwebsite Excel)
RUN apk add --no-cache \
    nginx \
    supervisor \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    libxml2-dev \
    zip \
    unzip \
    bash \
    mysql-client

# Configure and install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        bcmath \
        zip \
        opcache \
        gd \
        intl

# Configure PHP production settings
RUN { \
        echo 'memory_limit = 512M'; \
        echo 'upload_max_filesize = 64M'; \
        echo 'post_max_size = 64M'; \
        echo 'max_execution_time = 300'; \
    } > /usr/local/etc/php/conf.d/docker-php-custom.ini

# Copy Nginx and Supervisor configs
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copy application files
COPY --chown=www-data:www-data . .

# Copy built assets and vendor dependencies from previous stages
COPY --from=node-builder --chown=www-data:www-data /app/public/build ./public/build
COPY --from=php-builder --chown=www-data:www-data /app/vendor ./vendor

# Optimize Composer Autoload
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer
RUN composer dump-autoload --no-dev --classmap-authoritative

# Ensure storage and bootstrap/cache directories are writable
RUN mkdir -p storage/framework/{sessions,views,caches} \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Copy entrypoint script
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
