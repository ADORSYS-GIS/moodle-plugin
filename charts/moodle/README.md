
# Moodle Helm Chart

This Helm chart deploys [Moodle](https://moodle.org/) on a Kubernetes cluster. It supports optional deployment of Bitnami's PostgreSQL chart for internal database provisioning or allows integration with external PostgreSQL databases such as GCP CloudS.



## ✨ Features

- Deploys Moodle LMS using Bitnami's chart
- Optional built-in PostgreSQL database using Bitnami PostgreSQL
- Easy integration with external PostgreSQL (CloudSQL, RDS)
- Works with local clusters (k3s, Minikube, KinD)
- Helm-native configuration for cloud-native deployments



## 🚀 Usage

### ✅ Prerequisites

- Helm 3.x
- A Kubernetes cluster (e.g., k3s via Multipass)
- Internet access to pull Bitnami charts



### 🔧 Install with PostgreSQL (default)

```bash
cd charts/moodle
helm dependency update
helm install my-moodle . --values values.yaml
```

### 🔁 Uninstall

```bash
helm uninstall my-moodle
```

## 🧪 Testing the Chart

### Helm Dry Run (Template)

You can validate the chart without deploying it:

```bash
helm template my-moodle . --values values.yaml
```

---

### Local Cluster (k3s via Multipass)

Install the chart:

```bash
helm install my-moodle . --values values.yaml
kubectl get pods
kubectl get svc my-moodle
```

If the service type is `LoadBalancer` and EXTERNAL-IP is `<pending>`, use port forwarding:

```bash
kubectl port-forward svc/my-moodle 8080:80
```

Then on your host machine, open your browser and visit:

[http://localhost:8080](http://localhost:8080)

---

#### Alternative: Access via Multipass VM IP (NodePort)
1. Upgrade your release:

```bash
helm upgrade my-moodle . --values values.yaml
```

2. Find the NodePort:

```bash
kubectl get svc my-moodle
```

3. Access Moodle at:

```
http://<Multipass-VM-IP>:<NodePort>
e.g., http://10.81.206.4:30897
```

---

## 📦 Dependencies

This chart includes the following Helm dependencies:

* [bitnami/moodle](https://artifacthub.io/packages/helm/bitnami/moodle)
* [bitnami/postgresql](https://artifacthub.io/packages/helm/bitnami/postgresql)

These are defined in `Chart.yaml` and pulled using:

```bash
helm dependency update
```




