
# Moodle Helm Chart

This Helm chart deploys [Moodle](https://moodle.org/) on a Kubernetes cluster. It wraps the [Bitnami Moodle chart](https://github.com/bitnami/charts/tree/main/bitnami/moodle) and supports optional deployment of Bitnami's PostgreSQL chart for internal database provisioning, or integration with external PostgreSQL services .

---

## ✨ Features

* Deploys Moodle LMS using Bitnami's official chart
* Optional built-in PostgreSQL database using:
  - Bitnami PostgreSQL
  - Mariadb
* Easy integration with external PostgreSQL (CloudSQL, RDS)
* Persistent volume support
* Works with local clusters (k3s, Minikube, KinD)

---

## 🛠️ Requirements

* Helm 3.x
* Kubernetes 1.21+ (tested with k3s)
* Internet access to fetch Bitnami dependencies

---

## 🔧 Installation Options

### Option 1: External PostgreSQL (default)

This is the default approach using `values.yaml`, assuming an external database like CloudSQL or RDS:

```bash
cd charts/moodle
helm dependency build
helm install my-moodle . --values values.yaml
```

### Option 2: Internal PostgreSQL Or Mariadb (Bitnami dependency)

Use this if you want to deploy Moodle with an in-cluster PostgreSQL or Mariadb database (Bitnami):

```bash
helm dependency build
helm install  my-moodle . --values values.yaml --values values-mariadb.yaml --values values-postgres.yaml
```
#### NB
The database deployed will be based on one you enable as true.

``` postgresql/mariadb: 
   enabled: false
```  
---

## 🔁 Uninstall

```bash
helm uninstall my-moodle
```

---

## 🧪 Testing the Chart

### Dry Run (Template Only)

```bash
helm template  my-moodle . --values values.yaml --values values-mariadb.yaml --values values-postgres.yaml
```

This renders all manifests to stdout without deploying.


---

## 🖥️ Local Cluster Access (k3s via Multipass)

Install:

```bash
kubectl get pods
kubectl get svc my-moodle
```

If EXTERNAL-IP is `<pending>`, forward the port:

```bash
kubectl port-forward svc/my-moodle 8080:80
```

Then open:

[http://localhost:8080](http://localhost:8080)

---

### Alternative: Access via Multipass VM IP (NodePort)

1. Upgrade with `NodePort` enabled (in `values.yaml` ):

```bash
helm upgrade my-moodle . -f values-postgres.yaml
```

2. Get the NodePort:

```bash
kubectl get svc my-moodle
```

3. Open in browser:

```
http://<Multipass-VM-IP>:<NodePort>
e.g., http://10.81.206.4:30897
```

---

## 📦 Dependencies

This chart includes the following dependencies:

* [bitnami/moodle](https://artifacthub.io/packages/helm/bitnami/moodle)
* [bitnami/postgresql](https://artifacthub.io/packages/helm/bitnami/postgresql) (conditionally enabled)


They are defined in `Chart.yaml` and pulled via:

```bash
helm dependency update
```

---

## 🧾 Values Files

* `values.yaml` – for use with external databases (default)
* `values-postgres.yaml` – enables internal PostgreSQL (Bitnami)
* `values-mariadb.yaml`– used to pass mariadb autherntication values  

> 💡 Tip: Never hardcode production secrets in your values files. Use `--set`, `helm secrets`, or a CI/CD vault integration.

---
