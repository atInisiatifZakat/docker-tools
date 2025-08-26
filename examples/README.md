# Deployment Examples Index

This directory contains comprehensive deployment examples for different target environments and orchestration platforms.

## 📂 Available Examples

### [🐳 Docker Run Commands](docker-run.md)
Direct Docker run commands for simple deployments without orchestration:
- Basic HTTP server and artisan commands
- Multi-PHP version support (8.1, 8.2, 8.3, 8.4)
- Network configurations and resource management
- Environment-specific setups (dev, staging, production)
- Quick deployment scripts

**Best for**: Development, testing, simple production deployments on single servers

### [🐙 Docker Compose](docker-compose.md) 
Docker Compose configurations for VM environments:
- Development and production environments
- Docker Swarm cluster configurations
- Complete service orchestration with databases
- Health checks, scaling, and resource management
- Deploy scripts and service management

**Best for**: VM environments (EC2, Droplets, VMs), Docker Swarm clusters

### [☸️ Kubernetes](kubernetes.md)
Complete Kubernetes deployment manifests:
- HTTP server, worker, and scheduler deployments
- Services, ingress, and autoscaling configurations
- ConfigMaps, secrets, and persistent storage
- Migration jobs and health checks
- Production-ready manifests with best practices

**Best for**: Kubernetes clusters, cloud-native deployments, enterprise environments

### [🎯 Nomad](nomad.md)
HashiCorp Nomad job specifications:
- HTTP server, worker, and scheduler jobs
- Service discovery and health checks
- Resource allocation and constraints
- Batch jobs for migrations and maintenance
- Multi-job configurations and deployment scripts

**Best for**: HashiCorp stack, hybrid cloud, edge computing, Consul/Vault integration

## 🎯 Choosing the Right Deployment Method

### Single Server / VM Environments
- **AWS EC2, DigitalOcean Droplets, Azure VMs**: Use [Docker Compose](docker-compose.md)
- **Simple testing/development**: Use [Docker Run](docker-run.md)
- **Production with multiple services**: Use [Docker Compose](docker-compose.md) with production configuration

### Container Orchestration Platforms
- **Kubernetes (EKS, GKE, AKS)**: Use [Kubernetes](kubernetes.md) manifests
- **HashiCorp Nomad**: Use [Nomad](nomad.md) job specifications
- **Docker Swarm**: Use [Docker Compose](docker-compose.md) with Swarm mode

### Cloud Services
- **AWS ECS**: Adapt [Docker Compose](docker-compose.md) or [Kubernetes](kubernetes.md) patterns
- **Google Cloud Run**: Use [Docker Run](docker-run.md) patterns for single containers
- **Azure Container Instances**: Use [Docker Run](docker-run.md) or [Docker Compose](docker-compose.md)

## 🚀 Quick Start Guide

1. **Choose your target environment** from the options above
2. **Open the relevant guide** and copy the configuration
3. **Update environment variables** to match your setup
4. **Modify image names** to match your Docker registry
5. **Deploy using the provided commands and scripts**

## 🏗️ Image Architecture

All examples use the simplified 2-image architecture:

### HTTP Image (`myapp:http-php8.3`)
- **Purpose**: Web server with PHP-FPM + Caddy
- **Use cases**: Web requests, API endpoints
- **Port**: 8080
- **Auto-starts**: HTTP services

### Artisan Image (`myapp:artisan-php8.3`)
- **Purpose**: CLI operations and background tasks  
- **Use cases**: Queue workers, schedulers, migrations, custom commands
- **CMD Override**: Pure command execution pattern
- **No additional setup**: Just specify the command to run

## 🔧 Common Patterns

### Environment Variables
All deployments use standard Laravel environment variables:
```bash
APP_ENV=production
APP_DEBUG=false
DB_HOST=database
DB_DATABASE=laravel
QUEUE_CONNECTION=redis
REDIS_HOST=redis
```

### Service Scaling
- **HTTP servers**: Scale horizontally (3-10+ replicas)
- **Queue workers**: Scale based on workload (5-20+ replicas)
- **Schedulers**: Always single replica (1 only)

### Health Checks
- **HTTP**: `GET /up` endpoint check
- **Workers**: Process check `pgrep -f "php artisan queue:work"`
- **Scheduler**: Process check `pgrep -f "php artisan schedule:work"`

## 📖 Additional Resources

- **Build Tool**: See [`../bin/doctool`](../bin/doctool) for image building
- **Compatibility Testing**: See [`../test-compatibility.sh`](../test-compatibility.sh)
- **Main Documentation**: See [`../README.md`](../README.md)

## 💡 Best Practices

1. **Always use specific image tags** in production (not `latest`)
2. **Set resource limits** appropriate for your workload
3. **Implement health checks** for all services
4. **Use secrets management** for sensitive data
5. **Monitor and log** all services appropriately
6. **Run only one scheduler** to avoid duplicate executions
7. **Scale workers** based on queue load and processing time

## 🆘 Getting Help

If you need help with deployments:
1. Check the specific deployment guide for your platform
2. Run the compatibility test script: `./test-compatibility.sh`
3. Review container logs for error messages
4. Verify environment variables are set correctly
5. Test with simple `docker run` commands first

Each deployment example includes troubleshooting sections and best practices specific to that platform.
