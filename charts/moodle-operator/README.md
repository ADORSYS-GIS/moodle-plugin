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
{{ ... }}
      - apiGroups: ["moodle.io"]
        resources: ["*"]
        verbs: ["*"]
```

## Upgrade from Previous Versions

This chart has been updated to use the `app-template` library instead of the Bitnami `common` library for better maintainability and simplified configuration. The functionality remains the same, but the values structure follows app-template conventions.

## Migration from Bitnami Common

This chart has been migrated from using the Bitnami `common` library to the `bjw-s/app-template` v3.5.0 library for improved maintainability and modern Helm practices.

### Version Decision

We use **app-template v3.5.0** rather than the latest v4.x series due to:
- **Compatibility**: v4.x has breaking changes that cause template rendering issues
- **Stability**: v3.5.0 is proven stable and well-tested in production environments  
- **Feature Completeness**: v3.5.0 provides all necessary features for operator deployment

### Key Changes

- **Dependency**: Now uses `bjw-s/app-template` v3.5.0 instead of `bitnami/common`
- **Configuration**: Updated values.yaml structure to match app-template v3 schema
- **Security**: Enhanced security context with non-root user and read-only filesystem
- **Monitoring**: Built-in Prometheus ServiceMonitor support
- **RBAC**: Comprehensive cluster role permissions for operator functionality

## Monitoring

The operator exposes Prometheus metrics on port 8081. You can scrape these metrics for monitoring operator health and performance.
