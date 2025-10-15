/**
 * @module local_gis_ai_assistant/chat
 * Central chat controller for the AI Assistant
 */

define([
    'jquery',
    'core/log',
    'local_gis_ai_assistant/chat_ui',
    'local_gis_ai_assistant/chat_renderer',
    'local_gis_ai_assistant/utils'
], function ($, Log, ChatUI, Renderer, Utils) {

    'use strict';

    let initialized = false;

    /**
     * Initialize the chat interface
     */
    function init() {
        if (initialized) return;
        Log.info('Initializing AI chat...');
        try { if (Utils && Utils.injectCssOnce) { Utils.injectCssOnce(M.cfg.wwwroot + '/local/gis_ai_assistant/amd/src/styles.css'); } } catch (e) {}
        // Ensure renderer deps then init UI
        try { if (Renderer && Renderer.ensure) { Renderer.ensure(); } } catch (e) {}
        try { ChatUI.init(); } catch (e) { Log.error('ChatUI.init failed', e); }
        initialized = true;
        Log.info('AI chat initialized');
    }

    /**
     * Bind UI event listeners
     */
    function bindEvents() { /* UI events are bound inside chat_ui.js */ }

    /**
     * Handle sending a message
     */
    function handleSend() { /* Sending handled in chat_ui.js via ChatCore */ }

    return { init };
});
