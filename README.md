# Docker Tools

A comprehensive Docker toolkit for building and deploying Laravel applications with support for multiple PHP versions and deployment environments.

## Features

- **Multi-stage Docker builds** with HTTP and Artisan stages
- **PHP versions support**: 8.1, 8.2, 8.3, 8.4
- **Simple CMD override pattern** for maximum flexibility
- **Environment compatibility**: VM environments (EC2, Droplets) and cluster environments (K8s, Nomad)
- **Semantic versioning** with automated build and push
- **Zero-complexity deployment** with no environment variables required

## Quick Start

### 1. Install the Package

```bash
composer require your-vendor/docker-tools
```

### 2. Publish Docker Files

```bash
php artisan docker:publish
```

This creates:
- `docker/` directory with Dockerfile and configurations
- `docker-compose.yml` for development
- `docker-compose.prod.yml` for production

### 3. Build Images

```bash
# Build with default settings (PHP 8.3)
./bin/doctool

# Build with specific PHP version
./bin/doctool --php-version 8.4

# Build and push to registry
./bin/doctool --push myregistry/myapp

# Build with semantic versioning
./bin/doctool --push myregistry/myapp --version 1.2.3
```

## Usage Examples

### Quick Start

```bash
# HTTP Server
docker run -p 8080:8080 myapp:http-php8.3

# Queue Worker
docker run myapp:artisan-php8.3 php artisan queue:work --verbose --tries=3

# Scheduler  
docker run myapp:artisan-php8.3 php artisan schedule:work

# Any Custom Command
docker run myapp:artisan-php8.3 php artisan migrate --force
```

📋 **For complete deployment examples see**: [examples/](examples/)
- [Docker Run Commands](examples/docker-run.md) - Single server deployment
- [Docker Compose](examples/docker-compose.md) - VM environments (EC2, Droplets)  
- [Kubernetes](examples/kubernetes.md) - Cloud-native deployments
- [Nomad](examples/nomad.md) - HashiCorp stack deployments

## Docker Images

The toolkit builds two types of images:

### HTTP Image (`myapp:http-php8.3`)
- **Purpose**: Web server with PHP-FPM + Caddy
- **Entrypoint**: Automatically starts HTTP services
- **Use cases**: Web requests, API endpoints
- **Port**: 8080

### Artisan Image (`myapp:artisan-php8.3`)  
- **Purpose**: CLI operations and background tasks
- **Entrypoint**: Pure command execution via CMD override
- **Use cases**: Queue workers, schedulers, migrations, custom commands
- **Port**: None (CLI only)

## Build Tool (`bin/doctool`)

The build script provides semantic versioning and registry management:

```bash
Usage: ./bin/doctool [OPTIONS]

Options:
  --php-version VERSION    PHP version to use (8.1, 8.2, 8.3, 8.4)
                          Default: 8.3
                          
  --app-name NAME         Application name for image prefix
                          Default: current directory name
                          
  --push REGISTRY         Build and push to registry
                          Example: --push myregistry/myapp
                          
  --version VERSION       Semantic version (major.minor.patch)
                          Example: --version 1.2.3
                          Auto-generates: latest, 1.2.3, 1.2, 1
                          
  --help                  Show this help message

Examples:
  ./bin/doctool                                    # Build locally
  ./bin/doctool --php-version 8.4                 # Build with PHP 8.4
  ./bin/doctool --push myregistry/myapp            # Build and push
  ## PHP Version Support

All major PHP versions are supported with version-specific optimizations:

- **PHP 8.1**: Stable LTS with proven compatibility
- **PHP 8.2**: Performance improvements and new features  
- **PHP 8.3**: Latest stable with enhanced performance
- **PHP 8.4**: Cutting-edge features and optimizations

Each version includes:
- Optimized PHP-FPM configuration
- Version-specific extensions
- Performance tuning for container environments

## Environment Compatibility

### VM Environments
- **AWS EC2**: Full support with Docker and Docker Compose
- **DigitalOcean Droplets**: Native Docker support
- **Azure VMs**: Compatible with container runtimes
- **Google Compute Engine**: Works with Docker and containerd
- **On-premises VMs**: Any Linux distribution with Docker

### Cluster Environments  
- **Kubernetes**: Native support with health checks and resource management
- **Nomad**: Full job specification support
- **Docker Swarm**: Service deployment and scaling
- **Amazon ECS**: Task definitions and service management
- **Azure Container Instances**: Direct container deployment

## Testing Compatibility

Run the comprehensive compatibility test:

```bash
# Build images first
./bin/doctool

# Run compatibility tests
./test-compatibility.sh
```

Tests cover:
- Direct Docker run commands
- Docker Compose scenarios  
- Kubernetes simulation
- Multi-PHP version support
- Environment variable handling

## File Structure

After running `php artisan docker:publish`:

```
project/
├── docker/
│   ├── Dockerfile              # Multi-stage build definition
│   ├── entrypoint.sh          # Simple service detection
│   ├── caddy/
│   │   └── 8.x/
│   │       └── Caddyfile      # Web server configuration
│   └── php/
│       ├── opcache.ini        # PHP optimization
│       ├── php.ini           # PHP configuration
│       └── 8.x/
│           └── php-fpm.conf  # PHP-FPM pool configuration
├── docker-compose.yml         # Development composition
├── docker-compose.prod.yml    # Production composition
├── bin/
│   └── doctool               # Build and deployment script
└── examples/
    ├── README.md            # Deployment examples index
    ├── docker-run.md        # Direct Docker run commands
    ├── docker-compose.md    # Docker Compose for VMs
    ├── kubernetes.md        # Kubernetes manifests  
    └── nomad.md             # Nomad job specifications
```
## Configuration

The images support standard Laravel environment variables and PHP customizations.

📋 **For detailed configuration examples see**: [examples/](examples/)

### Key Environment Variables

```bash
# Essential Laravel settings
APP_ENV=production
APP_KEY=base64:your-key-here
DB_HOST=database
DB_DATABASE=laravel
QUEUE_CONNECTION=redis
REDIS_HOST=redis

# PHP optimizations  
PHP_MEMORY_LIMIT=512M
PHP_OPCACHE_ENABLE=1
```

## Best Practices

1. **Use specific versions**: Always tag with semantic versions
2. **Resource limits**: Set appropriate CPU and memory limits  
3. **Health checks**: Implement proper liveness and readiness probes
4. **Single scheduler**: Only run one scheduler instance
5. **Monitoring**: Use proper logging and monitoring for production

## Troubleshooting

### Quick Debugging

```bash
# Check logs
docker logs <container_name>

# Interactive debugging
docker run -it myapp:artisan-php8.3 bash

# Test health endpoint
curl http://localhost:8080/up
```

📋 **For detailed troubleshooting see**: [examples/](examples/)

### Getting Help

1. Check the [deployment examples](examples/) for your target platform
2. Run the compatibility test script: `./test-compatibility.sh`
3. Review Docker logs for error messages
4. Verify environment variables are set correctly

## Contributing

1. Fork the repository
2. Create a feature branch
3. Add tests for new functionality
4. Ensure all tests pass
5. Submit a pull request

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).
