# Multi-stage Dockerfile untuk Laravel dengan support HTTP server dan worker
# Mendukung deployment di Docker, Google Cloud Run, dan Kubernetes
# Menggunakan Debian Slim untuk compatibility yang lebih baik

# Build arguments
ARG PHP_VERSION=8.3
ARG DEPLOYMENT_MODE=docker-compose

# Base stage dengan PHP dan dependencies
FROM php:${PHP_VERSION}-fpm-bookworm AS base

# Extract major.minor version for configurations
ARG PHP_VERSION
RUN echo "${PHP_VERSION}" | cut -d. -f1,2 > /tmp/php_short_version

# Install system dependencies
RUN apt-get update && apt-get install -y \
    curl \
    git \
    unzip \
    zip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libwebp-dev \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    libpq-dev \
    supervisor \
    nginx-light \
    procps \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        gd \
        zip \
        intl \
        mbstring \
        pdo \
        pdo_mysql \
        pdo_pgsql \
        bcmath \
        opcache \
        pcntl \
        sockets

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files
COPY composer.json composer.lock ./

# Install PHP dependencies (skip scripts that need artisan)
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress --no-scripts

# Node.js build stage (only if package.json exists)
FROM node:18-bookworm-slim AS node-build

WORKDIR /app

# Copy package files (allow failure if package.json doesn't exist)
COPY package.json* bun.lock* bun.lockb* ./

# Only install and build if package.json exists
RUN if [ -f "package.json" ]; then \
        echo "📦 Found package.json, installing frontend dependencies..."; \
        if [ -f "bun.lockb" ] || [ -f "bun.lock" ]; then \
            echo "🔧 Using Bun package manager"; \
            npm install -g bun && bun install; \
        else \
            echo "🔧 Using npm package manager"; \
            npm install; \
        fi; \
    else \
        echo "⚠️  No package.json found, skipping frontend build"; \
        mkdir -p public/build && echo "/* No frontend build */" > public/build/app.css; \
    fi

# Copy source files for building (only if package.json exists)
RUN if [ -f "package.json" ]; then \
        echo "📁 Copying frontend source files..."; \
    fi

COPY resources* ./resources/
COPY vite.config.js* tsconfig.json* ./

# Build assets (only if package.json exists)
RUN if [ -f "package.json" ]; then \
        echo "🏗️  Building frontend assets..."; \
        if [ -f "bun.lockb" ] || [ -f "bun.lock" ]; then \
            bun run build; \
        else \
            npm run build; \
        fi; \
    else \
        echo "⏭️  Skipping frontend build (no package.json)"; \
    fi

# Application stage
FROM base AS app

# Copy application files
COPY . .
COPY --from=node-build /app/public/build ./public/build

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Copy configurations
COPY docker/php/php.ini /usr/local/etc/php/conf.d/app.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# HTTP Server stage dengan Caddy
FROM caddy:2-builder AS caddy-builder

# Build Caddy dengan plugins yang diperlukan (jika ada)
RUN xcaddy build

FROM base AS http

# Build arguments untuk mode switching
ARG DEPLOYMENT_MODE=docker-compose
ENV DEPLOYMENT_MODE=${DEPLOYMENT_MODE}

# Install additional dependencies for HTTP server
RUN apt-get update && apt-get install -y \
    curl \
    ca-certificates \
    && if [ "$DEPLOYMENT_MODE" = "docker-compose" ]; then \
        apt-get install -y supervisor; \
    fi \
    && rm -rf /var/lib/apt/lists/*

# Copy Caddy binary from builder
COPY --from=caddy-builder /usr/bin/caddy /usr/bin/caddy

# Create caddy user
RUN groupadd --gid 1000 caddy \
    && useradd --uid 1000 --gid caddy --shell /bin/bash --create-home caddy

# Copy application from app stage
COPY --from=app /var/www/html /var/www/html

# Copy configurations based on PHP version
ARG PHP_VERSION
RUN PHP_SHORT_VERSION=$(echo "${PHP_VERSION}" | cut -d. -f1,2) && \
    echo "Using PHP version: ${PHP_SHORT_VERSION}"

COPY docker/caddy/ /tmp/caddy/
COPY docker/php/ /tmp/php/
COPY docker/supervisor/ /tmp/supervisor/

# Create necessary directories and install version-specific configurations
RUN PHP_SHORT_VERSION=$(echo "${PHP_VERSION}" | cut -d. -f1,2) && \
    mkdir -p /etc/caddy && \
    mkdir -p "/etc/php/${PHP_SHORT_VERSION}/fpm/pool.d" && \
    cp "/tmp/caddy/${PHP_SHORT_VERSION}/Caddyfile" /etc/caddy/Caddyfile && \
    cp "/tmp/php/${PHP_SHORT_VERSION}/php-fpm.conf" "/etc/php/${PHP_SHORT_VERSION}/fpm/pool.d/www.conf"

# Copy supervisor configuration conditionally
RUN PHP_SHORT_VERSION=$(echo "${PHP_VERSION}" | cut -d. -f1,2) && \
    if [ "$DEPLOYMENT_MODE" = "docker-compose" ] && [ -f "/tmp/supervisor/${PHP_SHORT_VERSION}/supervisord.conf" ]; then \
        mkdir -p /etc/supervisor/conf.d && \
        cp "/tmp/supervisor/${PHP_SHORT_VERSION}/supervisord.conf" /etc/supervisor/conf.d/supervisord.conf; \
    fi

# Set permissions
RUN chown -R caddy:caddy /var/www/html

# Create directories conditionally
RUN mkdir -p /var/run/php && \
    if [ "$DEPLOYMENT_MODE" = "docker-compose" ]; then \
        mkdir -p /var/log/supervisor; \
    fi

# Copy entrypoint script
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Set service type automatically
ENV SERVICE_TYPE=http

# Expose port
EXPOSE 8080

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost:8080/up || exit 1

# Use smart entrypoint
CMD ["/entrypoint.sh"]

# Worker stage
FROM app AS worker

# Build arguments untuk mode switching
ARG DEPLOYMENT_MODE=docker-compose
ENV DEPLOYMENT_MODE=${DEPLOYMENT_MODE}

# Install supervisor conditionally
RUN apt-get update && \
    if [ "$DEPLOYMENT_MODE" = "docker-compose" ]; then \
        apt-get install -y supervisor; \
    fi && \
    rm -rf /var/lib/apt/lists/*

# Copy supervisor configuration conditionally
COPY docker/supervisor/worker.conf* /tmp/
RUN if [ "$DEPLOYMENT_MODE" = "docker-compose" ] && [ -f "/tmp/worker.conf" ]; then \
        mkdir -p /etc/supervisor/conf.d && \
        cp /tmp/worker.conf /etc/supervisor/conf.d/supervisord.conf; \
    fi

# Create log directory conditionally
RUN if [ "$DEPLOYMENT_MODE" = "docker-compose" ]; then \
        mkdir -p /var/log/supervisor; \
    fi

# Copy entrypoint script
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Set service type environment
ENV SERVICE_TYPE=worker

# Health check untuk worker
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD pgrep -f "php artisan queue:work" > /dev/null || exit 1

# Use smart entrypoint
CMD ["/entrypoint.sh"]

# Scheduler stage (optional, untuk cron jobs)
FROM app AS scheduler

# Build arguments untuk mode switching
ARG DEPLOYMENT_MODE=docker-compose
ENV DEPLOYMENT_MODE=${DEPLOYMENT_MODE}

# Install supervisor and cron conditionally
RUN apt-get update && \
    if [ "$DEPLOYMENT_MODE" = "docker-compose" ]; then \
        apt-get install -y supervisor cron; \
    fi && \
    rm -rf /var/lib/apt/lists/*

# Copy supervisor configuration conditionally
COPY docker/supervisor/scheduler.conf* /tmp/
RUN if [ "$DEPLOYMENT_MODE" = "docker-compose" ] && [ -f "/tmp/scheduler.conf" ]; then \
        mkdir -p /etc/supervisor/conf.d && \
        cp /tmp/scheduler.conf /etc/supervisor/conf.d/supervisord.conf; \
    fi

# Copy crontab conditionally
COPY docker/cron/laravel-cron* /tmp/
RUN if [ "$DEPLOYMENT_MODE" = "docker-compose" ] && [ -f "/tmp/laravel-cron" ]; then \
        cp /tmp/laravel-cron /etc/cron.d/laravel-cron && \
        chmod 0644 /etc/cron.d/laravel-cron && \
        crontab /etc/cron.d/laravel-cron; \
    fi

# Create log directory conditionally
RUN if [ "$DEPLOYMENT_MODE" = "docker-compose" ]; then \
        mkdir -p /var/log/supervisor /var/log/cron; \
    fi

# Copy entrypoint script
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Set service type environment
ENV SERVICE_TYPE=scheduler

# Health check untuk scheduler
HEALTHCHECK --interval=60s --timeout=3s --start-period=5s --retries=3 \
    CMD pgrep cron || exit 1

# Use smart entrypoint
CMD ["/entrypoint.sh"]
