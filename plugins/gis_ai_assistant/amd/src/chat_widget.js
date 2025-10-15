/**
 * @module local_gis_ai_assistant/chat_widget
 * Moodle AMD entrypoint for initializing the chat widget
 */

define(['jquery', 'core/log', 'local_gis_ai_assistant/chat'], function ($, Log, Chat) {

    'use strict';

    return {
        init: function () {
            Log.info('Loading AI Assistant Chat Widget...');
            $(document).ready(function () {
                Chat.init();
            });
        }
    };
});
