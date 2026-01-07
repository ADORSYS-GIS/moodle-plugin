# 🚀 Release Guide for Themes & Provider Plugins

This guide explains how to trigger Theme Releases and Provider Plugin Releases using Git tags.

Both release pipelines run automatically when a correctly formatted tag is pushed.

## 📌 1. Tag Naming Conventions

### Themes

```
theme-{theme_name}-v{version}
```

**Example:**

```
theme-adorsys_theme_v1-v1.2.3
```

### Providers

```
provider-{provider_name}-v{version}
```

**Example:**

```
provider-openai_compatible_v1-v1.0.0
```

## 📌 2. Required Folder Structure

### Themes:

```
plugins/gis-theme/{theme_name}
```

**Example:**

```
plugins/gis-theme/adorsys_theme_v1
```

### Providers:

```
plugins/ai/provider/{provider_name}
```

**Example:**

```
plugins/ai/provider/openaicompatible
```

## 🚀 3. How to Trigger a Release (Local Git)

**1️⃣ Push your latest code**

```
git push
```

**2️⃣ Create a tag**

Theme example:

```
git tag theme-adorsys_theme_v1-v1.2.3
```

Provider example:

```
git tag provider-openaicompatible-v1.0.0
```

Creating the tag opens your editor, where you can add an optional description.

**3️⃣ Push the tag**

```
git push origin <tag-name>
```

**Examples:**

```
git push origin theme-adorsys_theme_v1-v1.2.3
git push origin provider-openaicompatible-v1.0.0
```

Once the tag is pushed, GitHub Actions will automatically:

Build the theme/provider

Package it as a .zip

Create a GitHub Release with the artifact
