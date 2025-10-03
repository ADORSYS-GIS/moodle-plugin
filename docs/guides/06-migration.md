
---

# 🚀 Moodle v3 ➜ v5 Migration

**Fresh Helm Install with Manual Database & moodledata Restore**

This guide describes how to:

1. **Back up** the current Moodle v3 site (database + moodledata).
2. **Deploy a brand-new Moodle v5 (latest) Helm release** with a new MariaDB instance.
3. **Manually restore** the old database dump and moodledata files into the new deployment.

---

## 0️⃣  Prerequisites

| Requirement | Notes                                     |
| ----------- | ----------------------------------------- |
| Kubernetes  | GKE or any K8s 1.24+ cluster              |
| Helm        | v3.x                                      |
| kubectl     | Configured for target cluster             |
| Storage     | Enough capacity for new PVCs plus backups |
| Tools       | `mysqldump`, `kubectl`, `helm`, `tar`     |
| UI          | Enable maintainance mode on the UI        |
|             | (optional)                                |
---

## 1️⃣  Back Up the Existing v3 Site

### 1.1 Identify Resources

```bash
kubectl get pods,pvc,svc -n moodle
# Note current PVCs, e.g.:
#  data-moodle-mariadb-0   (database)
#  moodle-moodle           (moodledata)
```

### 1.2 Database Dump

```bash
kubectl exec -n moodle -it <old-mariadb-pod> -- \
  mysqldump --single-transaction -u root -p bitnami_moodle > /tmp/moodle.sql
kubectl cp moodle/<old-mariadb-pod>:/tmp/moodle.sql ./moodle.sql
```

### 1.3 moodledata Archive

```bash
kubectl exec -n moodle -it <old-moodle-pod> -- \
  tar czf /bitnami/moodledata.tar.gz -C /bitnami/moodle .
kubectl cp moodle/<old-moodle-pod>:/bitnami/moodledata.tar.gz ./moodledata.tar.gz
```

> 💾 **Store these files safely** (e.g., GCS bucket or off-cluster disk snapshots).

---

## 2️⃣  Deploy a Fresh Moodle v5 + New MariaDB

Create (or reuse) the namespace:

```bash
kubectl create namespace moodle
```

### 2.1 `values.yaml`

Use this complete file—**edit all `CHANGEME_*` placeholders**:

```yaml
# ===========================
# Bitnami Moodle Helm values
# New Deployment – Manual Restore
# ===========================

image:
  repository: bitnami/moodle
  tag: 5.0.2-debian-12-r2     # exact 5.x tag

# --- Core Moodle credentials ---
moodleUsername: admin
moodlePassword: CHANGEME_ADMIN_PASSWORD
moodleEmail: admin@example.com

# --- Database (new Bitnami MariaDB) ---
mariadb:
  enabled: true
  architecture: standalone
  auth:
    rootPassword: CHANGEME_ROOT_PASS
    username: bn_moodle
    password: CHANGEME_MOODLE_DB_PASS
    database: bitnami_moodle
  primary:
    persistence:
      enabled: true
      storageClass: standard      # match your cluster
      size: 8Gi                   # adjust to DB size

# --- Moodle persistent storage ---
persistence:
  enabled: true
  storageClass: standard
  accessModes:
    - ReadWriteOnce
  size: 10Gi                      # adjust to moodledata size

# --- Service exposure ---
service:
  type: LoadBalancer
  port: 80

# --- PHP & resources ---
phpConfiguration: |
  upload_max_filesize = 128M
  post_max_size = 128M
resources:
  requests:
    cpu: 250m
    memory: 512Mi
  limits:
    cpu: 1
    memory: 1Gi

# --- Debug helpers ---
moodleSkipInstall: false
moodleDebug: true
```

### 2.2 Install

```bash
helm repo add bitnami https://charts.bitnami.com/bitnami
helm repo update
helm install moodle bitnami/moodle -n moodle -f values.yaml
kubectl get pods -n moodle -w   # wait until Running
```

---

## 3️⃣  Restore Old Data

### 3.1 Database Import

```bash
kubectl cp ./moodle.sql moodle/moodle-mariadb-0:/tmp/moodle.sql
kubectl exec -n moodle -it moodle-mariadb-0 -- \
  bash -c "mysql -u root -p'CHANGEME_ROOT_PASS' bitnami_moodle < /tmp/moodle.sql"
```

### 3.2 moodledata Copy

```bash
kubectl cp ./moodledata.tar.gz moodle/<new-moodle-pod>:/tmp/
kubectl exec -n moodle -it <new-moodle-pod> -- \
  bash -c "tar xzf /tmp/moodledata.tar.gz -C /bitnami/moodle && \
           chown -R bitnami:bitnami /bitnami/moodle"
```

---

## 4️⃣  Upgrade the Database Schema

```bash
kubectl exec -n moodle -it <new-moodle-pod> -- \
  php /bitnami/moodle/admin/cli/upgrade.php --non-interactive
```

---

## 5️⃣  Post-Migration Tasks

* Verify site via LoadBalancer:

  ```bash
  kubectl get svc -n moodle moodle
  ```
* Log in with **old admin credentials** (from the dump).
* Check: *Site administration → Notifications* for version **5.x**.

---

## ⚠️ Precautions & Caveats

### Backups

* Keep both the SQL dump and tarball **off-cluster**.
* Consider GCP disk snapshots for both old PVCs.

### Database

* Verify compatibility between old MariaDB and the new Bitnami version.
* Use `--single-transaction` to avoid inconsistent backups.

### Storage

* Ensure new PVC sizes ≥ old data sizes.
* If you change `storageClass`, verify performance/locking behavior.

### Application

* Moodle v5 requires PHP 8.1+.
  Audit plugins/themes for compatibility before restoring.
* Maintenance mode (optional) before schema upgrade:

  ```bash
  php /bitnami/moodle/admin/cli/maintenance.php --enable
  ```

### Rollback

* Keep old namespace/PVCs until the new site is stable.
* To revert, delete the new namespace and re-deploy the old Helm release using the saved PVC snapshots and `values.yaml`.

---

### ✅ Summary

* **Fresh Helm install**, not an in-place upgrade.
* **Manual DB & moodledata restore** from verified backups.
* **Post-upgrade schema migration** ensures Moodle v5 functionality.

This structured process provides a clean Moodle v5 deployment while preserving all courses, users, and files from your Moodle v3 site.
