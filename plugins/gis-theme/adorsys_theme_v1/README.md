# Adorsys Theme v1

A custom Moodle theme plugin  built with Tailwind CSS, CssNano, Tailwind/Postcss and Webpack.

This repository contains the **adorsys_theme_v1** folder under `plugins/`, designed as a classical Moodle theme scaffold.

## Prerequisites

- Node.js (>=14)
- Yarn
- Docker & Docker Compose (see root `compose.yaml`)

## Setup & Build

1. Change into the theme folder:
   ```bash
   cd plugins/gis-theme/adorsys_theme_v1
   ```

2. Initialize dependencies and build assets:
   ```bash
   yarn install
   yarn build
   ```

## Project Structure

```
adorsys_theme_v1/
├── config.php                # Moodle theme definition
├── version.php
├── settings.php              # Admin settings stub
├── lib.php                   # Empty lib stub
├── package.json
├── tsconfig.json             # TypeScript configuration
├── webpack.config.ts         # SCSS/JS build config
├── postcss.config.mjs        # PostCSS plugins (e.g., Tailwind, autoprefixer)
│
├── scss/
│   └── style.scss            # Tailwind CSS entry
│
├── style/                    # (optional) Custom static CSS files
│
├── src/                      # Source files (JS/TS, components, etc.)
│   └── index.ts              # (example) Main entry for JS/TS
│                
│
├── templates/                # Mustache templates used by Moodle
│   └── some_template.mustache
│
├── layout/                   # Layout files for Moodle (e.g., columns, drawers)
│   └── some_layout_files.php
│
├── classes/                  # PHP classes (autoloaded by Moodle)
│   └── output/
│       └── renderer.php
│
├── lang/
│   └── en/
│       └── theme_adorsys_theme_v1.php
│
└── pix/                      # Theme images (logos, icons, etc.)

```

## Docker Integration

To mount the theme in your Moodle container, add to `docker-compose.yml` under the `moodle` service:
```yaml
volumes:
  - ./outputs/plugins/gis-theme/adorsys_theme_v1:/bitnami/moodle/theme/adorsys_theme_v1:ro
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


## Next Steps


## License

MIT