# Moodle Operator — Helm Chart

This chart installs the **Moodle Kubernetes Operator** and its **CRD(s)**. The operator manages Moodle instances declaratively via `Moodle` custom resources.

## What's included
- CRDs in `crds/` (installed automatically by Helm before templates)
- Operator deployment/manifests (via `templates/`), using the **app-template** library chart for simplified configuration
- Service account with appropriate RBAC permissions
- Service for operator HTTP and metrics endpoints

> Note: application dependencies (DB, cache, storage) are **not bundled**. Bring your own PostgreSQL/MariaDB/Redis/etc. with separate charts and wire them via the `Moodle` resource spec.

## Requirements
- Kubernetes ≥ 1.23
- Helm ≥ 3.8
- Cluster permissions to install CRDs

## Install
```bash
helm install moodle-operator ./charts/moodle-operator
```

## Configuration

The chart uses the [app-template](https://bjw-s-labs.github.io/helm-charts/docs/app-template/) library for simplified configuration. Key configuration options:

### Operator Configuration
```yaml
controllers:
  main:
    containers:
      main:
        image:
          repository: moodle-operator
          tag: "1.16.0"
        env:
          LOG_LEVEL: "info"
          WATCH_NAMESPACE: ""  # Empty = all namespaces
        resources:
          limits:
            cpu: 500m
            memory: 512Mi
          requests:
            cpu: 100m
            memory: 128Mi
```

### Service Configuration
```yaml
service:
  main:
    ports:
      http:
        port: 8080      # Operator API
      metrics:
        port: 8081      # Prometheus metrics
```

### RBAC Configuration
```yaml
rbac:
  main:
    enabled: true
    rules:
      - apiGroups: [""]
        resources: ["pods", "services", "endpoints", "persistentvolumeclaims", "events", "configmaps", "secrets"]
        verbs: ["*"]
      - apiGroups: ["apps"]
        resources: ["deployments", "daemonsets", "replicasets", "statefulsets"]
        verbs: ["*"]
      - apiGroups: ["moodle.io"]
        resources: ["*"]
        verbs: ["*"]
```

## Upgrade from Previous Versions

This chart has been updated to use the `app-template` library instead of the Bitnami `common` library for better maintainability and simplified configuration. The functionality remains the same, but the values structure follows app-template conventions.

## Monitoring

The operator exposes Prometheus metrics on port 8081. You can scrape these metrics for monitoring operator health and performance.
