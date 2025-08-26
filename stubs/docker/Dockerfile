# Multi-stage Dockerfile untuk Laravel dengan support HTTP server dan artisan commands
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

# Install additional dependencies for HTTP server
RUN apt-get update && apt-get install -y \
    curl \
    ca-certificates \
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
COPY docker/caddy/ /tmp/caddy/
COPY docker/php/ /tmp/php/

# Create necessary directories and install version-specific configurations
RUN PHP_SHORT_VERSION=$(echo "${PHP_VERSION}" | cut -d. -f1,2) && \
    mkdir -p /etc/caddy && \
    mkdir -p "/etc/php/${PHP_SHORT_VERSION}/fpm/pool.d" && \
    cp "/tmp/caddy/${PHP_SHORT_VERSION}/Caddyfile" /etc/caddy/Caddyfile && \
    cp "/tmp/php/${PHP_SHORT_VERSION}/php-fpm.conf" "/etc/php/${PHP_SHORT_VERSION}/fpm/pool.d/www.conf"

# Set permissions
RUN chown -R caddy:caddy /var/www/html

# Create directories
RUN mkdir -p /var/run/php

# Copy entrypoint script
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Expose port
EXPOSE 8080

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost:8080/up || exit 1

# Default command to start HTTP server
CMD ["/entrypoint.sh", "http"]

# Artisan stage - untuk menjalankan perintah artisan
FROM app AS artisan

# Copy entrypoint script
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Health check untuk artisan (detects php artisan processes)
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD pgrep -f "php" > /dev/null || exit 1

# Default command to show artisan help
CMD ["php", "artisan", "list"]
