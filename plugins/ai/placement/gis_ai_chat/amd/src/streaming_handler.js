define(['core/notification'], function(Notification) {
    'use strict';

    /**
     * Handle streaming responses via Server-Sent Events.
     * @param {string} streamId Stream ID from initial response
     * @param {(chunk: string) => void} onChunk Callback for each chunk
     * @param {() => void} onEnd Callback when stream ends
     * @param {(err: any) => void} onError Callback on error
     */
    function handleServerSentEvents(streamId, onChunk, onEnd, onError) {
        var eventSource = new EventSource(M.cfg.wwwroot + '/ai/placement/gis_ai_chat/stream.php?id=' + streamId);
        
        eventSource.onmessage = function(event) {
            try {
                var data = JSON.parse(event.data);
                
                if (data.type === 'chunk') {
                    if (typeof onChunk === 'function') {
                        onChunk(data.content);
                    }
                } else if (data.type === 'done') {
                    if (typeof onEnd === 'function') {
                        onEnd();
                    }
                    eventSource.close();
                } else if (data.type === 'error') {
                    if (typeof onError === 'function') {
                        onError(new Error(data.message));
                    }
                    eventSource.close();
                }
            } catch (e) {
                if (typeof onError === 'function') {
                    onError(e);
                }
                eventSource.close();
            }
        };

        eventSource.onerror = function(err) {
            if (typeof onError === 'function') {
                onError(new Error('Stream connection error'));
            }
            eventSource.close();
        };

        return eventSource;
    }

    /**
     * Handle streaming responses via Fetch API and ReadableStream (fallback).
     * @param {string} url Endpoint URL
     * @param {object} payload Request payload
     * @param {(chunk: string) => void} onChunk Callback for each chunk
     * @param {() => void} onEnd Callback when stream ends
     * @param {(err: any) => void} onError Callback on error
     */
    function handleReadableStream(url, payload, onChunk, onEnd, onError) {
        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        })
        .then(function(response) {
            if (!response.ok) { throw new Error('HTTP ' + response.status); }
            if (!response.body) { throw new Error('ReadableStream not supported'); }
            var reader = response.body.getReader();
            var decoder = new TextDecoder();
            var buffer = '';
            
            function read() {
                reader.read().then(function(result) {
                    if (result.done) { 
                        if (typeof onEnd === 'function') { onEnd(); } 
                        return; 
                    }
                    
                    var chunk = decoder.decode(result.value, { stream: true });
                    buffer += chunk;
                    
                    // Process complete JSON objects from buffer
                    var lines = buffer.split('\n');
                    buffer = lines.pop() || ''; // Keep incomplete line in buffer
                    
                    lines.forEach(function(line) {
                        if (line.trim()) {
                            try {
                                var data = JSON.parse(line);
                                if (data.type === 'chunk' && typeof onChunk === 'function') {
                                    onChunk(data.content);
                                }
                            } catch (e) {
                                // Skip invalid JSON lines
                                console.warn('Invalid JSON in stream:', line);
                            }
                        }
                    });
                    
                    read();
                }).catch(function(err) {
                    if (typeof onError === 'function') { onError(err); } else { Notification.exception(err); }
                });
            }
            read();
        })
        .catch(function(err) {
            if (typeof onError === 'function') { onError(err); } else { Notification.exception(err); }
        });
    }

    /**
     * Main streaming handler that tries SSE first, falls back to ReadableStream.
     * @param {string} streamId Stream ID from initial response
     * @param {(chunk: string) => void} onChunk Callback for each chunk
     * @param {() => void} onEnd Callback when stream ends
     * @param {(err: any) => void} onError Callback on error
     */
    function handleStream(streamId, onChunk, onEnd, onError) {
        // Try Server-Sent Events first
        if (typeof EventSource !== 'undefined') {
            return handleServerSentEvents(streamId, onChunk, onEnd, onError);
        } else {
            // Fallback to ReadableStream
            var url = M.cfg.wwwroot + '/ai/placement/gis_ai_chat/stream_fallback.php';
            var payload = { streamid: streamId };
            handleReadableStream(url, payload, onChunk, onEnd, onError);
        }
    }

    return { 
        handleStream: handleStream,
        handleServerSentEvents: handleServerSentEvents,
        handleReadableStream: handleReadableStream
    };
});
