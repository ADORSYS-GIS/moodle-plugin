// Placeholder for streaming/chunk handling
export function handleStream(source, onChunk, onEnd, onError) {
    try {
        // TODO: implement streaming via fetch/readable streams
        onEnd && onEnd();
    } catch (e) {
        onError && onError(e);
    }
}
