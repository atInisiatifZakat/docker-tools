# Laravel Docker Tools

[![🧪 Tests](https://github.com/atInisiatifZakat/docker-tools/workflows/🧪%20Tests/badge.svg)](https://github.com/atInisiatifZakat/docker-tools/actions?query=workflow%3A%22🧪%20Tests%22)
[![🐳 Docker Tests](https://github.com/atInisiatifZakat/docker-tools/workflows/🐳%20Docker%20Tests/badge.svg)](https://github.com/atInisiatifZakat/docker-tools/actions?query=workflow%3A%22🐳%20Docker%20Tests%22)
[![🔍 Code Quality](https://github.com/atInisiatifZakat/docker-tools/workflows/🔍%20Code%20Quality/badge.svg)](https://github.com/atInisiatifZakat/docker-tools/actions?query=workflow%3A%22🔍%20Code%20Quality%22)
[![🔄 Compatibility](https://github.com/atInisiatifZakat/docker-tools/workflows/🔄%20Compatibility/badge.svg)](https://github.com/atInisiatifZakat/docker-tools/actions?query=workflow%3A%22🔄%20Compatibility%22)
[![Latest Stable Version](https://poser.pugx.org/inisiatif/docker-tools/v/stable)](https://packagist.org/packages/inisiatif/docker-tools)
[![Total Downloads](https://poser.pugx.org/inisiatif/docker-tools/downloads)](https://packagist.org/packages/inisiatif/docker-tools)
[![License](https://poser.pugx.org/inisiatif/docker-tools/license)](https://packagist.org/packages/inisiatif/docker-tools)

A Laravel package that provides Docker tools and configurations for easy containerization of Laravel applications.

## Features

- 🐳 **Multi-stage Dockerfile** support for HTTP server, worker, and scheduler
- 🚀 **GitHub Workflows** for automated building and deployment
- ⚙️ **Docker Compose** configurations for development and production
- 🔧 **Automatic placeholder replacement** for Docker Hub username and app names
- 📦 **Easy publishing** with `doctool:publish` command
- 🎯 **Support for multiple deployment modes** (Docker Compose, Kubernetes)
- 🔒 **Built-in security** with proper file permissions and configurations

## Requirements

- PHP 8.0 or higher
- Laravel 9.x, 10.x, 11.x, or 12.x
- Docker (for using the generated configurations)

## Installation

You can install the package via Composer:

```bash
composer require inisiatif/docker-tools
```

## Usage

### Publishing Docker Configurations

Use the `doctool:publish` command to publish Docker configurations with automatic placeholder replacement:

```bash
php artisan doctool:publish
```

This command will:
1. Ask for your Docker Hub username
2. Ask for your application name
3. Publish all Docker configuration files
4. Replace placeholders with your actual values

### Direct Publishing (Advanced)

If you want to publish raw configuration files without placeholder replacement:

```bash
# With confirmation prompt
php artisan vendor:publish --tag=doctool-stubs

# Skip confirmation (assumes you understand you're publishing raw files)
php artisan vendor:publish --tag=doctool-stubs --force
```

### Building Docker Images

Use the included build script to create Docker images:

```bash
# Basic build
./bin/doctool --app-name myapp --app-version v1.0.0

# Build with registry
./bin/doctool --registry docker.io/username --app-name myapp --app-version v1.0.0

# Build for Kubernetes
./bin/doctool --mode kubernetes --app-name myapp --app-version v1.0.0

# Show help
./bin/doctool --help
```

## Published Files

The package publishes the following files:

### Docker Configuration
- `docker/Dockerfile` - Multi-stage Dockerfile
- `docker/entrypoint.sh` - Smart entrypoint script
- `docker/caddy/` - Caddy web server configurations
- `docker/php/` - PHP-FPM configurations
- `docker/supervisor/` - Supervisor configurations for multi-process containers

### Docker Compose
- `docker-compose.yml` - Development/production compose file
- `docker-compose.prod.yml` - Production-optimized compose file

### GitHub Workflows
- `.github/workflows/build-images.yml` - Automated Docker image building
- `.github/workflows/release.yml` - Release automation
- `.github/release-drafter.yml` - Release drafter configuration

## Configuration

### Supported PHP Versions
- PHP 8.2
- PHP 8.3

### Supported Deployment Modes
- **Docker Compose**: Traditional multi-container setup
- **Kubernetes**: Single-process containers with proper health checks

### Image Types
- **HTTP**: Web server with Caddy + PHP-FPM
- **Worker**: Background job processing
- **Scheduler**: Cron job handling

## Development

### Running Tests

```bash
composer test
```

### Test Coverage

```bash
composer test-coverage
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Nuradiyana](https://github.com/nuradiyana)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
