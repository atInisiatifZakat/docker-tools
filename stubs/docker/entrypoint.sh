#!/bin/bash
set -e

# Function to check if supervisord is available
check_supervisord() {
    if command -v supervisord >/dev/null 2>&1 && [ -f "/etc/supervisor/conf.d/supervisord.conf" ]; then
        return 0
    else
        return 1
    fi
}

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

# Function to start worker
start_worker() {
    echo "Starting Laravel queue worker..."
    exec php artisan queue:work --verbose --tries=3 --timeout=90
}

# Function to start scheduler
start_scheduler() {
    echo "Starting Laravel scheduler..."
    exec php artisan schedule:work
}

# Function to start supervisor
start_supervisor() {
    echo "Starting supervisor..."
    exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
}

# Function to detect if running in Kubernetes
detect_kubernetes() {
    # Method 1: Check for Kubernetes service account token
    if [ -f "/var/run/secrets/kubernetes.io/serviceaccount/token" ]; then
        return 0
    fi

    # Method 2: Check for Kubernetes environment variables
    if [ -n "$KUBERNETES_SERVICE_HOST" ] || [ -n "$KUBERNETES_PORT" ]; then
        return 0
    fi

    # Method 3: Check for typical Kubernetes DNS
    if [ -f "/etc/resolv.conf" ] && grep -q "cluster.local" /etc/resolv.conf; then
        return 0
    fi

    # Method 4: Check hostname pattern (pod names usually have random suffix)
    hostname=$(hostname)
    if echo "$hostname" | grep -qE '^[a-z0-9-]+-[a-z0-9]{5}$|^[a-z0-9-]+-[a-z0-9]{10}-[a-z0-9]{5}$'; then
        return 0
    fi

    # Method 5: Check for container runtime indicators
    if [ -f "/.dockerenv" ] && [ -z "$DOCKER_HOST" ] && [ -n "$HOSTNAME" ]; then
        # In container but no Docker socket = likely K8s
        if [ ! -S "/var/run/docker.sock" ]; then
            return 0
        fi
    fi

    return 1
}

# Determine deployment mode
DEPLOYMENT_MODE=${DEPLOYMENT_MODE:-"docker-compose"}
K8S_MODE=${K8S_MODE:-"false"}
SERVICE_TYPE=${SERVICE_TYPE:-"http"}

# Auto-detect Kubernetes if not explicitly set
if [ "$K8S_MODE" = "false" ] && [ "$DEPLOYMENT_MODE" = "docker-compose" ]; then
    if detect_kubernetes; then
        echo "Kubernetes environment detected automatically!"
        K8S_MODE="true"
        DEPLOYMENT_MODE="kubernetes"
    fi
fi

# Override mode if K8S_MODE is true
if [ "$K8S_MODE" = "true" ]; then
    DEPLOYMENT_MODE="kubernetes"
fi

echo "Deployment Mode: $DEPLOYMENT_MODE"
echo "Service Type: $SERVICE_TYPE"
if detect_kubernetes; then
    echo "Kubernetes Environment: ✅ Detected"
else
    echo "Kubernetes Environment: ❌ Not detected"
fi
echo ""

# Start appropriate service based on mode
if [ "$DEPLOYMENT_MODE" = "kubernetes" ]; then
    # Kubernetes mode - single process per container
    case "$SERVICE_TYPE" in
        "http")
            start_http
            ;;
        "worker")
            start_worker
            ;;
        "scheduler")
            start_scheduler
            ;;
        *)
            echo "Unknown service type: $SERVICE_TYPE"
            echo "Available types: http, worker, scheduler"
            exit 1
            ;;
    esac
else
    # Docker Compose mode - use supervisor for multi-process
    if check_supervisord; then
        start_supervisor
    else
        echo "Supervisor not available, falling back to single process mode"
        case "$SERVICE_TYPE" in
            "http")
                start_http
                ;;
            "worker")
                start_worker
                ;;
            "scheduler")
                start_scheduler
                ;;
            *)
                start_http  # Default to HTTP
                ;;
        esac
    fi
fi
