#  Moodle Backup Requirements Before Migration (v4 to v5)

This document outlines **what should be backed up**, depending on your Moodle setup (basic, production, or enterprise).

---

## 📦 What Needs to Be Backed Up

Moodle consists of three primary components:

1. **Database** – all Moodle content, users, grades, activities, settings
2. **moodledata** directory – user-uploaded files, caches, sessions
3. **Moodle codebase** – the actual Moodle software and plugins

In more advanced or enterprise Moodle setups, you may also need to back up scheduled tasks (cron jobs), configurations for external services (like LTI tools or SSO integrations), and any secrets or credentials used for APIs or third-party systems.

---

## 🛠️ Tiered Backup Strategy

### 🔹 Minimal Backup (Standard Moodle Installation)

| Component         | Why It Matters                             |
|------------------|---------------------------------------------|
| **Database**  | Core content: users, courses, grades, logs  |
|  **moodledata** | Uploaded files, session info, media         |
|  **Moodle code**| Required if plugins/themes were added       |
|  `config.php`   | Contains DB credentials, paths              |


##### 📚 References
- https://docs.moodle.org/501/en/Site_backup
---

### 🔹 Recommended Backup (Production Use)

Includes all from **Minimal**, plus:

| Component                  | Why It Matters                                 |
|---------------------------|-------------------------------------------------|
| Custom plugins/themes   | May not be recoverable from Moodle.org         |
|  Plugin-specific DB tables | Custom tables (e.g. `local_*`, `mod_*`)       |
|  Cron jobs               | Moodle's background tasks                      |
|  PHP & DB version        | Needed for rollback or staging parity          |
|  External tool configs   | LTI, OAuth2, etc.                               |

---

### 🔹 Full Backup (Enterprise / Cloud / Kubernetes)

Includes all from **Recommended**, plus:

| Component                       | Why It Matters                                      |
|--------------------------------|------------------------------------------------------|
|  Helm values / environment    | Critical for K8s-based deployments                   |
|  Redis/Memcached dumps        | If sessions or caching is persistent                 |
|  SSL certificates             | For HTTPS continuity                                |
|  Ingress / Load balancer config | Needed for web routing, DNS, TLS                  |
|  3rd-party credentials & secrets | GPT/OpenAI, Google integrations, SSO             |        |
|  Backup test in staging      | Validates integrity before production migration      |

---

## 📁 How to Locate Moodle Components

Check the `config.php` file in your Moodle root folder for paths and config:

```php
$CFG->dbname     // Moodle database name
$CFG->dataroot   // Path to moodledata folder
$CFG->wwwroot    // Moodle site URL
```

---
## Additionally
The Moodle code (wwwroot) is less important as a frequent backup , since it will only change when the actual code is changed through upgrades, addins and code tweaks. You can always get a copy of the standard Moodle code from http://download.moodle.org so you only have to backup the parts you added or changed yourself.