# Adorsys Theme v1

A custom Moodle theme built with Tailwind CSS and Webpack based on the Boost parent theme.

## Prerequisites

- Node.js (>=14)
- Yarn
- Docker & Docker Compose
- Moodle Docker environment from `compose.yaml`

## Installation

```bash
cd plugins/adorsys-theme-v1
yarn install
```

## Build

Compile SCSS and bundle assets:

```bash
yarn build
```

Generated files will be in `style/`:
- `all.css` — compiled CSS
- `bundle.js` — placeholder JS bundle

## Development

Watch for changes and rebuild automatically:

```bash
yarn build --watch
```

## Docker Setup

Mount this theme into your Moodle container:

```yaml
    volumes:
      - ./plugins/adorsys-theme-v1:/bitnami/moodle/theme/adorsys-theme-v1:ro
```

Then start or restart your Docker Compose stack:

```bash
docker compose up -d
```

Purge theme cache in Moodle admin to see updates.

## File Structure

```
adorsys-theme-v1/
├── config.php
├── version.php
├── settings.php
├── lang/en/theme_adorsys-theme-v1.php
├── tailwind.config.js
├── postcss.config.js
├── webpack.config.js
├── scss/
│   └── style.scss
└── style/
    ├── all.css
    └── bundle.js
```

## Customization

- Add SCSS partials under `scss/` and import them in `style.scss`.
- Extend Tailwind config in `tailwind.config.js`.
- Define theme settings in `settings.php`.

## License

MIT