
---

````markdown
# 🚀 Moodle v3 → v5 Migration Guide (GCP GKE / k3s)

This guide explains how to migrate an existing **Bitnami Moodle Helm deployment** from **version 3.x** to **version 5.x** while preserving all courses, users, and files. It works for **GKE** or local k3s/Multipass setups.

---

## 📋 Requirements

- `kubectl` and `helm` installed and configured for your cluster.
- Bitnami Moodle Helm chart ≥ 19.x (for Moodle 5.x).
- Existing PVCs for:
  - Moodle application data (`moodledata`)
  - MariaDB database
- Ability to pull `bitnami/moodle:5.0.x` image.
- Maintenance window (site downtime required).

---

## 🛡️ Backups (Critical)

Before starting, back up **everything**.

### 1. Database Backup

```bash
kubectl exec -n moodle -it moodle-mariadb-0 -- \
  mysqldump -u bn_moodle -p bitnami_moodle > moodle-db-backup.sql
````

Enter the MariaDB password from your Helm `values.yaml`.

### 2. Moodle Files & Data Backup

For large volumes (avoiding `kubectl cp` timeouts):

#### Step A: Archive inside the pod

```bash
kubectl exec -n moodle -it moodle-5d86c9df5b-hsbhf -- bash

# Archive Moodle code
tar czf /tmp/moodle-backup-files.tar.gz -C /bitnami moodle

# Archive Moodle data
tar czf /tmp/moodle-backup-moodledata.tar.gz -C /bitnami moodledata

exit
```

#### Step B: Copy archives to host

```bash
kubectl cp -n moodle moodle-5d86c9df5b-hsbhf:/tmp/moodle-backup-files.tar.gz ./moodle-backup-files.tar.gz
kubectl cp -n moodle moodle-5d86c9df5b-hsbhf:/tmp/moodle-backup-moodledata.tar.gz ./moodle-backup-moodledata.tar.gz
```

#### Step C: Extract locally (optional verification)

```bash
tar xzf ./moodle-backup-files.tar.gz
tar xzf ./moodle-backup-moodledata.tar.gz
```

> This method avoids streaming each file individually and prevents “connection reset by peer” errors.

### 3. Helm Values & Secrets Backup

```bash
helm get values moodle -n moodle > current-values.yaml
```

### 4. TLS / Config Backup

Back up any `ConfigMap` or `Secret` containing custom certificates or settings.

---

## 🔧 Migration Steps

### 1. Prepare `values-upgrade.yaml`

```yaml
image:
  repository: bitnami/moodle
  tag: 5.0.2-debian-12-r2

moodleSkipInstall: true    # prevent fresh install
moodleDebug: true          # optional

service:
  type: LoadBalancer
  port: 80

mariadb:
  enabled: true
  auth:
    rootPassword: <same-root-password>
    username: bn_moodle
    password: <same-user-password>
    database: bitnami_moodle
  primary:
    persistence:
      enabled: true
      existingClaim: <database-pvc-name>   # reuse DB PVC

persistence:
  enabled: true
  existingClaim: <moodledata-pvc-name>    # reuse moodledata PVC
```

> Replace `<database-pvc-name>` and `<moodledata-pvc-name>` with your existing PVC names:
>
> ```bash
> kubectl get pvc -n moodle
> ```

### 2. Enable Maintenance Mode (optional)

In your current site (v3):

```
Site administration → Server → Maintenance mode → Enable
```

### 3. Upgrade via Helm

```bash
helm upgrade 
  -n moodle -f values-upgrade.yaml
```

* Keeps existing MariaDB and Moodle data PVCs.
* Uses new Moodle 5.x image.
* Skips fresh install (`moodleSkipInstall: true`).

### 4. Complete Web-Based Database Upgrade

1. Open the Moodle URL (LoadBalancer or Ingress).
2. Moodle detects existing database → **Database upgrade required**.
3. Follow prompts to upgrade the schema.

### 5. Disable Maintenance Mode

```
Site administration → Server → Maintenance mode → Disable
```

---

## ⚠️ Caveats & Tips

* **Downtime**: Required for upgrade.
* **Passwords**: Must match existing DB.
* **PVC names**: Double-check `existingClaim`; a typo creates a new empty PVC.
* **Chart changes**: Bitnami charts may require new settings in newer versions.
* **k3s / Multipass**: Use tar-based backup to avoid `kubectl cp` timeouts.

---

## ✅ Verification Checklist

* `kubectl get pods -n moodle` → all `Running`.
* Open site → version shows **5.x** in *Site administration → Notifications*.
* Browse courses and files → data integrity confirmed.

---

> **Summary**
> This process upgrades Moodle v3 → v5, reusing existing MariaDB and `moodledata` PVCs. The **critical steps** are:
>
> * Backup DB, Moodle code, and Moodledata using tar inside pod.
> * Set `moodleSkipInstall: true`.
> * Correct `existingClaim` in `values-upgrade.yaml`.

```

---


```
