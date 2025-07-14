# Local Development Setup (Docker Compose)

This guide shows you how to spin up a complete local Moodle environment using Docker Compose, including custom PHP plugins and integration with Redis, MinIO, and MailDev.

## Prerequisites

- Docker and Docker Compose (v1.27+)
- Git clone of the mono-repo:

  ```bash
  git clone https://github.com/ourorg/moodle-platform.git
  cd moodle-platform
  ```

- Folder layout: ensure you have `./plugins/` populated with your local plugins.

## 1. Docker Compose File

Create or update `docker-compose.yml` at the repo root with the following content:

## 2. Explanation of Services

- **moodle**: Bitnami Moodle image configured to connect to MariaDB, Redis (sessions & cache), and MinIO (ObjectFS plugin).

- Mounts `././plugins` as read-only into `custom/plugins` so your local code is immediately available.
- **mariadb**: MariaDB for Moodle’s main database.
- **redis**: Redis for session storage and caching.
- **minio**: S3-compatible object store for Moodle’s file storage.
- **minio-init**: Initializes the MinIO bucket and policy (runs once).
- **maildev**: SMTP testing UI for outgoing emails.

## 3. Bring Up the Stack

```bash
# Start all services in detached mode
docker compose up -d

# Monitor logs
docker compose logs -f moodle
```

1. Open [http://localhost:8080](http://localhost:8080) in your browser.
2. Follow the Moodle web installer, connecting to the MariaDB database credentials above.
3. After installation completes, verify your local plugins are listed under **Site administration > Plugins > Local**.

## 4. Tips & Troubleshooting

- If you change plugin code in `./plugins`, Moodle may require a **purge cache**: append `?purge_caches=1` to any URL.
- To reset the database and Moodle data, run:

  ```bash
  docker-compose down -v && docker-compose up -d
  ```

- To debug SMTP, navigate to http://localhost:1080 and trigger a password reset email from Moodle.
