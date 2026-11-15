FROM php:8.2-apache

# Set environment variables
ENV DEBIAN_FRONTEND=noninteractive \
    PHP_POST_MAX_SIZE=25M \
    PHP_UPLOAD_MAX_FILESIZE=25M \
    PHP_MAX_FILE_UPLOADS=10 \
    PHP_MEMORY_LIMIT=256M \
    PHP_MAX_EXECUTION_TIME=60 \
    APACHE_LOG_DIR=/var/log/apache2 \
    APACHE_RUN_DIR=/var/run \
    APACHE_PID_FILE=/var/run/apache2/apache2.pid

# ---------------------------------------------------------------------------
# System packages
#   libpq-dev        = PostgreSQL development files (pg_config + libpq)
#                    -> REQUIRED to compile pdo_pgsql / pgsql
#   netcat-openbsd   = provides `nc`, used by docker-entrypoint.sh
# ---------------------------------------------------------------------------
RUN apt-get update && apt-get install -y --no-install-recommends \
        libpq-dev \
        libzip-dev \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        libonig-dev \
        libcurl4-openssl-dev \
        netcat-openbsd \
        unzip \
        git \
    && rm -rf /var/lib/apt/lists/*

# ---------------------------------------------------------------------------
# PHP extensions
#   pdo_pgsql / pgsql -> PostgreSQL (Supabase) driver
#   curl, openssl, xml, json, tokenizer, fileinfo, pdo are already compiled
#   into the official php:8.2-apache image, so they are NOT built here.
# ---------------------------------------------------------------------------
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo \
        pdo_pgsql \
        pgsql \
        zip \
        gd \
        mbstring

# Enable Apache modules
RUN a2enmod rewrite \
    && a2enmod deflate \
    && a2enmod expires

# Set working directory
WORKDIR /var/www/html

# Copy application files + entrypoint
COPY . /var/www/html/
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh

# The app reads its secret keys at runtime; never bake .env into the image.
RUN rm -f /var/www/html/.env \
    && chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && find /var/www/html -type f -exec chmod 644 {} \; \
    && chmod +x /usr/local/bin/docker-entrypoint.sh

# Expose port
EXPOSE 80

# Set entrypoint
ENTRYPOINT ["docker-entrypoint.sh"]
