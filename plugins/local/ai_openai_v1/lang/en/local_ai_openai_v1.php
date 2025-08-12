<?php
defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'AI Provider (OpenAI Compatible V1)';
$string['providerdesc'] = 'An AI provider that uses a local Rust binary to connect to any OpenAI-compatible API.';
$string['api_key'] = 'API Key';
$string['api_key_desc'] = 'The API key for the OpenAI-compatible service.';
$string['base_url'] = 'API Base URL';
$string['base_url_desc'] = 'The base URL for the API endpoint (e.g., https://api.openai.com/v1). Leave empty for default.';
$string['rust_binary_path'] = 'Rust Binary Path';
$string['rust_binary_path_desc'] = 'The absolute path to the compiled Rust executable. Default: [plugin_dir]/rust_processor/target/release/moodle_ai_processor_openai_v1';  