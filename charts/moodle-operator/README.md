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
- Role and RoleBinding (namespaced) with least-privilege permissions required by the controller
- Deployment for the operator (1 replica) with secure defaults (non-root user, read-only root filesystem)
- ClusterIP Service exposing port `8080` (name: `http`)
- CRDs from `crds/`

Note: There is no metrics Service/port rendered by default, and no LOG_LEVEL or WATCH_NAMESPACE env vars are set.

## RBAC

- Default: namespaced Role/RoleBinding with least-privilege
  - core: pods, services, endpoints, events, configmaps, secrets, persistentvolumeclaims -> get, list, watch
  - apps: deployments -> get, list, watch, create, update, patch, delete
  - moodle.adorsys.com: moodles, moodles/status, moodles/finalizers -> get, list, watch, update, patch

- Cluster-wide (opt-in):
  If you need cluster-scoped operation, explicitly switch the types:
  ```bash
  helm upgrade --install <release> . \
    --set operator.rbac.roles.operator.type=ClusterRole \
    --set operator.rbac.bindings.operator.type=ClusterRoleBinding
  ```
  And extend the RBAC rules via a values overlay as required by your environment.

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
