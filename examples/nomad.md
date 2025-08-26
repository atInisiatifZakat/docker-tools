# Nomad Job Specifications

Complete Nomad job specifications for Laravel applications using the Docker tools.

## HTTP Server Job

```hcl
job "laravel-http" {
  datacenters = ["dc1"]
  type        = "service"

  group "http" {
    count = 3

    network {
      port "http" {
        to = 8080
      }
    }

    service {
      name = "laravel-http"
      port = "http"
      
      check {
        type     = "http"
        path     = "/up"
        interval = "10s"
        timeout  = "3s"
      }
    }

    task "app" {
      driver = "docker"

      config {
        image = "myapp:http-php8.3"
        ports = ["http"]
      }

      env {
        APP_ENV = "production"
        PHP_VERSION = "8.3"
        DB_HOST = "database.service.consul"
        DB_DATABASE = "laravel"
        DB_USERNAME = "laravel"
        DB_PASSWORD = "secure_password"
      }

      resources {
        cpu    = 500
        memory = 512
      }

      restart {
        attempts = 3
        delay    = "15s"
        interval = "30m"
        mode     = "fail"
      }
    }
  }
}
```

## Queue Worker Job

```hcl
job "laravel-worker" {
  datacenters = ["dc1"]
  type        = "service"

  group "worker" {
    count = 5

    task "worker" {
      driver = "docker"

      config {
        image = "myapp:artisan-php8.3"
        command = ["php", "artisan", "queue:work", "--verbose", "--tries=3", "--timeout=90", "--memory=512"]
      }

      env {
        APP_ENV = "production"
        DB_HOST = "database.service.consul"
        DB_DATABASE = "laravel"
        DB_USERNAME = "laravel"
        DB_PASSWORD = "secure_password"
        QUEUE_CONNECTION = "redis"
        REDIS_HOST = "redis.service.consul"
      }

      resources {
        cpu    = 500
        memory = 1024
      }

      service {
        name = "laravel-worker"
        
        check {
          type     = "script"
          command  = "/bin/bash"
          args     = ["-c", "pgrep -f 'php artisan queue:work' > /dev/null"]
          interval = "30s"
          timeout  = "5s"
        }
      }

      restart {
        attempts = 5
        delay    = "10s"
        interval = "5m"
        mode     = "delay"
      }
    }
  }
}
```

## Scheduler Job

```hcl
job "laravel-scheduler" {
  datacenters = ["dc1"]
  type        = "service"

  group "scheduler" {
    count = 1  # Only one scheduler needed

    constraint {
      attribute = "${attr.unique.hostname}"
      operator  = "distinct_hosts"
      value     = "true"
    }

    task "scheduler" {
      driver = "docker"

      config {
        image = "myapp:artisan-php8.3"
        command = ["php", "artisan", "schedule:work"]
      }

      env {
        APP_ENV = "production"
        DB_HOST = "database.service.consul"
        DB_DATABASE = "laravel"
        DB_USERNAME = "laravel"
        DB_PASSWORD = "secure_password"
      }

      resources {
        cpu    = 200
        memory = 256
      }

      service {
        name = "laravel-scheduler"
        
        check {
          type     = "script"
          command  = "/bin/bash"
          args     = ["-c", "pgrep -f 'php artisan schedule:work' > /dev/null"]
          interval = "60s"
          timeout  = "5s"
        }
      }

      restart {
        attempts = 3
        delay    = "15s"
        interval = "30m"
        mode     = "fail"
      }
    }
  }
}
```

## Migration Batch Job

```hcl
job "laravel-migrate" {
  datacenters = ["dc1"]
  type        = "batch"

  group "migrate" {
    task "migrate" {
      driver = "docker"

      config {
        image = "myapp:artisan-php8.3"
        command = ["php", "artisan", "migrate", "--force"]
      }

      env {
        APP_ENV = "production"
        DB_HOST = "database.service.consul"
        DB_DATABASE = "laravel"
        DB_USERNAME = "laravel"
        DB_PASSWORD = "secure_password"
      }

      resources {
        cpu    = 200
        memory = 256
      }
    }
  }
}
```

## Database Seed Job

```hcl
job "laravel-seed" {
  datacenters = ["dc1"]
  type        = "batch"

  group "seed" {
    task "seed" {
      driver = "docker"

      config {
        image = "myapp:artisan-php8.3"
        command = ["php", "artisan", "db:seed", "--force"]
      }

      env {
        APP_ENV = "production"
        DB_HOST = "database.service.consul"
        DB_DATABASE = "laravel"
        DB_USERNAME = "laravel"
        DB_PASSWORD = "secure_password"
      }

      resources {
        cpu    = 300
        memory = 512
      }
    }
  }
}
```

## Cache Clear Job

```hcl
job "laravel-cache-clear" {
  datacenters = ["dc1"]
  type        = "batch"

  group "cache" {
    task "clear" {
      driver = "docker"

      config {
        image = "myapp:artisan-php8.3"
        command = ["php", "artisan", "cache:clear"]
      }

      env {
        APP_ENV = "production"
        CACHE_DRIVER = "redis"
        REDIS_HOST = "redis.service.consul"
      }

      resources {
        cpu    = 100
        memory = 128
      }
    }
  }
}
```

## Complete Multi-Job Configuration

```hcl
# laravel-complete.nomad
job "laravel-complete" {
  datacenters = ["dc1"]
  type        = "service"

  # HTTP Server Group
  group "http" {
    count = 3

    network {
      port "http" {
        static = 8080
        to     = 8080
      }
    }

    service {
      name = "laravel-http"
      port = "http"
      
      tags = [
        "http",
        "web",
        "laravel"
      ]

      check {
        type     = "http"
        path     = "/up"
        interval = "10s"
        timeout  = "3s"
      }
    }

    task "http" {
      driver = "docker"

      config {
        image = "myapp:http-php8.3"
        ports = ["http"]
      }

      env {
        APP_ENV = "production"
        APP_DEBUG = "false"
        DB_HOST = "database.service.consul"
        DB_DATABASE = "laravel"
        CACHE_DRIVER = "redis"
        SESSION_DRIVER = "redis"
        QUEUE_CONNECTION = "redis"
        REDIS_HOST = "redis.service.consul"
      }

      resources {
        cpu    = 500
        memory = 512
      }

      restart {
        attempts = 3
        delay    = "15s"
        interval = "30m"
        mode     = "fail"
      }
    }
  }

  # Worker Group
  group "worker" {
    count = 5

    task "worker" {
      driver = "docker"

      config {
        image = "myapp:artisan-php8.3"
        command = ["php", "artisan", "queue:work", "--verbose", "--tries=3", "--timeout=90", "--memory=512"]
      }

      env {
        APP_ENV = "production"
        DB_HOST = "database.service.consul"
        DB_DATABASE = "laravel"
        QUEUE_CONNECTION = "redis"
        REDIS_HOST = "redis.service.consul"
      }

      resources {
        cpu    = 500
        memory = 1024
      }

      service {
        name = "laravel-worker"
        
        tags = [
          "worker",
          "queue",
          "laravel"
        ]

        check {
          type     = "script"
          command  = "/bin/bash"
          args     = ["-c", "pgrep -f 'php artisan queue:work' > /dev/null"]
          interval = "30s"
          timeout  = "5s"
        }
      }

      restart {
        attempts = 5
        delay    = "10s"
        interval = "5m"
        mode     = "delay"
      }
    }
  }

  # Scheduler Group
  group "scheduler" {
    count = 1

    constraint {
      attribute = "${attr.unique.hostname}"
      operator  = "distinct_hosts"
      value     = "true"
    }

    task "scheduler" {
      driver = "docker"

      config {
        image = "myapp:artisan-php8.3"
        command = ["php", "artisan", "schedule:work"]
      }

      env {
        APP_ENV = "production"
        DB_HOST = "database.service.consul"
        DB_DATABASE = "laravel"
      }

      resources {
        cpu    = 200
        memory = 256
      }

      service {
        name = "laravel-scheduler"
        
        tags = [
          "scheduler",
          "cron",
          "laravel"
        ]

        check {
          type     = "script"
          command  = "/bin/bash"
          args     = ["-c", "pgrep -f 'php artisan schedule:work' > /dev/null"]
          interval = "60s"
          timeout  = "5s"
        }
      }

      restart {
        attempts = 3
        delay    = "15s"
        interval = "30m"
        mode     = "fail"
      }
    }
  }
}
```

## Deployment Scripts

### Deploy Script

```bash
#!/bin/bash
# deploy-nomad.sh

set -e

NOMAD_ADDR=${NOMAD_ADDR:-http://localhost:4646}
JOB_FILE=${1:-laravel-complete.nomad}

echo "🚀 Deploying Laravel app to Nomad..."
echo "Nomad Address: $NOMAD_ADDR"
echo "Job File: $JOB_FILE"

# Validate job
nomad job validate $JOB_FILE

# Plan deployment
echo "📋 Planning deployment..."
nomad job plan $JOB_FILE

# Deploy
echo "🚀 Running deployment..."
nomad job run $JOB_FILE

echo "✅ Deployment completed!"
echo "🔍 Check status with: nomad job status laravel-complete"
```

### Status Check Script

```bash
#!/bin/bash
# check-status.sh

echo "📊 Laravel Application Status"
echo "============================"

echo "📋 Job Status:"
nomad job status laravel-complete

echo ""
echo "🌐 HTTP Service Status:"
nomad service info laravel-http

echo ""
echo "⚡ Worker Service Status:"
nomad service info laravel-worker

echo ""
echo "⏰ Scheduler Service Status:"
nomad service info laravel-scheduler

echo ""
echo "📊 Allocation Status:"
nomad job allocs laravel-complete
```

## Usage

1. **Update configuration**: Modify job specifications to match your environment
2. **Deploy services**:
   ```bash
   nomad job run laravel-complete.nomad
   ```
3. **Run migrations**:
   ```bash
   nomad job run laravel-migrate.nomad
   ```
4. **Monitor deployment**:
   ```bash
   nomad job status laravel-complete
   nomad job allocs laravel-complete
   nomad logs -f <allocation_id>
   ```

## Best Practices

1. **Resource Allocation**: Set appropriate CPU and memory limits based on your workload
2. **Health Checks**: Implement comprehensive health checks for all services
3. **Service Discovery**: Use Consul for service discovery between components
4. **Single Scheduler**: Use constraints to ensure only one scheduler runs
5. **Restart Policies**: Configure appropriate restart policies for different service types
6. **Security**: Use Nomad ACLs and Vault integration for production deployments
