# 📘 How to Install and Use the Base Theme Plugin

This guide will help you install the **Adorsys Theme v1** theme into your Moodle site.

## ✅ Prerequisites

- A working Moodle instance (tested with Moodle 5.x or later)

- Access to the Moodle server (via SFTP, SSH, or terminal)

- Admin access to the Moodle UI

- Node.js (>=18)

- Yarn

- Docker & Docker Compose (see root `compose.yaml`)

## 🧩 Mounting steps for Developers

### Setup & Build

1. Change into the theme folder:
   ```bash
   cd plugins/gis-theme/adorsys_theme_v1
   ```

2. Initialize dependencies and build assets:
   ```bash
   yarn install
   yarn build
   ```
   
 ### Docker Integration

To mount the theme in your Moodle container, add to `docker-compose.yml`found at the root of the directory, under the `moodle` service:
```yaml
volumes:
  - ./outputs/plugins/gis-theme/adorsys_theme_v1:/bitnami/moodle/theme/adorsys_theme_v1:ro
```

## Demo

1. Start your Docker stack:
   ```bash
   docker compose up -d
   ```
2. Navigate to `http://localhost:8080/` (or your host’s mapped port).

3. Purge Moodle caches in the UI (Site administration → Development → Purge all caches) to see your theme.

4. In Site administration → Appearance → Theme selector, choose **Adorsys Theme v1** and confirm.