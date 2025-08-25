# Moodle Operator — Helm Chart

This chart installs the **Moodle Kubernetes Operator** and its **CRD(s)**. The operator manages Moodle instances declaratively via `Moodle` custom resources.

## What’s included
- CRDs in `crds/` (installed automatically by Helm before templates)
- Operator deployment/manifests (via `templates/`), using the Bitnami **common** library chart for helpers

> Note: application dependencies (DB, cache, storage) are **not bundled**. Bring your own PostgreSQL/MariaDB/Redis/etc. with separate charts and wire them via the `Moodle` resource spec.

## Requirements
- Kubernetes ≥ 1.23
- Helm ≥ 3.8
- Cluster permissions to install CRDs

## Install
```bash
helm install moodle-operator ./charts/moodle-operator
