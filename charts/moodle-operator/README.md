## Moodle CRD (Custom Resource Definition)

This project includes a Kubernetes Custom Resource Definition (CRD) that enables the creation and management of **Moodle** instances using custom resources.

### 📄 Location

The CRD file is located at: moodle-plugin/charts/moodle-operator/crds


### 🧩 What This CRD Does

It defines a new resource type: `Moodle`, which lets you configure Moodle deployments declaratively in YAML.

### 🧪 Example Moodle Instance

```yaml
apiVersion: moodle.adorsys.com/v1
kind: Moodle
metadata:
  name: my-moodle
spec:
  image: bitnami/moodle:5.0.3
  replicas: 2
  serviceType: LoadBalancer
  database:
    host: postgres.default.svc.cluster.local
    port: 5432
    user: moodle_user
    password: secret123
```

### Apply with ;
```

kubectl apply -f my-moodle.yaml

``` 