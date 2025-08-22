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
  name: my-moodle-cr
  namespace: default
spec:
  image: bitnami/moodle:latest
  replicas: 4
  serviceType: ClusterIP
  pvcName: my-moodle-pvc
  database:
    host: my-database-postgresql
    port: 5432
    user: bn_moodle
    password: secret
    type: pgsql
    name: bitnami_moodle  

```

### Apply with ;
```

kubectl apply -f my-moodle.yaml

``` 