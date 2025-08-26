#!/bin/bash
set -e

# Function to start HTTP server
start_http() {
    echo "Starting HTTP server..."

    # Extract PHP version (default to 8.3 if not set)
    PHP_VERSION=${PHP_VERSION:-8.3}
    PHP_SHORT_VERSION=$(echo "${PHP_VERSION}" | cut -d. -f1,2)

    echo "Using PHP version: ${PHP_VERSION} (short: ${PHP_SHORT_VERSION})"

    # Validate PHP version is supported
    if [[ ! "$PHP_SHORT_VERSION" =~ ^8\.[1-4]$ ]]; then
        echo "Error: Unsupported PHP version '${PHP_VERSION}'. Supported versions: 8.1, 8.2, 8.3, 8.4"
        exit 1
    fi

    # Start PHP-FPM in background with correct version
    /usr/sbin/php-fpm${PHP_SHORT_VERSION} -D --fpm-config /etc/php/${PHP_SHORT_VERSION}/fpm/pool.d/www.conf

    # Start Caddy
    exec /usr/bin/caddy run --config /etc/caddy/Caddyfile
}

# If first argument is "http", start HTTP server
if [ "$1" = "http" ]; then
    start_http
else
    # Otherwise, execute whatever command was passed
    exec "$@"
fi
