<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * English language strings for local_gis_ai_assistant plugin
 *
 * @package    local_gis_ai_assistant
 * @copyright  2024 Adorsys GIS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'GIS AI Assistant';
$string['gis_ai_assistant:use'] = 'Use GIS AI Assistant';
$string['gis_ai_assistant:viewanalytics'] = 'View GIS AI Analytics';
$string['gis_ai_assistant:manageplugin'] = 'Manage GIS AI Plugin';

// Settings strings.
$string['base_url'] = 'AI API Base URL';
$string['base_url_desc'] = 'The base URL for the AI API (e.g., https://api.openai.com/v1). For OpenAI, if you omit /v1, it will be added automatically.';
$string['api_key'] = 'AI API Key';
$string['api_key_desc'] = 'The API key for authentication with the AI service.';
$string['enabled'] = 'Enable AI functionality';
$string['enabled_desc'] = 'Enable or disable AI features across the site.';
$string['default_model'] = 'Default AI model';
$string['default_model_desc'] = 'The default AI model to use for requests (e.g., gpt-4o-mini, gpt-4, claude-3-sonnet).';
$string['max_tokens'] = 'Maximum tokens per request';
$string['max_tokens_desc'] = 'Maximum number of tokens allowed per AI request.';
$string['temperature'] = 'Temperature';
$string['temperature_desc'] = 'Controls randomness in AI responses (0.0 = deterministic, 1.0 = very random).';
$string['rate_limit_requests'] = 'Rate limit - Requests per hour';
$string['rate_limit_requests_desc'] = 'Maximum number of requests per user per hour.';
$string['rate_limit_tokens'] = 'Rate limit - Tokens per hour';
$string['rate_limit_tokens_desc'] = 'Maximum number of tokens per user per hour.';
$string['cache_ttl'] = 'Cache TTL (seconds)';
$string['cache_ttl_desc'] = 'How long to cache AI responses in seconds.';
$string['enable_cache'] = 'Enable response caching';
$string['enable_cache_desc'] = 'Cache AI responses to improve performance and reduce API costs.';
$string['enable_analytics'] = 'Enable analytics';
$string['enable_analytics_desc'] = 'Track AI usage for analytics and monitoring.';
$string['system_prompt'] = 'System prompt';
$string['system_prompt_desc'] = 'The system prompt that will be sent with every AI request to set context and behavior.';
$string['stream_session_ttl'] = 'Streaming session TTL (seconds)';
$string['stream_session_ttl_desc'] = 'How long a streaming session is valid before it expires. Used by the streaming endpoint to validate session age.';

// UI strings.
$string['chat_title'] = 'AI Chat Assistant';
$string['chat_placeholder'] = 'Ask me anything...';
$string['send'] = 'Send';
$string['send_stream'] = 'Send (stream)';
$string['clear'] = 'Clear chat';
$string['thinking'] = 'AI is thinking...';
$string['ai_welcome'] = 'Hello! I am your AI assistant. How can I help you today?';
$string['ai_disclaimer'] = 'AI responses are generated and may contain inaccuracies. Please verify important information.';
$string['toggle_theme'] = 'Toggle theme';
$string['confirm_clear_chat'] = 'Are you sure you want to clear the chat history?';
$string['error_occurred'] = 'An error occurred while processing your request.';
$string['rate_limit_exceeded'] = 'Rate limit exceeded. Please try again later.';
$string['ai_disabled'] = 'AI functionality is currently disabled.';
$string['no_api_key'] = 'AI service is not configured. Please contact your administrator.';

// Capabilities.
$string['ai:use'] = 'Use AI assistant';
$string['ai:viewanalytics'] = 'View AI analytics';
$string['ai:manage'] = 'Manage AI settings';

// Privacy.
$string['privacy:metadata:local_gis_ai_assistant_conversations'] = 'Information about AI conversations made by users.';
$string['privacy:metadata:local_gis_ai_assistant_conversations:userid'] = 'The ID of the user who made the request.';
$string['privacy:metadata:local_gis_ai_assistant_conversations:message'] = 'User message sent to the AI.';
$string['privacy:metadata:local_gis_ai_assistant_conversations:response'] = 'AI response content.';
$string['privacy:metadata:local_gis_ai_assistant_conversations:model'] = 'The AI model used for the request.';
$string['privacy:metadata:local_gis_ai_assistant_conversations:tokens_used'] = 'Total number of tokens used.';
$string['privacy:metadata:local_gis_ai_assistant_conversations:response_time'] = 'Time taken to process the request.';
$string['privacy:metadata:local_gis_ai_assistant_conversations:timecreated'] = 'When the request was made.';
$string['privacy:metadata:local_gis_ai_assistant_rate_limits'] = 'Information about user rate limiting.';
$string['privacy:metadata:local_gis_ai_assistant_rate_limits:userid'] = 'The ID of the user.';
$string['privacy:metadata:local_gis_ai_assistant_rate_limits:requests_count'] = 'Number of requests made in the current window.';
$string['privacy:metadata:local_gis_ai_assistant_rate_limits:tokens_count'] = 'Number of tokens used in the current window.';
$string['privacy:metadata:local_gis_ai_assistant_rate_limits:window_start'] = 'Start of the rate limit window (epoch).';
$string['privacy:metadata:openai'] = 'The AI service processes user messages to generate responses.';
$string['privacy:metadata:openai:messages'] = 'User messages sent to the AI service.';
$string['privacy:metadata:openai:user_email'] = 'User email sent as header for identification.';

// Privacy export headings.
$string['conversations'] = 'Conversations';
$string['rate_limits'] = 'Rate limits';

// Analytics.
$string['analytics_title'] = 'AI Usage Analytics';
$string['total_requests'] = 'Total requests';
$string['total_tokens'] = 'Total tokens used';
$string['average_response_time'] = 'Average response time';
$string['top_users'] = 'Top users';
$string['usage_by_model'] = 'Usage by model';
$string['requests_over_time'] = 'Requests over time';
// Additional analytics UI strings
$string['analytics_empty'] = 'No analytics data available for this period.';
$string['analytics_error_loading'] = 'Failed to load analytics.';
$string['ms_suffix'] = '{$a} ms';
$string['analytics_period'] = 'Period';
$string['analytics_day'] = 'Day';
$string['analytics_week'] = 'Week';
$string['analytics_month'] = 'Month';
$string['retry'] = 'Retry';
$string['users'] = 'Users';
$string['model'] = 'Model';
$string['request_count'] = 'Requests';
$string['token_count'] = 'Tokens';
$string['active_users'] = 'Active users';
$string['avg_response_time'] = 'Avg. response time';
$string['usage_over_time'] = 'Usage over time';
$string['model_distribution'] = 'Model distribution';
$string['response_time_distribution'] = 'Response time distribution';
$string['user_activity_heatmap'] = 'User activity heatmap';
$string['last_week'] = 'Last 7 days';
$string['requests'] = 'Requests';

// Errors.
$string['error_api_key_missing'] = 'API key not configured in environment variables.';
$string['error_api_request_failed'] = 'API request failed: {$a}';
$string['error_invalid_response'] = 'Invalid response from AI service.';
$string['error_rate_limit'] = 'Rate limit exceeded. Try again in {$a} minutes.';
$string['error_token_limit'] = 'Token limit exceeded for this request.';
$string['error_model_not_available'] = 'The requested model is not available.';
$string['error_empty_message'] = 'Please enter a message before sending.';

// Features.
$string['feature_chat'] = 'Chat';
$string['feature_summarize'] = 'Summarize';
$string['feature_explain'] = 'Explain';
$string['feature_translate'] = 'Translate';

// Actions.
$string['summarize_text'] = 'Summarize this text';
$string['explain_concept'] = 'Explain this concept';
$string['translate_text'] = 'Translate this text';
$string['ask_question'] = 'Ask a question';

// Cache definitions.
$string['cachedef_responses'] = 'AI response cache';
$string['cachedef_stream_sessions'] = 'AI streaming session cache';
