<?php
// This file is part of Moodle - http://moodle.org/

$string['pluginname'] = 'GIS AI Provider';

// Settings UI.
$string['settingsheading'] = 'GIS AI Provider settings';
$string['settingsdesc'] = 'Configure the provider. Credentials are environment-only (e.g., OPENAI_API_KEY). Non-credential settings may be set here or via environment variables.';
$string['baseurl'] = 'Base API URL';
$string['baseurl_desc'] = 'Base URL for the OpenAI-compatible API. If not set here, OPENAI_BASE_URL will be used.';
$string['model'] = 'Default model';
$string['model_desc'] = 'Default model used for text/chat generation (e.g., gpt-4o).';

// Capabilities.
$string['gis_ai:viewanalytics'] = 'View provider analytics';
$string['analytics'] = 'Provider analytics';

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

$string['healthcheck'] = 'Provider health status';

// Rate limiting.
$string['ratelimiting'] = 'Rate limiting';
$string['ratelimiting_desc'] = 'Control usage limits to prevent abuse and manage costs.';
$string['requestsperhour'] = 'Requests per hour per user';
$string['requestsperhour_desc'] = 'Maximum number of AI requests a user can make per hour. Use 0 for unlimited.';
$string['tokensperhour'] = 'Tokens per hour per user';
$string['tokensperhour_desc'] = 'Maximum number of AI tokens a user can consume per hour. Use 0 for unlimited.';

// Features.
$string['enablestreaming'] = 'Enable streaming responses';
$string['enablestreaming_desc'] = 'Allow real-time streaming of AI responses for better user experience.';
$string['enableconversations'] = 'Enable conversation persistence';
$string['enableconversations_desc'] = 'Store chat history for better context and user experience.';

// Advanced settings.
$string['advanced'] = 'Advanced settings';
$string['advanced_desc'] = 'Fine-tune AI behavior and performance.';
$string['requesttimeout'] = 'Request timeout (seconds)';
$string['requesttimeout_desc'] = 'Maximum time to wait for AI API responses.';
$string['maxtokens'] = 'Maximum tokens per response';
$string['maxtokens_desc'] = 'Limit the length of AI responses to control costs.';
$string['temperature'] = 'Temperature (0.0-1.0)';
$string['temperature_desc'] = 'Controls randomness in responses. Lower values are more deterministic.';

// Error messages.
$string['ratelimitexceeded'] = 'Rate limit exceeded. Please try again later.';
$string['ratelimittokensexceeded'] = 'Token limit exceeded. Please try again later.';
$string['streamingnotavailable'] = 'Streaming is not available. Using regular response.';
$string['conversationnotfound'] = 'Conversation not found.';
$string['invalidconversationid'] = 'Invalid conversation ID.';
$string['emptyprompt'] = 'Please enter a message.';
$string['streamstarted'] = 'Streaming response started...';
$string['processingfailed'] = 'Failed to process your request. Please try again.';

// Analytics.
$string['ratelimits'] = 'Rate Limits';
$string['useractivity'] = 'User Activity';
$string['systemhealth'] = 'System Health';
$string['noactivity'] = 'No activity in selected period.';
$string['last24hours'] = 'Last 24 hours';
$string['last7days'] = 'Last 7 days';
$string['last30days'] = 'Last 30 days';
$string['customrange'] = 'Custom range';
