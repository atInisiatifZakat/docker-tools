# Kubernetes Deployments

Complete Kubernetes deployment manifests for Laravel applications using the Docker tools.

## HTTP Server Deployment

```yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: app-http
  labels:
    app: laravel-app
    component: http
spec:
  replicas: 3
  selector:
    matchLabels:
      app: laravel-app
      component: http
  template:
    metadata:
      labels:
        app: laravel-app
        component: http
    spec:
      containers:
      - name: http
        image: myapp:http-php8.3
        ports:
        - containerPort: 8080
          name: http
        env:
        - name: APP_ENV
          value: "production"
        - name: PHP_VERSION
          value: "8.3"
        - name: DB_HOST
          value: "database-service"
        - name: DB_DATABASE
          value: "laravel"
        resources:
          requests:
            memory: "256Mi"
            cpu: "200m"
          limits:
            memory: "512Mi"
            cpu: "500m"
        livenessProbe:
          httpGet:
            path: /up
            port: 8080
          initialDelaySeconds: 30
          periodSeconds: 10
        readinessProbe:
          httpGet:
            path: /up
            port: 8080
          initialDelaySeconds: 5
          periodSeconds: 5

---
apiVersion: v1
kind: Service
metadata:
  name: app-http-service
spec:
  selector:
    app: laravel-app
    component: http
  ports:
  - port: 80
    targetPort: 8080
  type: LoadBalancer
```

## Queue Worker Deployment

```yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: app-worker
  labels:
    app: laravel-app
    component: worker
spec:
  replicas: 5
  selector:
    matchLabels:
      app: laravel-app
      component: worker
  template:
    metadata:
      labels:
        app: laravel-app
        component: worker
    spec:
      containers:
      - name: worker
        image: myapp:artisan-php8.3
        command: ["php", "artisan", "queue:work", "--verbose", "--tries=3", "--timeout=90", "--memory=512"]
        env:
        - name: APP_ENV
          value: "production"
        - name: DB_HOST
          value: "database-service"
        - name: DB_DATABASE
          value: "laravel"
        - name: QUEUE_CONNECTION
          value: "redis"
        - name: REDIS_HOST
          value: "redis-service"
        resources:
          requests:
            memory: "256Mi"
            cpu: "100m"
          limits:
            memory: "1Gi"
            cpu: "500m"
        livenessProbe:
          exec:
            command:
            - pgrep
            - -f
            - "php artisan queue:work"
          initialDelaySeconds: 30
          periodSeconds: 30
```

## Scheduler Deployment

```yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: app-scheduler
  labels:
    app: laravel-app
    component: scheduler
spec:
  replicas: 1  # Only one scheduler needed
  selector:
    matchLabels:
      app: laravel-app
      component: scheduler
  template:
    metadata:
      labels:
        app: laravel-app
        component: scheduler
    spec:
      containers:
      - name: scheduler
        image: myapp:artisan-php8.3
        command: ["php", "artisan", "schedule:work"]
        env:
        - name: APP_ENV
          value: "production"
        - name: DB_HOST
          value: "database-service"
        - name: DB_DATABASE
          value: "laravel"
        resources:
          requests:
            memory: "128Mi"
            cpu: "50m"
          limits:
            memory: "256Mi"
            cpu: "200m"
        livenessProbe:
          exec:
            command:
            - pgrep
            - -f
            - "php artisan schedule:work"
          initialDelaySeconds: 30
          periodSeconds: 60
```

## Migration Job

```yaml
apiVersion: batch/v1
kind: Job
metadata:
  name: app-migrate
spec:
  template:
    spec:
      containers:
      - name: migrate
        image: myapp:artisan-php8.3
        command: ["php", "artisan", "migrate", "--force"]
        env:
        - name: APP_ENV
          value: "production"
        - name: DB_HOST
          value: "database-service"
        - name: DB_DATABASE
          value: "laravel"
      restartPolicy: Never
  backoffLimit: 4
```

## ConfigMap for Environment Variables

```yaml
apiVersion: v1
kind: ConfigMap
metadata:
  name: app-config
data:
  APP_ENV: "production"
  APP_DEBUG: "false"
  DB_CONNECTION: "mysql"
  DB_HOST: "database-service"
  DB_PORT: "3306"
  DB_DATABASE: "laravel"
  QUEUE_CONNECTION: "redis"
  REDIS_HOST: "redis-service"
  CACHE_DRIVER: "redis"
  SESSION_DRIVER: "redis"
```

## Secret for Sensitive Data

```yaml
apiVersion: v1
kind: Secret
metadata:
  name: app-secrets
type: Opaque
stringData:
  APP_KEY: "base64:your-app-key-here"
  DB_USERNAME: "laravel"
  DB_PASSWORD: "secure-password"
  REDIS_PASSWORD: "redis-password"
```

## Horizontal Pod Autoscaler

```yaml
apiVersion: autoscaling/v2
kind: HorizontalPodAutoscaler
metadata:
  name: app-http-hpa
spec:
  scaleTargetRef:
    apiVersion: apps/v1
    kind: Deployment
    name: app-http
  minReplicas: 3
  maxReplicas: 20
  metrics:
  - type: Resource
    resource:
      name: cpu
      target:
        type: Utilization
        averageUtilization: 70
  - type: Resource
    resource:
      name: memory
      target:
        type: Utilization
        averageUtilization: 80
```

## Ingress Configuration

```yaml
apiVersion: networking.k8s.io/v1
kind: Ingress
metadata:
  name: app-ingress
  annotations:
    nginx.ingress.kubernetes.io/rewrite-target: /
    cert-manager.io/cluster-issuer: "letsencrypt-prod"
spec:
  tls:
  - hosts:
    - myapp.example.com
    secretName: myapp-tls
  rules:
  - host: myapp.example.com
    http:
      paths:
      - path: /
        pathType: Prefix
        backend:
          service:
            name: app-http-service
            port:
              number: 80
```

## Complete Deployment Script

```bash
#!/bin/bash
# deploy-k8s.sh

set -e

NAMESPACE=${NAMESPACE:-laravel-app}
IMAGE_TAG=${IMAGE_TAG:-latest}

echo "🚀 Deploying Laravel app to Kubernetes..."
echo "Namespace: $NAMESPACE"
echo "Image Tag: $IMAGE_TAG"

# Create namespace if it doesn't exist
kubectl create namespace $NAMESPACE --dry-run=client -o yaml | kubectl apply -f -

# Apply configurations
kubectl apply -n $NAMESPACE -f configmap.yaml
kubectl apply -n $NAMESPACE -f secrets.yaml

# Run migration job
envsubst < migration-job.yaml | kubectl apply -n $NAMESPACE -f -
kubectl wait --for=condition=complete job/app-migrate -n $NAMESPACE --timeout=300s

# Deploy applications
envsubst < http-deployment.yaml | kubectl apply -n $NAMESPACE -f -
envsubst < worker-deployment.yaml | kubectl apply -n $NAMESPACE -f -
envsubst < scheduler-deployment.yaml | kubectl apply -n $NAMESPACE -f -

# Apply services and ingress
kubectl apply -n $NAMESPACE -f service.yaml
kubectl apply -n $NAMESPACE -f ingress.yaml
kubectl apply -n $NAMESPACE -f hpa.yaml

echo "✅ Deployment completed!"
echo "🔍 Check status with: kubectl get pods -n $NAMESPACE"
```

## Usage

1. **Update image references** in all YAML files to match your registry
2. **Configure environment variables** in ConfigMap and Secrets
3. **Apply the configurations**:
   ```bash
   kubectl apply -f configmap.yaml
   kubectl apply -f secrets.yaml
   kubectl apply -f http-deployment.yaml
   kubectl apply -f worker-deployment.yaml
   kubectl apply -f scheduler-deployment.yaml
   kubectl apply -f migration-job.yaml
   ```
4. **Monitor deployment**:
   ```bash
   kubectl get pods
   kubectl logs -f deployment/app-http
   kubectl describe service app-http-service
   ```

## Best Practices

1. **Resource Limits**: Always set appropriate CPU and memory limits
2. **Health Checks**: Implement proper liveness and readiness probes
3. **Horizontal Scaling**: Use HPA for automatic scaling based on metrics
4. **Secrets Management**: Store sensitive data in Kubernetes Secrets
5. **Single Scheduler**: Only run one scheduler replica to avoid conflicts
6. **Monitoring**: Set up proper logging and monitoring for production workloads
