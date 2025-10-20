<?php
// This file is part of Moodle - http://moodle.org/

$string['pluginname'] = 'GIS AI Provider';

// Settings UI.
$string['settingsheading'] = 'GIS AI Provider settings';
$string['settingsdesc'] = 'Configure the provider. Secrets are best supplied via environment variables (e.g., OPENAI_API_KEY). UI values are fallbacks.';
$string['baseurl'] = 'Base API URL';
$string['baseurl_desc'] = 'Base URL for the OpenAI-compatible API. If not set here, OPENAI_BASE_URL will be used.';
$string['apikey'] = 'API key';
$string['apikey_desc'] = 'API key for the upstream AI service. Prefer OPENAI_API_KEY in the environment.';
$string['model'] = 'Default model';
$string['model_desc'] = 'Default model used for text/chat generation (e.g., gpt-4o).';

// Privacy.
$string['privacy:metadata'] = 'The GIS AI Provider does not store any personal data itself.';

// Errors and exceptions.
$string['apiresponseerror'] = 'AI API returned an error: {$a}';
$string['emptyresponse'] = 'The AI response was empty.';
$string['invalidmode'] = 'Invalid AI_RUST_MODE: {$a}';
$string['envmissing'] = 'Required environment variable missing: {$a}';
$string['invalidemail'] = 'Invalid email: {$a}';

// Events.
$string['eventinteractionlogged'] = 'AI interaction logged';

// Tasks.
$string['task_purge_old_analytics'] = 'Purge old AI analytics logs';

// Privacy metadata for analytics table.
$string['privacy:metadata:aiprovider_gis_ai_logs'] = 'Stores anonymised analytics for AI interactions.';
$string['privacy:metadata:aiprovider_gis_ai_logs:userid'] = 'The user ID associated with the interaction.';
$string['privacy:metadata:aiprovider_gis_ai_logs:timestamp'] = 'The time when the interaction occurred.';
$string['privacy:metadata:aiprovider_gis_ai_logs:prompt_hash'] = 'SHA-256 hash of the prompt to avoid storing raw content.';
$string['privacy:metadata:aiprovider_gis_ai_logs:response_hash'] = 'SHA-256 hash of the response for deduplication/analytics.';
$string['privacy:metadata:aiprovider_gis_ai_logs:tokens'] = 'Total tokens used by the AI provider for the request.';
$string['privacy:metadata:aiprovider_gis_ai_logs:status'] = 'Whether the request succeeded (1) or failed (0).';
$string['privacy:metadata:aiprovider_gis_ai_logs:contextid'] = 'Context ID where the interaction took place.';
$string['privacy:metadata:aiprovider_gis_ai_logs:courseid'] = 'Course ID related to the interaction, if any.';
