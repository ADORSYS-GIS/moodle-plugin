<?php
$string['pluginname'] = 'GIS AI Assistant 1';
$string['togglechat'] = 'Toggle AI chat';
$string['configtitle'] = 'GIS AI Assistant configuration';
$string['configdescription'] = 'This plugin is configured using environment variables only.';
$string['analytics'] = 'AI Analytics';

// Settings page ENV status labels.
$string['env_present'] = 'present';
$string['env_missing'] = 'missing';
$string['envstatus'] = 'Environment variables status';

// Optional future strings.
$string['enabled'] = 'Enabled';

// Exceptions and validation messages.
$string['envmissing'] = 'Required environment variable missing: {$a}';
$string['invalidemail'] = 'Invalid email address provided';

// API/bridge exception messages.
$string['apiresponseerror'] = 'AI API returned an error: {$a}';
$string['emptyresponse'] = 'The AI response was empty.';
$string['invalidmode'] = 'Invalid AI_RUST_MODE: {$a}';

// High-priority backend error strings
$string['ffierror'] = 'Rust FFI error: {$a}';
$string['apierror'] = 'Rust API request failed: {$a}';
$string['invalidjson'] = 'Invalid JSON received from backend.';
