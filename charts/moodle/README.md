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

## 📦 Installing the Chart

1. **Build dependencies:**

```bash
helm dependency build
```

2. **Install the chart:**

```bash
helm install my-moodle .
```