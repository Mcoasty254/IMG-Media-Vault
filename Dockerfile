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

# Install required system packages
RUN apt-get update && apt-get install -y --no-install-recommends \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libcurl4-openssl-dev \
    unzip \
    git \
    && rm -rf /var/lib/apt/lists/*

# Configure PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        pgsql \
        curl \
        zip \
        gd \
        mbstring \
        openssl \
        tokenizer \
        xml \
        json \
        fileinfo

# Enable Apache modules
RUN a2enmod rewrite \
    a2enmod deflate \
    a2enmod expires

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html/

# Fix permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 644 /var/www/html/*.php \
    && chmod -R 644 /var/www/html/*.html \
    && chmod -R 644 /var/www/html/*.sql \
    && chmod -R 644 /var/www/html/*.md \
    && chmod -R 644 /var/www/html/.env.example \
    && chmod -R 644 /var/www/html/.gitignore \
    && chmod -R 644 /var/www/html/.htaccess \
    && chmod -R 644 /var/www/html/css/* \
    && chmod -R 644 /var/www/html/js/* \
    && chmod -R 644 /var/www/html/lib/* \
    && chmod -R 644 /var/www/html/includes/* \
    && chmod -R 644 /var/www/html/assets/* \
    && chmod -R 644 /var/www/html/supabase-schema.sql

# Remove .env file (will be created at runtime)
RUN rm -f /var/www/html/.env

# Copy and make entrypoint script executable
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Expose port
EXPOSE 80

# Set entrypoint
ENTRYPOINT ["docker-entrypoint.sh"]