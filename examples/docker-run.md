# Direct Docker Run Commands

Simple Docker run commands for quick deployment and testing without Docker Compose or orchestration platforms.

## HTTP Server

### Basic Setup

```bash
# Basic HTTP server
docker run -d --name laravel-app \
  -p 8080:8080 \
  -e APP_ENV=production \
  -e APP_DEBUG=false \
  -e DB_HOST=host.docker.internal \
  -e DB_DATABASE=laravel \
  myapp:http-php8.3
```

### With External Database

```bash
# Connect to external MySQL database
docker run -d --name laravel-app \
  -p 8080:8080 \
  -e APP_ENV=production \
  -e APP_KEY=base64:your-32-character-key-here \
  -e DB_CONNECTION=mysql \
  -e DB_HOST=mysql.example.com \
  -e DB_PORT=3306 \
  -e DB_DATABASE=laravel \
  -e DB_USERNAME=laravel \
  -e DB_PASSWORD=secure_password \
  myapp:http-php8.3
```

### With Redis Cache

```bash
# HTTP server with Redis cache and sessions
docker run -d --name laravel-app \
  -p 8080:8080 \
  -e APP_ENV=production \
  -e APP_KEY=base64:your-32-character-key-here \
  -e DB_HOST=database.example.com \
  -e DB_DATABASE=laravel \
  -e DB_USERNAME=laravel \
  -e DB_PASSWORD=secure_password \
  -e CACHE_DRIVER=redis \
  -e SESSION_DRIVER=redis \
  -e REDIS_HOST=redis.example.com \
  -e REDIS_PORT=6379 \
  myapp:http-php8.3
```

### Custom PHP Configuration

```bash
# HTTP server with custom PHP settings
docker run -d --name laravel-app \
  -p 8080:8080 \
  -e APP_ENV=production \
  -e PHP_MEMORY_LIMIT=512M \
  -e PHP_MAX_EXECUTION_TIME=300 \
  -e PHP_UPLOAD_MAX_FILESIZE=64M \
  -e PHP_POST_MAX_SIZE=64M \
  myapp:http-php8.3
```

### With Volume Mounts

```bash
# HTTP server with persistent storage
docker run -d --name laravel-app \
  -p 8080:8080 \
  -v /host/path/storage:/var/www/html/storage \
  -v /host/path/logs:/var/www/html/storage/logs \
  -e APP_ENV=production \
  -e DB_HOST=host.docker.internal \
  myapp:http-php8.3
```

## Artisan Commands

### Queue Workers

```bash
# Basic queue worker
docker run -d --name laravel-worker \
  -e APP_ENV=production \
  -e DB_HOST=host.docker.internal \
  -e QUEUE_CONNECTION=redis \
  -e REDIS_HOST=host.docker.internal \
  myapp:artisan-php8.3 \
  php artisan queue:work --verbose --tries=3 --timeout=90

# Multiple queue workers
for i in {1..5}; do
  docker run -d --name laravel-worker-$i \
    -e APP_ENV=production \
    -e DB_HOST=host.docker.internal \
    -e QUEUE_CONNECTION=redis \
    -e REDIS_HOST=host.docker.internal \
    myapp:artisan-php8.3 \
    php artisan queue:work --verbose --tries=3 --timeout=90 --memory=512
done

# Worker with specific queue
docker run -d --name laravel-worker-emails \
  -e APP_ENV=production \
  -e DB_HOST=host.docker.internal \
  -e QUEUE_CONNECTION=redis \
  -e REDIS_HOST=host.docker.internal \
  myapp:artisan-php8.3 \
  php artisan queue:work --queue=emails --verbose --tries=5
```

### Scheduler

```bash
# Basic scheduler
docker run -d --name laravel-scheduler \
  -e APP_ENV=production \
  -e DB_HOST=host.docker.internal \
  -e DB_DATABASE=laravel \
  -e DB_USERNAME=laravel \
  -e DB_PASSWORD=secure_password \
  myapp:artisan-php8.3 \
  php artisan schedule:work

# Scheduler with logging
docker run -d --name laravel-scheduler \
  -v /host/logs:/var/www/html/storage/logs \
  -e APP_ENV=production \
  -e DB_HOST=host.docker.internal \
  myapp:artisan-php8.3 \
  php artisan schedule:work
```

### Database Operations

```bash
# Run migrations
docker run --rm \
  -e APP_ENV=production \
  -e DB_HOST=host.docker.internal \
  -e DB_DATABASE=laravel \
  -e DB_USERNAME=laravel \
  -e DB_PASSWORD=secure_password \
  myapp:artisan-php8.3 \
  php artisan migrate --force

# Run database seeding
docker run --rm \
  -e APP_ENV=production \
  -e DB_HOST=host.docker.internal \
  myapp:artisan-php8.3 \
  php artisan db:seed --force

# Create database backup
docker run --rm \
  -v /host/backups:/backups \
  -e APP_ENV=production \
  -e DB_HOST=host.docker.internal \
  myapp:artisan-php8.3 \
  php artisan backup:run
```

### Cache Operations

```bash
# Clear all caches
docker run --rm \
  -e APP_ENV=production \
  -e CACHE_DRIVER=redis \
  -e REDIS_HOST=host.docker.internal \
  myapp:artisan-php8.3 \
  php artisan cache:clear

# Optimize for production
docker run --rm \
  -e APP_ENV=production \
  myapp:artisan-php8.3 \
  php artisan optimize

# Clear and rebuild caches
docker run --rm \
  -e APP_ENV=production \
  myapp:artisan-php8.3 \
  sh -c "php artisan config:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache"
```

### Custom Artisan Commands

```bash
# Run custom artisan command
docker run --rm \
  -e APP_ENV=production \
  -e DB_HOST=host.docker.internal \
  myapp:artisan-php8.3 \
  php artisan custom:import-data --file=data.csv

# Interactive tinker session
docker run -it --rm \
  -e APP_ENV=local \
  -e DB_HOST=host.docker.internal \
  myapp:artisan-php8.3 \
  php artisan tinker

# Generate application key
docker run --rm \
  -e APP_ENV=production \
  myapp:artisan-php8.3 \
  php artisan key:generate --show
```

## Multi-PHP Version Support

### PHP 8.1

```bash
# HTTP with PHP 8.1
docker run -d -p 8080:8080 \
  -e APP_ENV=production \
  myapp:http-php8.1

# Artisan with PHP 8.1
docker run --rm \
  -e APP_ENV=production \
  myapp:artisan-php8.1 \
  php artisan migrate
```

### PHP 8.2

```bash
# HTTP with PHP 8.2
docker run -d -p 8080:8080 \
  -e APP_ENV=production \
  myapp:http-php8.2

# Worker with PHP 8.2
docker run -d \
  -e APP_ENV=production \
  -e QUEUE_CONNECTION=redis \
  myapp:artisan-php8.2 \
  php artisan queue:work
```

### PHP 8.3

```bash
# HTTP with PHP 8.3 (default)
docker run -d -p 8080:8080 \
  -e APP_ENV=production \
  myapp:http-php8.3

# Scheduler with PHP 8.3
docker run -d \
  -e APP_ENV=production \
  myapp:artisan-php8.3 \
  php artisan schedule:work
```

### PHP 8.4

```bash
# HTTP with PHP 8.4 (latest)
docker run -d -p 8080:8080 \
  -e APP_ENV=production \
  myapp:http-php8.4

# Custom command with PHP 8.4
docker run --rm \
  -e APP_ENV=production \
  myapp:artisan-php8.4 \
  php artisan inspire
```

## Networking Examples

### Using Docker Networks

```bash
# Create a custom network
docker network create laravel-network

# Run database on custom network
docker run -d --name mysql-db \
  --network laravel-network \
  -e MYSQL_ROOT_PASSWORD=secret \
  -e MYSQL_DATABASE=laravel \
  -e MYSQL_USER=laravel \
  -e MYSQL_PASSWORD=password \
  mysql:8.0

# Run Redis on custom network
docker run -d --name redis-cache \
  --network laravel-network \
  redis:7-alpine

# Run Laravel app connected to services
docker run -d --name laravel-app \
  --network laravel-network \
  -p 8080:8080 \
  -e APP_ENV=production \
  -e DB_HOST=mysql-db \
  -e DB_DATABASE=laravel \
  -e DB_USERNAME=laravel \
  -e DB_PASSWORD=password \
  -e CACHE_DRIVER=redis \
  -e REDIS_HOST=redis-cache \
  myapp:http-php8.3

# Run worker connected to services
docker run -d --name laravel-worker \
  --network laravel-network \
  -e APP_ENV=production \
  -e DB_HOST=mysql-db \
  -e QUEUE_CONNECTION=redis \
  -e REDIS_HOST=redis-cache \
  myapp:artisan-php8.3 \
  php artisan queue:work --verbose
```

## Health Checks and Monitoring

```bash
# HTTP server with health check
docker run -d --name laravel-app \
  -p 8080:8080 \
  -e APP_ENV=production \
  --health-cmd="curl -f http://localhost:8080/up || exit 1" \
  --health-interval=30s \
  --health-timeout=3s \
  --health-retries=3 \
  myapp:http-php8.3

# Check container health
docker ps
docker inspect --format='{{.State.Health.Status}}' laravel-app

# View container logs
docker logs -f laravel-app

# Execute commands inside running container
docker exec laravel-app php artisan --version
docker exec -it laravel-app bash
```

## Resource Management

```bash
# Run with resource limits
docker run -d --name laravel-app \
  -p 8080:8080 \
  --memory=512m \
  --cpus="0.5" \
  -e APP_ENV=production \
  myapp:http-php8.3

# Run worker with memory limit
docker run -d --name laravel-worker \
  --memory=1g \
  --cpus="0.5" \
  -e APP_ENV=production \
  -e QUEUE_CONNECTION=redis \
  myapp:artisan-php8.3 \
  php artisan queue:work --memory=900
```

## Environment-Specific Configurations

### Development

```bash
# Development HTTP server with debugging
docker run -d --name laravel-dev \
  -p 8080:8080 \
  -v $(pwd):/var/www/html \
  -e APP_ENV=local \
  -e APP_DEBUG=true \
  -e DB_HOST=host.docker.internal \
  myapp:http-php8.3
```

### Staging

```bash
# Staging environment
docker run -d --name laravel-staging \
  -p 8080:8080 \
  -e APP_ENV=staging \
  -e APP_DEBUG=false \
  -e DB_HOST=staging-db.example.com \
  -e MAIL_MAILER=log \
  myapp:http-php8.3
```

### Production

```bash
# Production with full configuration
docker run -d --name laravel-prod \
  -p 8080:8080 \
  --restart=unless-stopped \
  --memory=512m \
  --cpus="0.5" \
  -e APP_ENV=production \
  -e APP_DEBUG=false \
  -e APP_KEY=base64:production-key-here \
  -e DB_HOST=prod-db.example.com \
  -e CACHE_DRIVER=redis \
  -e SESSION_DRIVER=redis \
  -e QUEUE_CONNECTION=redis \
  -e REDIS_HOST=prod-redis.example.com \
  myapp:http-php8.3
```

## Troubleshooting Commands

```bash
# Check if image exists
docker images | grep myapp

# Inspect image
docker inspect myapp:http-php8.3

# Run interactive shell for debugging
docker run -it --rm myapp:artisan-php8.3 bash

# Check PHP configuration
docker run --rm myapp:artisan-php8.3 php -i

# Test database connection
docker run --rm \
  -e DB_HOST=host.docker.internal \
  -e DB_DATABASE=laravel \
  myapp:artisan-php8.3 \
  php artisan tinker --execute="DB::connection()->getPdo();"

# Check queue status
docker run --rm \
  -e APP_ENV=production \
  -e QUEUE_CONNECTION=redis \
  -e REDIS_HOST=host.docker.internal \
  myapp:artisan-php8.3 \
  php artisan queue:failed
```

## Quick Deploy Script

```bash
#!/bin/bash
# quick-deploy.sh

APP_NAME=${1:-myapp}
PHP_VERSION=${2:-8.3}
APP_ENV=${3:-production}

echo "🚀 Quick deploying $APP_NAME with PHP $PHP_VERSION"

# Stop existing containers
docker stop laravel-app laravel-worker laravel-scheduler 2>/dev/null || true
docker rm laravel-app laravel-worker laravel-scheduler 2>/dev/null || true

# Start HTTP server
docker run -d --name laravel-app \
  -p 8080:8080 \
  --restart=unless-stopped \
  -e APP_ENV=$APP_ENV \
  -e DB_HOST=host.docker.internal \
  $APP_NAME:http-php$PHP_VERSION

# Start queue worker
docker run -d --name laravel-worker \
  --restart=unless-stopped \
  -e APP_ENV=$APP_ENV \
  -e DB_HOST=host.docker.internal \
  -e QUEUE_CONNECTION=redis \
  -e REDIS_HOST=host.docker.internal \
  $APP_NAME:artisan-php$PHP_VERSION \
  php artisan queue:work --verbose

# Start scheduler
docker run -d --name laravel-scheduler \
  --restart=unless-stopped \
  -e APP_ENV=$APP_ENV \
  -e DB_HOST=host.docker.internal \
  $APP_NAME:artisan-php$PHP_VERSION \
  php artisan schedule:work

echo "✅ Deployment completed!"
echo "🌐 Application: http://localhost:8080"
echo "🔍 Check status: docker ps"
```

## Usage

```bash
# Make script executable
chmod +x quick-deploy.sh

# Deploy with defaults
./quick-deploy.sh

# Deploy with custom settings
./quick-deploy.sh myapp 8.4 staging
```
