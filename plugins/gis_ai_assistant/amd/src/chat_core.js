define(['jquery', 'core/ajax', 'core/notification'], function($, Ajax, Notification) {
    'use strict';

    var events = {};
    function on(ev, cb) { (events[ev] = events[ev] || []).push(cb); }
    function off(ev, cb) {
        if (!events[ev]) { return; }
        var idx = events[ev].indexOf(cb);
        if (idx !== -1) { events[ev].splice(idx, 1); }
    }
    function emit(ev, payload) {
        var list = events[ev] || [];
        for (var i = 0; i < list.length; i++) {
            try { list[i](payload); } catch (e) { if (window.console) console.error(e); }
        }
    }

    /* -------------------------
       getHistory(limit) -> returns Promise
    --------------------------*/
    function getHistory(limit) {
        var req = { methodname: 'local_gis_ai_assistant_get_history', args: { limit: limit || 100 } };
        return Ajax.call([req])[0].then(function(resp){
            if (resp && resp.success && resp.history) { return resp.history; }
            return [];
        }).catch(function() { return []; });
    }

    /* -------------------------
       sendMessage(message): non-streaming single call
       emits events:
         'message_sent' with { id?, role:'user', content }
         'message_received' with { id?, role:'assistant', content, content_html? }
    --------------------------*/
    function sendMessage(message) {
        var req = {
            methodname: 'local_gis_ai_assistant_send_message',
            args: { message: message }
        };
        // optimistic local echo
        emit('message_added', { role: 'user', content: message, local: true });
        return Ajax.call([req])[0].then(function(resp) {
            if (resp && resp.success) {
                var content = resp.content || resp.content_html || '';
                emit('message_added', { role: 'assistant', content: content, content_html: resp.content_html || null, raw: resp.content || '' });
                return resp;
            } else {
                Notification.addNotification({ message: (resp && resp.error) ? resp.error : 'Error contacting AI', type: 'error' });
                return resp;
            }
        }).catch(function(err){
            Notification.addNotification({ message: 'Network error contacting AI', type: 'error' });
            return Promise.reject(err);
        });
    }

    /* -------------------------
       Streaming support via SSE.
       startStreaming(message) -> returns EventSource instance (or null)
       Emits:
         'message_stream_start' (with { id? })
         'message_stream_chunk' (with { content })
         'message_stream_done' (with { content_html? })
         'message_stream_error'
    --------------------------*/
    function startStreaming(message) {
        emit('message_added', { role: 'user', content: message, local: true });
        // Ask backend to create stream
        var req = { methodname: 'local_gis_ai_assistant_send_message_stream', args: { message: message } };
        return Ajax.call([req])[0].then(function(resp){
            if (resp && resp.success && resp.stream_url) {
                var es = new EventSource(resp.stream_url);
                emit('message_stream_start', { stream_url: resp.stream_url });

                var buffer = '';
                // create an empty assistant bubble
                emit('message_added', { role: 'assistant', content: '', local: true, streaming: true });

                es.onmessage = function(ev) {
                    try {
                        var data = JSON.parse(ev.data);
                        if (data.type === 'content') {
                            buffer += (data.content || '');
                            emit('message_stream_chunk', { content: data.content, buffer: buffer });
                        } else if (data.type === 'done') {
                            emit('message_stream_done', { buffer: buffer, content_html: data.content_html || null });
                            es.close();
                        } else if (data.type === 'error') {
                            emit('message_stream_error', { error: data.error || 'stream error' });
                            es.close();
                        }
                    } catch (e) {
                        // ignore parse errors
                    }
                };

                es.onerror = function() {
                    emit('message_stream_error', { error: 'SSE error' });
                    try { es.close(); } catch (e) {}
                };

                return es;
            } else {
                Notification.addNotification({ message: (resp && resp.error) ? resp.error : 'Streaming unavailable', type: 'error' });
                return null;
            }
        }).catch(function(){
            Notification.addNotification({ message: 'Network error starting stream', type: 'error' });
            return null;
        });
    }

    return {
        on: on,
        off: off,
        emit: emit,
        getHistory: getHistory,
        sendMessage: sendMessage,
        startStreaming: startStreaming
    };
});
