# Adorsys Theme v1

A custom Moodle theme plugin  built with Tailwind CSS, CssNano, Tailwind/Postcss and Webpack.

This repository contains the **adorsys_theme_v1** folder under `plugins/`, designed as a classical Moodle theme scaffold.

## Prerequisites

- Node.js (>=18)
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
```
adorsys_theme_v1/
├── classes/                    # PHP classes (autoloaded by Moodle)
│   └── output/
│       └── renderer.php        # Renders the theme's output
├── config.php                  # Moodle theme definition
├── lang/                       # Language files for internationalization
│   └── en/
│       └── theme_adorsys_theme_v1.php # English language strings
├── layout/                     # Defines the page layouts for different Moodle contexts
│   └── some_layout_files.php   # (e.g., columns.php, default.php, login.php)
├── lib.php                     # Empty library stub (for theme-specific functions)
├── package.json                # Project dependencies and scripts
├── pix/                        # Theme images and assets
│   ├── favicon.ico             # Favicon for the theme
│   └── screenshot.png          # Screenshot for Moodle theme selector
├── postcss.config.mjs          # PostCSS configuration (e.g., Tailwind CSS, Autoprefixer)
├── README.md                   # This README file
├── settings.php                # Admin settings for the theme
├── src/                        # Source files (TypeScript, SCSS, etc.)
│   ├── assets/                 # Static assets like images or fonts
│   ├── index.ts                # Main TypeScript entry file
│   └── styles/                 # SCSS stylesheets
│       └── main.scss           # Main SCSS file
├── templates/                  # Mustache templates used by Moodle for rendering
│   ├── some_mustache_files.mustache # (e.g., columns.mustache, default.mustache)
│   └── partials/               # Reusable Mustache partials
│       └── some_partials.mustache # (e.g., footer.mustache, navbar.mustache)
├── tsconfig.json               # TypeScript configuration
├── version.php                 # Moodle plugin version details
├── webpack.config.ts           # Webpack configuration for asset bundling
└── yarn.lock                   # Yarn dependency lock file
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