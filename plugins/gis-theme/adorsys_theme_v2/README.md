# Adorsys Theme v2

A modern Moodle theme built with **Tailwind CSS**, **PostCSS**, and **Webpack**.

This theme is a child theme based on Adorsys Theme v1, providing enhanced customization and modern styling capabilities for Moodle LMS.

---

## Requirements

- **Moodle**: 3.11 or higher
- **PHP**: 7.4 or higher
- **Node.js**: 18 or higher
- **Yarn**: Latest stable version
- **Browser**: Modern browsers (Chrome, Firefox, Safari, Edge)

---

## Features

- ✨ Modern UI with **Tailwind CSS** for rapid styling
- 🎨 Customizable theme settings via Moodle admin interface
- 📱 Fully responsive design for mobile, tablet, and desktop
- ⚡ Optimized asset bundling with **Webpack**
- 🔧 Developer-friendly with TypeScript and SCSS support
- 🎯 Based on Adorsys Theme v1 architecture

---

## Installation

### Standard Installation

1. **Download the plugin**
   - Download the latest release from the [GitHub releases page](https://github.com/ADORSYS-GIS/moodle-plugin/releases)

2. **Upload to Moodle**
   - Log in to your Moodle site as an administrator
   - Navigate to: `Site administration → Plugins → Install plugins`
   - Upload the `theme_adorsys_theme_v2.zip` file
   - Select **Theme (theme)** as the plugin type
   - Click **Install plugin from the ZIP file**

3. **Complete installation**
   - Navigate to: `Site administration → Notifications`
   - Follow the on-screen prompts to complete the installation

4. **Activate the theme**
   - Navigate to: `Site administration → Appearance → Theme selector`
   - Select **Adorsys Theme v2** for your desired device types
   - Click **Save** to apply the theme

5. **Purge caches**
   - Navigate to: `Site administration → Development → Purge all caches`
   - Click **Purge all caches** to ensure changes take effect

---

## Configuration

After installation, you can customize the theme settings:

1. Navigate to: `Site administration → Appearance → Themes → Adorsys Theme v2`
2. Configure available settings as needed
3. Save your changes
4. Purge caches to see the updates

---

## Development

### Prerequisites

- Docker & Docker Compose
- Node.js (>=18)
- Yarn package manager

### Local Development Setup

1. **Clone the repository** (if not already done)
   ```bash
   git clone https://github.com/ADORSYS-GIS/moodle-plugin.git
   cd moodle-plugin
   ```

2. **Navigate to the theme directory**
   ```bash
   cd plugins/gis-theme/adorsys_theme_v2
   ```

3. **Install dependencies**
   ```bash
   yarn install
   ```

4. **Build assets**
   ```bash
   yarn build
   ```

### Docker Compose Integration

To mount the theme in your Moodle Docker container for live development:

1. **Add volume mapping** to your `docker-compose.yml` or `compose.yaml` under the `moodle` service:

   ```yaml
   volumes:
     - ./outputs/plugins/gis-theme/adorsys_theme_v2:/bitnami/moodle/theme/adorsys_theme_v2:ro
   ```

2. **Start the Docker stack**
   ```bash
   docker compose up -d
   ```

3. **Access Moodle**
   - Navigate to `http://localhost:8080` (or your configured port)
   - Log in as administrator

4. **Select the theme**
   - Go to: `Site administration → Appearance → Theme selector`
   - Choose **Adorsys Theme v2**
   - Click **Save**

5. **Purge caches** (Required after any changes)
   - Navigate to: `Site administration → Development → Purge all caches`
   - Click **Purge all caches**
   
   > **Note**: You must purge caches after every change to see updates. This includes:
   > - Template modifications (`.mustache` files)
   > - CSS/SCSS changes
   > - JavaScript updates
   > - Configuration changes

### Development Workflow

1. Make changes to your theme files (templates, SCSS, TypeScript, etc.)
2. If you modified assets in `src/`, rebuild:
   ```bash
   yarn build
   ```
3. Purge Moodle caches via the web interface or CLI:
   ```bash
   docker compose exec moodle php admin/cli/purge_caches.php
   ```
4. Refresh your browser to see changes

### Project Structure

```
adorsys_theme_v2/
├── classes/                    # PHP classes (autoloaded by Moodle)
│   └── output/
│       └── renderer.php        # Custom theme renderer
├── config.php                  # Moodle theme configuration
├── lang/                       # Language files
│   └── en/
│       └── theme_adorsys_theme_v2.php # English language strings
├── layout/                     # Page layout definitions
│   └── *.php                   # Various layout files (columns, login, etc.)
├── lib.php                     # Theme functions (asset loading, SCSS compilation)
├── package.json                # Node.js dependencies and build scripts
├── pix/                        # Theme images and icons
│   ├── favicon.ico             # Theme favicon
│   └── screenshot.png          # Theme preview screenshot
├── postcss.config.js           # PostCSS configuration (Tailwind, Autoprefixer)
├── README.md                   # This file
├── scss/                       # SCSS source files
├── settings.php                # Admin settings definition
├── src/                        # TypeScript/JavaScript source
│   ├── index.ts                # Main entry point
│   └── styles/                 # Style sources
│       └── main.scss           # Main SCSS file
├── tailwind.config.js          # Tailwind CSS configuration
├── templates/                  # Mustache templates
│   └── *.mustache              # Template files
├── tsconfig.json               # TypeScript configuration
├── version.php                 # Plugin version information
├── webpack.config.ts           # Webpack build configuration
└── yarn.lock                   # Dependency lock file
```

---

## Troubleshooting

### Theme not appearing after installation
- Ensure you've purged all caches: `Site administration → Development → Purge all caches`
- Check that the theme is compatible with your Moodle version (requires Moodle 3.11+)

### Changes not reflecting
- Always purge caches after making changes
- If using Docker, ensure volume mounting is correct
- Check browser console for JavaScript errors

### Build errors
- Ensure Node.js version is 18 or higher: `node --version`
- Delete `node_modules` and reinstall: `rm -rf node_modules && yarn install`
- Check for TypeScript or webpack configuration errors

---

## Support

For issues, questions, or contributions:
- **GitHub Issues**: [https://github.com/ADORSYS-GIS/moodle-plugin/issues](https://github.com/ADORSYS-GIS/moodle-plugin/issues)
- **Repository**: [https://github.com/ADORSYS-GIS/moodle-plugin](https://github.com/ADORSYS-GIS/moodle-plugin)

---

## License

MIT License - See LICENSE file for details

---

## Credits

Developed and maintained by the Adorsys GIS team.