# Moodle Helm Chart

This Helm chart wraps the [Bitnami Moodle chart](https://github.com/bitnami/charts/tree/main/bitnami/moodle) as a dependency, making it easier to install and configure Moodle — a popular open-source learning management system — on a Kubernetes cluster.

## 🚀 Features

- Installs Moodle using Bitnami's official Helm chart
- Uses an embedded MariaDB database (disabled)
- Persistent volume support
- Customizable values through a single `values.yaml`

---

## 🛠️ Requirements

- Helm 3.x
- Kubernetes 1.21+ (tested with  K3s)
- Internet access to fetch dependencies from Bitnami

---

## 🔧 Installation Options

### Option 1: External Database (default)

This is the default approach using `values.yaml`, assuming an external database:

```bash
cd charts/moodle
helm dependency build
helm install my-moodle . --values values.yaml
```

### Option 2: Internal MariaDB (Bitnami dependency)

```bash
helm dependency build
helm install my-moodle . \
  -n moodle \
  --create-namespace \
  -f values.yaml \
  -f values-mariadb.yaml
```
### Option 3: Internal PostgreSQL (Bitnami dependency)

```bash
helm dependency build
helm install my-moodle . \
  -n moodle \
  --create-namespace \
  -f values.yaml \
  -f values-postgres.yaml
```

***Note***: add the flag ``` --set global.security.allowInsecureImages=true ```  if you trust the custom image and understand the security/performance risks.



### Option 4: CNPG Cluster + External Database

1. **Build dependencies:**

```bash
helm dependency build
```

2. **Install the chart:**

```bash
helm install my-moodle .
```