#!/bin/sh

# This script downloads Moodle plugins based on the PLUGINS_TO_DOWNLOAD environment variable.
# The format of PLUGINS_TO_DOWNLOAD is "URL,TARGET_DIR;URL,TARGET_DIR;..."

# POSIX compliant way to set a default value for PLUGINS_DIR
if [ -z "$PLUGINS_DIR" ]; then
  PLUGINS_DIR="/plugin_downloads"
fi

echo "Starting plugin download process..."

# Ensure the plugins directory exists
mkdir -p "${PLUGINS_DIR}"

if [ -z "$PLUGINS_TO_DOWNLOAD" ]; then
  echo "No plugins specified in PLUGINS_TO_DOWNLOAD environment variable. Skipping download."
  exit 0
fi

# Split the PLUGINS_TO_DOWNLOAD string by semicolon to get individual plugin entries
# Using 'echo' and 'tr' for POSIX compatibility instead of Bash-specific '<<< "$VAR"'
PLUGIN_ENTRIES_STR=$(echo "$PLUGINS_TO_DOWNLOAD" | tr ';' '\n')

# Iterate over each line (each plugin entry)
echo "$PLUGIN_ENTRIES_STR" | while IFS= read -r entry; do
  # Skip empty entries that might result from trailing semicolons
  [ -z "$entry" ] && continue

  # Split each entry by comma to get URL and TARGET_DIR
  # Using 'echo' and 'cut' for POSIX compatibility
  PLUGIN_URL=$(echo "$entry" | cut -d',' -f1)
  PLUGIN_TARGET_DIR=$(echo "$entry" | cut -d',' -f2)

  # Trim whitespace (xargs is generally available on alpine/linux)
  PLUGIN_URL=$(echo "${PLUGIN_URL}" | xargs)
  PLUGIN_TARGET_DIR=$(echo "${PLUGIN_TARGET_DIR}" | xargs)

  if [ -z "$PLUGIN_URL" ] || [ -z "$PLUGIN_TARGET_DIR" ]; then
    echo "Warning: Invalid plugin entry '$entry'. Skipping."
    continue
  fi

  echo "Processing plugin: ${PLUGIN_TARGET_DIR}"

  if [ ! -d "${PLUGINS_DIR}/${PLUGIN_TARGET_DIR}" ]; then
    echo "  Downloading ${PLUGIN_URL}..."
    TEMP_ZIP="/tmp/$(basename "${PLUGIN_URL}" | cut -d'?' -f1)" # Get filename, remove query params
    wget -q -O "${TEMP_ZIP}" "${PLUGIN_URL}"

    if [ $? -eq 0 ]; then
      echo "  Extracting to ${PLUGINS_DIR}/${PLUGIN_TARGET_DIR}..."
      # Create target directory first, then unzip into it
      mkdir -p "${PLUGINS_DIR}/${PLUGIN_TARGET_DIR}"
      unzip -q "${TEMP_ZIP}" -d "${PLUGINS_DIR}/${PLUGIN_TARGET_DIR}/"

      # Check if unzip created a nested folder (common for some plugins)
      # If so, move contents up. Using find in a more POSIX-friendly way.
      # This part remains mostly the same as 'find' is typically available.
      EXTRACTED_CONTENT=$(find "${PLUGINS_DIR}/${PLUGIN_TARGET_DIR}" -mindepth 1 -maxdepth 1 -type d -print -quit 2>/dev/null)
      if [ -n "$EXTRACTED_CONTENT" ] && [ "$(basename "$EXTRACTED_CONTENT")" != "${PLUGIN_TARGET_DIR}" ]; then
        echo "  Detected nested folder: $(basename "$EXTRACTED_CONTENT"). Moving contents up..."
        mv "${EXTRACTED_CONTENT}"/* "${PLUGINS_DIR}/${PLUGIN_TARGET_DIR}/"
        rmdir "${EXTRACTED_CONTENT}"
      fi

      rm "${TEMP_ZIP}"
      echo "  ${PLUGIN_TARGET_DIR} downloaded and extracted successfully."
    else
      echo "  Error: Failed to download ${PLUGIN_URL}. Skipping."
    fi
  else
    echo "  ${PLUGIN_TARGET_DIR} already exists. Skipping download."
  fi
done

echo "Plugin download process finished."