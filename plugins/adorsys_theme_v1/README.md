# Adorsys Theme v1

A custom Moodle theme plugin based on Boost, built with Tailwind CSS and Webpack.

This repository contains the **adorsys_theme_v1** folder under `plugins/`, designed as a classical Moodle theme scaffold following the [Creating a custom theme](https://docs.moodle.org/dev/Creating_a_custom_theme) guide.

## Prerequisites

- Node.js (>=14)
- Yarn
- Docker & Docker Compose (see root `compose.yaml`)

## Setup & Build

1. Change into the theme folder:
   ```bash
   cd plugins/adorsys_theme_v1
   ```

2. Initialize dependencies and build assets:
   ```bash
   yarn install
   yarn build
   ```

3. For development with live rebuilds:
   ```bash
   yarn dev
   ```

## Project Structure

```
adorsys_theme_v1/
├── config.php           # Moodle theme definition
├── version.php
├── settings.php         # Admin settings stub
├── lib.php              # Empty lib stub
├── package.json
├── webpack.config.js    # SCSS compilation
├── tailwind.config.js
├── postcss.config.js
├── scss/
│   └── style.scss       # Tailwind entry
├── style/
│   ├── all.css          # Compiled CSS
│   └── bundle.js        # JS bundle placeholder
│
├── lang/en/
│   └── theme_adorsys_theme_v1.php
└── pix/                 # Images & screenshot for theme picker
```

## Docker Integration

To mount the theme in your Moodle container, add to `docker-compose.yml` under the `moodle` service:
```yaml
volumes:
  - ./plugins/adorsys_theme_v1:/bitnami/moodle/theme/adorsys_theme_v1:ro
```
Then restart:
```bash
docker compose up -d
```
Finally, purge Moodle caches in the UI (Site administration → Development → Purge all caches) to see your theme.

## Demo

1. Start your Docker stack:
   ```bash
   docker compose up -d
   ```
2. Navigate to `http://localhost:8080/` (or your host’s mapped port).
3. In Site administration → Appearance → Theme selector, choose **Adorsys Theme v1** and confirm.
4. Observe inherited Boost layout; any SCSS changes will reload after `yarn dev`.

## Next Steps


## License

MIT