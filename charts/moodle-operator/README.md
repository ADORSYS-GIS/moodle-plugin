# Moodle Operator — Helm Chart

This chart installs the **Moodle Kubernetes Operator** and its **CRD(s)**. The operator manages Moodle instances declaratively via `Moodle` custom resources.

## Install
```bash
helm install moodle-operator ./charts/moodle-operator
```

## Values
This chart uses the [bjw-s/app-template](https://bjw-s-labs.github.io/helm-charts/docs/app-template/) library as a dependency, aliased as `operator`. Configure all options under the top-level `operator:` key. The parent chart has no local `templates/`; resources are rendered by the dependency using your values.

## What this chart renders by default
- ServiceAccount for the operator
- ClusterRole and ClusterRoleBinding with permissions to manage Kubernetes and Moodle resources
- Deployment for the operator (1 replica) with secure defaults (non-root user, read-only root filesystem)
- ClusterIP Service exposing port `8080` (name: `http`)
- CRDs from `crds/`

Note: There is no metrics Service/port rendered by default, and no LOG_LEVEL or WATCH_NAMESPACE env vars are set.

## Customize
- Override image:
  ```yaml
  operator:
    controllers:
      main:
        containers:
          main:
            image:
              repository: your-registry/moodle-operator
              tag: "<version>"
  ```
- Disable the operator Deployment (CRDs only):
  ```yaml
  operator:
    controllers:
      main:
        enabled: false
  ```
