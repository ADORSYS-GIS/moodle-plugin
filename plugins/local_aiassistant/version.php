<?php
defined('MOODLE_INTERNAL') || die(); // Security check

// The component name must match the plugin's directory name.
$plugin->component = 'local_aiassistant';
// Use the current date (YYYYMMDD) plus a two-digit counter for the version.
$plugin->version   = 2025082501;
// Specifies the minimum Moodle version this plugin will work with.
$plugin->requires  = 2023100900; // Moodle 4.3+
// Indicates the stability of the plugin (e.g., MATURITY_ALPHA, MATURITY_BETA, MATURITY_STABLE).
$plugin->maturity  = MATURITY_STABLE;