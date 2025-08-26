# Docker Compose Examples

Docker Compose configurations for VM environments (EC2, Droplets, VMs) supporting both development and production setups.

## Development Environment

```yaml
version: '3.8'

services:
  app:
    build:
      context: .
      target: http
    ports:
      - "8080:8080"
    environment:
      - APP_ENV=local
      - APP_DEBUG=true
      - APP_KEY=base64:your-32-character-key-here
      - DB_HOST=database
      - DB_DATABASE=laravel
      - DB_USERNAME=laravel
      - DB_PASSWORD=password
      - QUEUE_CONNECTION=redis
      - REDIS_HOST=redis
      - CACHE_DRIVER=redis
      - SESSION_DRIVER=redis
      - MAIL_MAILER=log
    volumes:
      - .:/var/www/html
      - ./storage:/var/www/html/storage
      - ./bootstrap/cache:/var/www/html/bootstrap/cache
    depends_on:
      - database
      - redis
    restart: unless-stopped

  worker:
    build:
      context: .
      target: artisan
    command: ["php", "artisan", "queue:work", "--verbose", "--tries=3"]
    environment:
      - APP_ENV=local
      - APP_DEBUG=true
      - APP_KEY=base64:your-32-character-key-here
      - DB_HOST=database
      - DB_DATABASE=laravel
      - DB_USERNAME=laravel
      - DB_PASSWORD=password
      - QUEUE_CONNECTION=redis
      - REDIS_HOST=redis
    volumes:
      - .:/var/www/html
      - ./storage:/var/www/html/storage
    depends_on:
      - database
      - redis
    restart: unless-stopped

  scheduler:
    build:
      context: .
      target: artisan
    command: ["php", "artisan", "schedule:work"]
    environment:
      - APP_ENV=local
      - APP_DEBUG=true
      - APP_KEY=base64:your-32-character-key-here
      - DB_HOST=database
      - DB_DATABASE=laravel
      - DB_USERNAME=laravel
      - DB_PASSWORD=password
    volumes:
      - .:/var/www/html
      - ./storage:/var/www/html/storage
    depends_on:
      - database
    restart: unless-stopped

  database:
    image: mysql:8.0
    command: --default-authentication-plugin=mysql_native_password
    environment:
      - MYSQL_ROOT_PASSWORD=secret
      - MYSQL_DATABASE=laravel
      - MYSQL_USER=laravel
      - MYSQL_PASSWORD=password
    volumes:
      - db_data:/var/lib/mysql
    ports:
      - "3306:3306"
    restart: unless-stopped

  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"
    volumes:
      - redis_data:/data
    restart: unless-stopped

  mailhog:
    image: mailhog/mailhog:latest
    ports:
      - "1025:1025"
      - "8025:8025"
    restart: unless-stopped

volumes:
  db_data:
  redis_data:
```

## Production Environment

```yaml
version: '3.8'

services:
  app:
    image: myapp:http-php8.3
    ports:
      - "8080:8080"
    environment:
      - APP_ENV=production
      - APP_DEBUG=false
      - APP_KEY=base64:your-production-32-character-key-here
      - APP_URL=https://yourdomain.com
      - DB_HOST=database
      - DB_DATABASE=laravel
      - DB_USERNAME=laravel
      - DB_PASSWORD=secure_production_password
      - QUEUE_CONNECTION=redis
      - REDIS_HOST=redis
      - CACHE_DRIVER=redis
      - SESSION_DRIVER=redis
      - MAIL_MAILER=smtp
      - MAIL_HOST=smtp.mailgun.org
      - MAIL_PORT=587
      - MAIL_USERNAME=your-smtp-username
      - MAIL_PASSWORD=your-smtp-password
      - MAIL_ENCRYPTION=tls
    deploy:
      replicas: 3
      resources:
        limits:
          memory: 512M
          cpus: '0.5'
        reservations:
          memory: 256M
          cpus: '0.25'
      restart_policy:
        condition: on-failure
        delay: 5s
        max_attempts: 3
        window: 120s
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost:8080/up"]
      interval: 30s
      timeout: 10s
      retries: 3
      start_period: 40s
    depends_on:
      - database
      - redis
    restart: unless-stopped

  worker:
    image: myapp:artisan-php8.3
    command: ["php", "artisan", "queue:work", "--verbose", "--tries=3", "--timeout=90", "--memory=512"]
    environment:
      - APP_ENV=production
      - APP_DEBUG=false
      - APP_KEY=base64:your-production-32-character-key-here
      - DB_HOST=database
      - DB_DATABASE=laravel
      - DB_USERNAME=laravel
      - DB_PASSWORD=secure_production_password
      - QUEUE_CONNECTION=redis
      - REDIS_HOST=redis
    deploy:
      replicas: 5
      resources:
        limits:
          memory: 1G
          cpus: '0.5'
        reservations:
          memory: 512M
          cpus: '0.25'
      restart_policy:
        condition: on-failure
        delay: 5s
        max_attempts: 5
    healthcheck:
      test: ["CMD", "pgrep", "-f", "php artisan queue:work"]
      interval: 30s
      timeout: 10s
      retries: 3
    depends_on:
      - database
      - redis
    restart: unless-stopped

  scheduler:
    image: myapp:artisan-php8.3
    command: ["php", "artisan", "schedule:work"]
    environment:
      - APP_ENV=production
      - APP_DEBUG=false
      - APP_KEY=base64:your-production-32-character-key-here
      - DB_HOST=database
      - DB_DATABASE=laravel
      - DB_USERNAME=laravel
      - DB_PASSWORD=secure_production_password
    deploy:
      replicas: 1
      resources:
        limits:
          memory: 256M
          cpus: '0.2'
        reservations:
          memory: 128M
          cpus: '0.1'
      restart_policy:
        condition: on-failure
        delay: 15s
        max_attempts: 3
    healthcheck:
      test: ["CMD", "pgrep", "-f", "php artisan schedule:work"]
      interval: 60s
      timeout: 10s
      retries: 3
    depends_on:
      - database
    restart: unless-stopped

  database:
    image: mysql:8.0
    command: --default-authentication-plugin=mysql_native_password --innodb-buffer-pool-size=1G
    environment:
      - MYSQL_ROOT_PASSWORD=very_secure_root_password
      - MYSQL_DATABASE=laravel
      - MYSQL_USER=laravel
      - MYSQL_PASSWORD=secure_production_password
    volumes:
      - db_data:/var/lib/mysql
      - ./mysql/conf.d:/etc/mysql/conf.d:ro
    deploy:
      resources:
        limits:
          memory: 2G
          cpus: '1.0'
        reservations:
          memory: 1G
          cpus: '0.5'
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      interval: 30s
      timeout: 10s
      retries: 3
    restart: unless-stopped

  redis:
    image: redis:7-alpine
    command: redis-server --appendonly yes --maxmemory 256mb --maxmemory-policy allkeys-lru
    volumes:
      - redis_data:/data
    deploy:
      resources:
        limits:
          memory: 512M
          cpus: '0.2'
        reservations:
          memory: 256M
          cpus: '0.1'
    healthcheck:
      test: ["CMD", "redis-cli", "ping"]
      interval: 30s
      timeout: 3s
      retries: 3
    restart: unless-stopped

  # Reverse proxy with SSL termination (optional)
  nginx:
    image: nginx:alpine
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./nginx/nginx.conf:/etc/nginx/nginx.conf:ro
      - ./nginx/conf.d:/etc/nginx/conf.d:ro
      - ./ssl:/etc/ssl:ro
    depends_on:
      - app
    restart: unless-stopped

volumes:
  db_data:
  redis_data:
```

## Docker Swarm (Production Cluster)

```yaml
version: '3.8'

services:
  app:
    image: myapp:http-php8.3
    ports:
      - target: 8080
        published: 8080
        protocol: tcp
        mode: ingress
    environment:
      - APP_ENV=production
      - APP_DEBUG=false
      - DB_HOST=database
      - DB_DATABASE=laravel
      - QUEUE_CONNECTION=redis
      - REDIS_HOST=redis
    networks:
      - laravel-network
    deploy:
      replicas: 6
      update_config:
        parallelism: 2
        delay: 10s
        order: start-first
      restart_policy:
        condition: on-failure
        delay: 5s
        max_attempts: 3
        window: 120s
      resources:
        limits:
          memory: 512M
          cpus: '0.5'
        reservations:
          memory: 256M
          cpus: '0.25'
      placement:
        constraints:
          - node.role == worker
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost:8080/up"]
      interval: 30s
      timeout: 10s
      retries: 3

  worker:
    image: myapp:artisan-php8.3
    command: ["php", "artisan", "queue:work", "--verbose", "--tries=3", "--timeout=90"]
    environment:
      - APP_ENV=production
      - DB_HOST=database
      - DB_DATABASE=laravel
      - QUEUE_CONNECTION=redis
      - REDIS_HOST=redis
    networks:
      - laravel-network
    deploy:
      replicas: 10
      restart_policy:
        condition: on-failure
        delay: 5s
        max_attempts: 5
      resources:
        limits:
          memory: 1G
          cpus: '0.5'
      placement:
        constraints:
          - node.role == worker

  scheduler:
    image: myapp:artisan-php8.3
    command: ["php", "artisan", "schedule:work"]
    environment:
      - APP_ENV=production
      - DB_HOST=database
      - DB_DATABASE=laravel
    networks:
      - laravel-network
    deploy:
      replicas: 1
      restart_policy:
        condition: on-failure
        delay: 15s
        max_attempts: 3
      resources:
        limits:
          memory: 256M
          cpus: '0.2'
      placement:
        constraints:
          - node.role == worker
        preferences:
          - spread: node.id

  database:
    image: mysql:8.0
    environment:
      - MYSQL_ROOT_PASSWORD_FILE=/run/secrets/mysql_root_password
      - MYSQL_DATABASE=laravel
      - MYSQL_USER=laravel
      - MYSQL_PASSWORD_FILE=/run/secrets/mysql_password
    volumes:
      - db_data:/var/lib/mysql
    networks:
      - laravel-network
    deploy:
      replicas: 1
      placement:
        constraints:
          - node.role == manager
      resources:
        limits:
          memory: 2G
          cpus: '1.0'
    secrets:
      - mysql_root_password
      - mysql_password

  redis:
    image: redis:7-alpine
    command: redis-server --appendonly yes
    volumes:
      - redis_data:/data
    networks:
      - laravel-network
    deploy:
      replicas: 1
      placement:
        constraints:
          - node.role == worker
      resources:
        limits:
          memory: 512M
          cpus: '0.2'

networks:
  laravel-network:
    driver: overlay
    attachable: true

volumes:
  db_data:
  redis_data:

secrets:
  mysql_root_password:
    external: true
  mysql_password:
    external: true
```

## Quick Deploy Scripts

### Development Setup

```bash
#!/bin/bash
# setup-dev.sh

echo "🚀 Setting up Laravel development environment..."

# Create .env if it doesn't exist
if [ ! -f .env ]; then
    cp .env.example .env
    echo "✅ Created .env file"
fi

# Build and start services
docker-compose up -d --build

# Wait for database
echo "⏳ Waiting for database to be ready..."
sleep 10

# Run migrations
docker-compose exec app php artisan migrate --force

# Generate app key if needed
docker-compose exec app php artisan key:generate

# Install dependencies
docker-compose exec app composer install

# Set permissions
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache

echo "✅ Development environment is ready!"
echo "🌐 Application: http://localhost:8080"
echo "📧 Mailhog: http://localhost:8025"
```

### Production Deploy

```bash
#!/bin/bash
# deploy-prod.sh

set -e

IMAGE_TAG=${1:-latest}
STACK_NAME=${2:-laravel-app}

echo "🚀 Deploying Laravel application to production..."
echo "Image Tag: $IMAGE_TAG"
echo "Stack Name: $STACK_NAME"

# Update image tags in docker-compose.prod.yml
sed -i "s|myapp:http-php8.3|myapp:http-php8.3-$IMAGE_TAG|g" docker-compose.prod.yml
sed -i "s|myapp:artisan-php8.3|myapp:artisan-php8.3-$IMAGE_TAG|g" docker-compose.prod.yml

# Deploy with Docker Compose
docker-compose -f docker-compose.prod.yml up -d --remove-orphans

# Wait for services to be ready
echo "⏳ Waiting for services to be ready..."
sleep 30

# Run migrations
docker-compose -f docker-compose.prod.yml exec app php artisan migrate --force

# Clear caches
docker-compose -f docker-compose.prod.yml exec app php artisan config:cache
docker-compose -f docker-compose.prod.yml exec app php artisan route:cache
docker-compose -f docker-compose.prod.yml exec app php artisan view:cache

echo "✅ Production deployment completed!"
echo "🔍 Check logs: docker-compose -f docker-compose.prod.yml logs -f"
```

## Usage Examples

### Single Commands

```bash
# HTTP Server only
docker run -p 8080:8080 \
  -e APP_ENV=production \
  -e DB_HOST=host.docker.internal \
  myapp:http-php8.3

# Queue Worker only
docker run \
  -e APP_ENV=production \
  -e DB_HOST=host.docker.internal \
  -e QUEUE_CONNECTION=redis \
  -e REDIS_HOST=host.docker.internal \
  myapp:artisan-php8.3 \
  php artisan queue:work --verbose

# One-time migration
docker run \
  -e APP_ENV=production \
  -e DB_HOST=host.docker.internal \
  myapp:artisan-php8.3 \
  php artisan migrate --force
```

### Service Management

```bash
# Scale services
docker-compose up -d --scale worker=8 --scale app=4

# View logs
docker-compose logs -f app
docker-compose logs -f worker
docker-compose logs -f scheduler

# Execute commands
docker-compose exec app php artisan tinker
docker-compose exec app php artisan queue:failed
docker-compose exec app php artisan cache:clear

# Health checks
curl http://localhost:8080/up
docker-compose ps
```

## Best Practices

1. **Environment Variables**: Use `.env` files and never commit secrets
2. **Health Checks**: Implement proper health checks for all services  
3. **Resource Limits**: Set appropriate memory and CPU limits
4. **Persistent Data**: Use volumes for database and Redis data
5. **Single Scheduler**: Only run one scheduler instance
6. **Monitoring**: Use logging and monitoring tools in production
7. **Backups**: Implement regular database backups
8. **SSL/TLS**: Use reverse proxy with SSL termination for production
