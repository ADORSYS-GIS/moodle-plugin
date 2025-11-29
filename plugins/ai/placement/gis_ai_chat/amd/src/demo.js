define(['core/ajax', 'core/notification', 'aiplacement_gis_ai_chat/streaming_handler'], function(Ajax, Notification, StreamingHandler) {
    'use strict';

    function h(tag, attrs, children) {
        var el = document.createElement(tag);
        if (attrs) { Object.keys(attrs).forEach(function(k){ el.setAttribute(k, attrs[k]); }); }
        (children || []).forEach(function(c){ el.appendChild(typeof c === 'string' ? document.createTextNode(c) : c); });
        return el;
    }

    function generateConversationId() {
        return 'conv_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }

    function addMessage(messages, text, isUser, streaming) {
        var bubble = h('div', {
            class: 'ai-message ' + (isUser ? 'user-message' : 'ai-message') + (streaming ? ' streaming' : ''),
            style: 'padding:.5rem;border:1px solid #ddd;margin:.5rem 0;border-radius:8px;background:' + (isUser ? '#e3f2fd' : '#f5f5f5') + ';'
        }, [text]);
        
        if (!isUser && streaming) {
            bubble.appendChild(h('div', { class: 'typing-indicator', style: 'color:#666;font-size:0.8em;' }, ['Typing...']));
        }
        
        messages.appendChild(bubble);
        messages.scrollTop = messages.scrollHeight;
        return bubble;
    }

    function updateStreamingMessage(bubble, text) {
        var indicator = bubble.querySelector('.typing-indicator');
        if (indicator) {
            indicator.remove();
        }
        bubble.textContent = text;
    }

    function buildUI(root, sendFn) {
        root.innerHTML = '';
        var input = h('textarea', { rows: 5, class: 'form-control gis-ai-prompt-input', style: 'width:100%;' });
        var btn = h('button', { type: 'button', class: 'btn btn-primary gis-ai-send-btn' }, ['Send']);
        var messages = h('div', { class: 'gis-ai-chat-messages', style: 'margin-top:1rem;' });

        btn.addEventListener('click', function(){
            var text = (input.value || '').trim();
            if (!text) { return; }
            btn.disabled = true;
            sendFn(text)
                .then(function(resp){
                    var ok = resp && resp.ok;
                    var content = ok ? (resp.content || '') : (resp.error || 'Error');
                    var bubble = h('div', { class: ok ? 'ai-reply' : 'ai-error', style: 'padding:.5rem;border:1px solid #ddd;margin:.5rem 0;' }, [content]);
                    messages.appendChild(bubble);
                })
                .catch(function(err){ Notification.exception(err); })
                .finally(function(){ btn.disabled = false; });
        });

        root.appendChild(input);
        root.appendChild(h('div', { style: 'margin-top:.5rem;' }, [btn]));
        root.appendChild(messages);
    }

    function wireUI(root, sendFn, streamingEnabled) {
        var input = root.querySelector('.gis-ai-prompt-input');
        var btn = root.querySelector('.gis-ai-send-btn');
        var messages = root.querySelector('.gis-ai-chat-messages');
        if (!input || !btn || !messages) {
            buildUI(root, sendFn);
            input = root.querySelector('.gis-ai-prompt-input');
            btn = root.querySelector('.gis-ai-send-btn');
            messages = root.querySelector('.gis-ai-chat-messages');
        }
        if (root.dataset.gisChatBound === '1') { return; }
        root.dataset.gisChatBound = '1';
        
        // Initialize conversation ID if not present
        if (!root.dataset.conversationId) {
            root.dataset.conversationId = generateConversationId();
        }
        
        var currentStream = null;
        
        btn.addEventListener('click', function(){
            var text = (input.value || '').trim();
            if (!text) { return; }
            
            // Add user message
            addMessage(messages, text, true, false);
            input.value = '';
            btn.disabled = true;
            
            // Prepare request arguments
            var args = {
                contextid: (root.dataset.contextid ? parseInt(root.dataset.contextid, 10) : 1),
                prompttext: text,
                stream: streamingEnabled,
                conversationid: root.dataset.conversationId
            };
            
            if (streamingEnabled) {
                // Use streaming
                Ajax.call([{ methodname: 'aiplacement_gis_ai_chat_send', args: args }])[0]
                    .then(function(resp){
                        if (resp && resp.ok && resp.streamid) {
                            var aiBubble = addMessage(messages, '', false, true);
                            
                            currentStream = StreamingHandler.handleStream(resp.streamid, 
                                function(chunk) {
                                    updateStreamingMessage(aiBubble, chunk);
                                },
                                function() {
                                    aiBubble.classList.remove('streaming');
                                    btn.disabled = false;
                                    currentStream = null;
                                },
                                function(err) {
                                    aiBubble.classList.remove('streaming');
                                    aiBubble.classList.add('ai-error');
                                    aiBubble.textContent = 'Error: ' + (err.message || 'Stream failed');
                                    btn.disabled = false;
                                    currentStream = null;
                                    Notification.exception(err);
                                }
                            );
                        } else {
                            // Fallback to non-streaming
                            handleRegularResponse(resp, messages, btn);
                        }
                    })
                    .catch(function(err){ 
                        Notification.exception(err); 
                        btn.disabled = false;
                    });
            } else {
                // Use regular request
                Ajax.call([{ methodname: 'aiplacement_gis_ai_chat_send', args: args }])[0]
                    .then(function(resp){
                        handleRegularResponse(resp, messages, btn);
                    })
                    .catch(function(err){ 
                        Notification.exception(err); 
                        btn.disabled = false;
                    });
            }
        });
        
        // Handle Enter key in textarea
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                btn.click();
            }
        });
    }
    
    function handleRegularResponse(resp, messages, btn) {
        var ok = resp && resp.ok;
        var content = ok ? (resp.content || '') : (resp.error || 'Error');
        var bubble = addMessage(messages, content, false, false);
        if (!ok) {
            bubble.classList.add('ai-error');
        }
        btn.disabled = false;
    }

    function init(rootSelector, options) {
        var root = document.querySelector(rootSelector || '.gis-ai-chat-full, .gis-ai-chat');
        if (!root) { return; }
        var opts = options || {};
        if (opts.contextid) { root.dataset.contextid = String(opts.contextid); }
        if (opts.conversationId) { root.dataset.conversationId = opts.conversationId; }
        
        var streamingEnabled = opts.streaming !== false; // Default to true
        
        function send(prompt) {
            return Ajax.call([{ 
                methodname: 'aiplacement_gis_ai_chat_send', 
                args: { 
                    contextid: (opts.contextid || 1), 
                    prompttext: prompt,
                    stream: streamingEnabled,
                    conversationid: root.dataset.conversationId
                } 
            }])[0];
        }
        
        wireUI(root, send, streamingEnabled);
    }

    return { 
        init: init,
        generateConversationId: generateConversationId
    };
});
