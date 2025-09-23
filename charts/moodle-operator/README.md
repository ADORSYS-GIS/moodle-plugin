# Moodle Operator — Helm Chart

This chart installs the **Moodle Kubernetes Operator** and its **CRD(s)**. The operator manages Moodle instances declaratively via `Moodle` custom resources.

## What's included
- CRDs in `crds/` (installed automatically by Helm before templates)
- Operator deployment/manifests (via `templates/`), using the **app-template** library chart for simplified configuration
- Service account with appropriate RBAC permissions
- Service for operator HTTP and metrics endpoints

> Note: application dependencies (DB, cache, storage) are **not bundled**. Bring your own PostgreSQL/MariaDB/Redis/etc. with separate charts and wire them via the `Moodle` resource spec.

## Requirements
- Kubernetes ≥ 1.28
- Helm ≥ 3.8
- Cluster permissions to install CRDs

## Install
```bash
helm install moodle-operator ./charts/moodle-operator
```

## Configuration

This chart uses the [app-template](https://bjw-s-labs.github.io/helm-charts/docs/app-template/) chart as a dependency with the alias `operator`. All app-template values must therefore be nested under the top-level `operator:` key.

### Operator Configuration
```yaml
operator:
  controllers:
    main:
      containers:
        main:
          image:
            repository: moodle-operator
            tag: "1.16.0"
          env:
            - name: LOG_LEVEL
              value: "info"
            - name: WATCH_NAMESPACE
              value: ""  # Empty = all namespaces
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
operator:
  service:
    main:
      ports:
        http:
          port: 8080      # Operator API
        metrics:
          port: 8081      # Metrics endpoint
```

### RBAC and ServiceAccount
```yaml
operator:
  serviceAccount:
    default:
      enabled: true

  rbac:
    roles:
      operator:
        type: ClusterRole
        rules:
          - apiGroups: [""]
            resources: ["pods", "services", "endpoints", "persistentvolumeclaims", "events", "configmaps", "secrets"]
            verbs: ["*"]
          - apiGroups: ["apps"]
            resources: ["deployments", "daemonsets", "replicasets", "statefulsets"]
            verbs: ["*"]
          - apiGroups: ["moodle.adorsys.com"]
            resources: ["*"]
            verbs: ["*"]
    bindings:
      operator:
        type: ClusterRoleBinding
        roleRef:
          identifier: operator
        subjects:
          - identifier: default  # binds the generated ServiceAccount
```

## Upgrade from Previous Versions

This chart has been updated to use the `app-template` library instead of the Bitnami `common` library for better maintainability and simplified configuration. The functionality remains the same, but the values structure follows app-template conventions.

## Migration from Bitnami Common

This chart has been migrated from using the Bitnami `common` library to the `bjw-s/app-template` v4.3.0 library for improved maintainability and modern Helm practices.

### Version Decision

We use **app-template v4.3.0** which provides:
- **Latest Features**: Access to the most recent app-template capabilities and improvements
- **Enhanced Security**: Updated security contexts and best practices
- **Better Performance**: Optimized template rendering and resource management
- **Future Compatibility**: Aligned with the latest Helm and Kubernetes standards

### Key Changes

- **Dependency**: Now uses `bjw-s/app-template` v4.3.0 instead of `bitnami/common`
- **Configuration**: Updated values.yaml structure to match app-template v4 schema
- **Security**: Enhanced security context with non-root user and read-only filesystem
- **RBAC**: Comprehensive cluster role permissions for operator functionality

## Metrics

The operator exposes a metrics endpoint on port 8081 (path `/metrics`). You can scrape these metrics with your preferred monitoring stack.
