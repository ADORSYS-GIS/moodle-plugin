# Adorsys Theme v1

A custom Moodle theme plugin  built with Tailwind CSS, CssNano, Tailwind/Postcss and Webpack.

This repository contains the **adorsys_theme_v1** folder under `plugins/`, designed as a classical Moodle theme scaffold.

## Prerequisites

- Node.js (>=24)
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
├── amd/                        # Asynchronous Module Definition (JS)
│   └── src/                    # JavaScript source files
├── classes/                    # PHP classes (autoloaded by Moodle)
│   └── output/
│       └── renderer.php        # Renders the theme's output
├── config.php                  # Moodle theme definition
├── lang/                       # Language files for internationalization
│   └── en/
│       └── theme_adorsys_theme_v1.php # English language strings
├── layout/                     # Moodle layout files
│   ├── columns.mustache        # Layout templates
│   ├── login.php               # Login page layout
│   └── ...
├── lib.php                     # Core theme functions & asset callbacks
├── package.json                # NPM configuration
├── pages/                      # Custom PHP pages (e.g. Terms of Service)
│   └── tos.php
├── pix/                        # Images & Icons
│   ├── favicon.ico
│   └── screenshot.png
├── postcss.config.mjs          # PostCSS / Tailwind configurations
├── README.md                   # Documentation
├── scss/                       # SCSS source files
├── settings.php                # Admin settings definition
├── src/                        # TypeScript Source
│   ├── index.ts                # Main entry point
│   └── styles/                 # Theme styles
├── style/                      # Compiled CSS output (generated)
├── templates/                  # Mustache templates
│   ├── drawers.mustache        # Navigation drawers
│   ├── login.mustache          # Login template
│   └── ...
├── tsconfig.json               # TypeScript config
├── version.php                 # Plugin version & dependencies
├── webpack.config.ts           # Build configuration
└── yarn.lock                   # Dependency lockfile
```

## Docker Integration

To mount the theme in your Moodle container, add to `docker-compose.yml` under the `moodle` service:
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


## Alternatively

### 🧩 Manual Installation via GitHub Release

#### 1- Download the Plugin ZIP

Download the latest release from GitHub:

```
https://github.com/ADORSYS-GIS/moodle-plugin/releases/tag/v1.0.0
```
#### 2- Installation
 After following the steps of the demo:
 
- Go to `Site Admin -> Plugins -> Install plugins`, and upload the *zip* file **"adorsys_theme_v1.zip"**.
- Select **theme** as the plugin type on the drop down.
- Click on **install plugin from the ZIP file**.
- Now go to **Site Administration > Notifications** 
- Follow the on-screen steps to complete the installation.

#### 3- Activation
- How to enable the theme:
```
Site Administration -> Appearance -> Theme Selector
```
Select the **Adorsys Theme v1**. You should see your theme changing to the select theme.



## License

MIT