#!/bin/bash
set -e

# Function to wait for a service to be ready
wait_for_service() {
    host="$1"
    port="$2"
    service_name="$3"
    timeout="${4:-30}"
    
    echo "Waiting for $service_name at $host:$port..."
    for i in $(seq 1 $timeout); do
        if nc -z "$host" "$port" 2>/dev/null; then
            echo "$service_name is ready!"
            return 0
        fi
        sleep 1
    done
    echo "Timeout waiting for $service_name"
    exit 1
}

# Function to create .env file from environment variables
create_env_file() {
    ENV_FILE="/var/www/html/.env"
    
    if [ ! -f "$ENV_FILE" ]; then
        echo "Creating .env file from environment variables..."
        cat > "$ENV_FILE" << EOF
SUPABASE_URL=${SUPABASE_URL:-}
SUPABASE_SERVICE_ROLE_KEY=${SUPABASE_SERVICE_ROLE_KEY:-}
SUPABASE_STORAGE_BUCKET=${SUPABASE_STORAGE_BUCKET:-avatars}
SUPABASE_MEDIA_BUCKET=${SUPABASE_MEDIA_BUCKET:-media}
EOF
        chown www-data:www-data "$ENV_FILE"
        chmod 600 "$ENV_FILE"
        echo ".env file created successfully"
    else
        echo ".env file already exists, skipping creation"
    fi
}

# Function to check required environment variables
check_required_vars() {
    missing_vars=()
    
    if [ -z "${SUPABASE_URL}" ]; then
        missing_vars+=("SUPABASE_URL")
    fi
    
    if [ -z "${SUPABASE_SERVICE_ROLE_KEY}" ]; then
        missing_vars+=("SUPABASE_SERVICE_ROLE_KEY")
    fi
    
    if [ ${#missing_vars[@]} -gt 0 ]; then
        echo "ERROR: Missing required environment variables: ${missing_vars[*]}"
        echo "Please set these variables in your container environment"
        exit 1
    fi
    
    echo "All required environment variables are set"
}

# Function to test database connection
test_db_connection() {
    echo "Testing database connection..."
    # Since we're using Supabase (external), we just verify the URL is reachable
    if [ -n "${SUPABASE_URL}" ]; then
        if curl -s --max-time 10 "${SUPABASE_URL}/rest/v1/" > /dev/null 2>&1; then
            echo "Database connection successful"
        else
            echo "WARNING: Could not connect to database at ${SUPABASE_URL}"
            echo "Application will start anyway, but database operations may fail"
        fi
    fi
}

# Function to set up Apache
setup_apache() {
    echo "Setting up Apache..."
    
    # Create necessary directories
    mkdir -p "${APACHE_LOG_DIR}"
    mkdir -p "${APACHE_RUN_DIR}"
    
    # Set proper ownership
    chown -R www-data:www-data "${APACHE_LOG_DIR}"
    chown -R www-data:www-data "${APACHE_RUN_DIR}"
    
    # Ensure .htaccess is enabled
    if [ -f /var/www/html/.htaccess ]; then
        echo "Using custom .htaccess file"
    fi
    
    # Ensure DirectoryIndex is set to index.html first (override any Apache defaults)
    echo "DirectoryIndex index.html index.php" > /etc/apache2/conf-available/index-preference.conf
    a2enconf index-preference >/dev/null 2>&1 || true
}

# Main execution
echo "=== IMG Media Vault Docker Entrypoint ==="
echo "Starting application..."

# Check required environment variables
check_required_vars

# Create .env file
create_env_file

# Test database connection
test_db_connection

# Setup Apache
setup_apache

# Execute the main Apache process
echo "Starting Apache..."
exec apache2-foreground