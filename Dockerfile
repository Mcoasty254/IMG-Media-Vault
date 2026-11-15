
FROM php:8.2-apache

ENV DEBIAN_FRONTEND=noninteractive \
    PHP_POST_MAX_SIZE=25M \
    PHP_UPLOAD_MAX_FILESIZE=25M \
    PHP_MAX_FILE_UPLOADS=10 \
    PHP_MEMORY_LIMIT=256M \
    PHP_MAX_EXECUTION_TIME=60 \
    APACHE_LOG_DIR=/var/log/apache2 \
    APACHE_RUN_DIR=/var/run \
    APACHE_PID_FILE=/var/run/apache2/apache2.pid

# Install system dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libcurl4-openssl-dev \
    unzip \
    git \
    netcat-openbsd \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_pgsql \
        pgsql \
        curl \
        zip \
        gd \
        mbstring

# Enable Apache modules
RUN rm -f /etc/apache2/mods-enabled/mpm_*.load /etc/apache2/mods-enabled/mpm_*.conf && \
    a2enmod mpm_prefork && \
    a2enmod rewrite && \
    a2enmod deflate && \
    a2enmod expires

# Set working directory
WORKDIR /var/www/html

# Copy application
COPY . /var/www/html/

# Set correct permissions
RUN find /var/www/html -type d -exec chmod 755 {} \; && \
    find /var/www/html -type f -exec chmod 644 {} \;

# Remove local environment file
RUN rm -f /var/www/html/.env

# Copy entrypoint
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh

RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80

ENTRYPOINT ["docker-entrypoint.sh"]
