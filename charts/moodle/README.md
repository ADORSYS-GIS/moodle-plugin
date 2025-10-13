
# Moodle Helm Chart

This Helm chart deploys [Moodle](https://moodle.org/) on a Kubernetes cluster. It wraps the [Bitnami Moodle chart](https://github.com/bitnami/charts/tree/main/bitnami/moodle) and supports optional deployment of Bitnami's PostgreSQL chart or MariaDB charts for internal database provisioning, or integration with external PostgreSQL services (including CloudNativePG).

---

## ✨ Features

* Deploys Moodle LMS using Bitnami's official chart
* Optional built-in database using:
  - Bitnami PostgreSQL
  - Bitnami MariaDB
* Easy integration with external databases (CloudSQL, RDS, etc.)
* Optional CloudNativePG cluster deployment
* Persistent volume support
* Helm-native configuration for cloud-native deployments
* Works with local clusters (k3s, Minikube, KinD)

---

## 🛠️ Requirements

* Helm 3.x
* Kubernetes 1.21+ (tested with k3s)
* Internet access to fetch Bitnami dependencies
* CloudNativePG operator installed (for CNPG option)

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

### Option 4: CNPG Cluster + External Database

Deploy Moodle with a CloudNativePG cluster managed by this chart:

```bash
helm dependency build
helm install my-moodle . \
  -n moodle \
  --create-namespace \
  -f values.yaml \
  -f values-cnpg.yaml
```


 **Install CloudNativePG CRDs** (only once per cluster):

```bash
kubectl apply --server-side -f \
  https://raw.githubusercontent.com/cloudnative-pg/cloudnative-pg/release-1.27/releases/cnpg-1.27.0.yaml
```


> **Note**: Only enable one database type at a time. The database deployed will be based on which one you enable as `true` in your values file.

---

## 🔁 Uninstall

```bash
helm uninstall my-moodle
```

---

## 🧪 Testing the Chart

### Dry Run (Template Only)

```bash
# Test with default (external DB)
helm template my-moodle . -f values.yaml

# Test with MariaDB
helm template my-moodle . -f values-mariadb.yaml

# Test with internal PostgreSQL
helm template my-moodle . -f values.yaml -f values-postgres.yaml

# Test with external CNPG
helm template my-moodle . -f values.yaml -f values-cnpg.yaml
```

This renders all manifests to stdout without deploying.

---

## 🖥️ Local Cluster Access (k3s via Multipass)

### Monitor

```bash
# Check pod status
kubectl get pods -w

# Check services
kubectl get svc

# Check CNPG cluster (if enabled)
kubectl get postgresql.cnpg.io
```

### Access Options

#### Option 1: Port Forwarding
If EXTERNAL-IP is `<pending>`, forward the port:

```bash
kubectl port-forward svc/my-moodle 8080:80
```

Then open: [http://localhost:8080](http://localhost:8080)


#### Option 2: NodePort Access
1. Update with NodePort service type:


```bash
helm upgrade my-moodle . -f values.yaml --set moodle.service.type=NodePort
```

2. Get the NodePort:

```bash
kubectl get svc my-moodle
```

3. Open in browser:

```
http://<Multipass-VM-IP>:<NodePort>
e.g., http://10.81.206.74:30897
```

---


## 📦 Dependencies

This chart includes the following dependencies, which are managed in `Chart.yaml`:

* [bitnami/moodle](https://artifacthub.io/packages/helm/bitnami/moodle) – core Moodle deployment
* [bitnami/postgresql](https://artifacthub.io/packages/helm/bitnami/postgresql) – optional internal PostgreSQL database
* [bitnami/mariadb](https://artifacthub.io/packages/helm/bitnami/mariadb) – optional internal MariaDB database
* [bitnami/common](https://artifacthub.io/packages/helm/bitnami/common) – common Bitnami utilities
* [bitnami/cloudnative-pg](https://artifacthub.io/packages/helm/bitnami/cloudnative-pg) (conditionally enabled)

**Note**: CloudNativePG resources are deployed directly by this chart when `cnpg.enabled: true`.

To update dependencies, run:

```bash
helm dependency update
```

For more information on configuring these dependencies, see the respective values files and the [Bitnami Helm chart documentation](https://artifacthub.io/packages/search?kind=0&org=bitnami).

---

## 🧾 Values Files

* `values.yaml` – for use with external databases (default)
* `values-mariadb.yaml` – enables and configures internal MariaDB (Bitnami)
* `values-postgres.yaml` – enables and configures internal PostgreSQL (Bitnami)
* `values-cnpg.yaml` – deploys CNPG cluster and configures Moodle to use it


---


### Service Configuration

Configure the service type:

```yaml
moodle:
  service:
    type: LoadBalancer  # or NodePort, ClusterIP
```

---

## 🚀 Quick Start

1. **Navigate to the chart directory:**
   ```bash
   cd charts/moodle
   ```

2. **Update dependencies:**
   ```bash
   helm dependency update
   ```

3. **Install with MariaDB:**
   ```bash
   helm install my-moodle . -f values.yaml -f values-mariadb.yaml
   ```

3. **Install with internal PostgreSQL:**
   ```bash
   helm install my-moodle . -f values.yaml -f values-postgres.yaml
   ```

   **OR install with CNPG cluster:**
   ```bash
   helm install my-moodle . -f values.yaml -f values-cnpg.yaml
   ```

* `values-postgres.yaml` – enables internal PostgreSQL (Bitnami)
* `values-cnpg.yaml`– used to enable CloudNativePG(Bitnami)  


4. **Access Moodle:**
   ```bash
   kubectl port-forward svc/my-moodle 8080:80
   # Open http://localhost:8080 in your browser
   ```

5. **Default credentials (example):**
   - Username: `admin`
   - Password: `adorsys-gis`
   - Email: `gis-udm@adorsys.com`

---

## 🐛 Troubleshooting

### Common Issues

1. **Dependencies out of sync:**
   ```bash
   helm dependency update
   helm dependency build
   ```

2. **Port already in use:**
   ```bash
   kubectl port-forward svc/my-moodle 8081:80
   ```

3. **Database connection issues:**
   ```bash
   kubectl logs deployment/my-moodle
   kubectl logs statefulset/my-moodle-mariadb-0
   # If using internal PostgreSQL
   kubectl logs statefulset/my-moodle-postgresql-0
   # If using CNPG
   kubectl get postgresql.cnpg.io
   kubectl describe postgresql.cnpg.io my-moodle-pg
   ```

### Getting Help

* Check pod logs: `kubectl logs <pod-name>`
* Check service status: `kubectl get svc`
* Check persistent volumes: `kubectl get pvc`
* Check events: `kubectl get events --sort-by='.lastTimestamp'`
* Check CNPG resources: `kubectl get postgresql.cnpg.io`

---


