<?php
// This file is part of Moodle - http://moodle.org/

$string['pluginname'] = 'GIS AI Chat placement';

// Capabilities.
$string['aiplacement/gis_ai_chat:generate_text'] = 'Use GIS AI Chat to generate text';

// Errors.
$string['processingfailed'] = 'Unable to process your request at this time.';
$string['feedback'] = 'Feedback';

// Strings used by the new templates.
$string['askyourquestion'] = 'Ask your question...';
$string['promptlabel'] = 'Chat message input';
$string['send'] = 'Send';
$string['typeyourmessage'] = 'Type your message here...';

// Privacy.
$string['privacy:metadata'] = 'The GIS AI Chat placement does not store any personal data.';

// Streaming and conversations.
$string['streamingenabled'] = 'Streaming enabled';
$string['streamingdisabled'] = 'Streaming disabled';
$string['conversationsenabled'] = 'Conversations enabled';
$string['conversationsdisabled'] = 'Conversations disabled';
$string['newconversation'] = 'New conversation';
$string['conversationhistory'] = 'Conversation history';
$string['typing'] = 'Typing...';
$string['erroroccurred'] = 'An error occurred';
$string['retry'] = 'Retry';
$string['clearchat'] = 'Clear chat';
$string['exportconversation'] = 'Export conversation';
$string['streamconnectionlost'] = 'Stream connection lost. Please try again.';
$string['streamtimeout'] = 'Stream timed out. Please try again.';

// Enhanced UI.
$string['welcome'] = 'Welcome to GIS AI Chat';
$string['welcomedesc'] = 'I\'m here to help you with your questions. How can I assist you today?';
$string['poweredby'] = 'Powered by GIS AI';
$string['statusonline'] = 'Online';
$string['statusoffline'] = 'Offline';
$string['statusconnecting'] = 'Connecting...';
$string['characterremaining'] = 'character remaining';
$string['charactersremaining'] = 'characters remaining';
$string['messagelengthlimit'] = 'Message too long. Please keep it under {$a} characters.';

// Rate limiting messages.
$string['ratelimitwarning'] = 'You\'re approaching your usage limit. {$a} requests remaining this hour.';
$string['ratelimitexceeded'] = 'You\'ve reached your usage limit. Please try again later.';
$string['ratelimitreset'] = 'Limit resets at: {$a}';

// Admin settings.
$string['placementsettings'] = 'Placement settings';
$string['placementsettingsdesc'] = 'Configure how the AI chat appears and behaves.';
$string['chatappearance'] = 'Chat appearance';
$string['chatbehaviour'] = 'Chat behaviour';
$string['enablestreamingplacement'] = 'Enable streaming responses';
$string['enablestreamingplacement_desc'] = 'Allow real-time streaming of AI responses in the chat interface.';
$string['enableconversationsplacement'] = 'Enable conversation persistence';
$string['enableconversationsplacement_desc'] = 'Allow users to maintain conversation history across sessions.';
$string['maxmessagelength'] = 'Maximum message length';
$string['maxmessagelength_desc'] = 'Maximum number of characters allowed in a single message.';
$string['chattheme'] = 'Chat theme';
$string['chattheme_desc'] = 'Choose the visual theme for the chat interface.';
$string['theme_default'] = 'Default';
$string['theme_dark'] = 'Dark';
$string['theme_compact'] = 'Compact';
$string['theme_minimal'] = 'Minimal';
$string['task_process_stream_chat'] = 'Process streaming chat request';
