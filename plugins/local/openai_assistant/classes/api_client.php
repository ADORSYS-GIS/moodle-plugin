<?php
namespace local_openai_assistant;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/stdio_communicator.php');

class api_client {
    private $communicator;
    
    public function __construct() {
        $this->communicator = new stdio_communicator();
    }
    
    /**
     * Send a chat message to the AI
     */
    public function chat($message, $context = null, $user_id = null) {
        $request = [
            'action' => 'chat',
            'content' => $message,
            'context' => $context,
            'user_id' => $user_id
        ];
        
        return $this->send_request($request);
    }
    
    /**
     * Summarize content
     */
    public function summarize($content, $user_id = null) {
        $request = [
            'action' => 'summarize',
            'content' => $content,
            'user_id' => $user_id
        ];
        
        return $this->send_request($request);
    }
    
    /**
     * Analyze educational content
     */
    public function analyze($content, $user_id = null) {
        $request = [
            'action' => 'analyze',
            'content' => $content,
            'user_id' => $user_id
        ];
        
        return $this->send_request($request);
    }
    
    /**
     * Send request to Rust sidecar
     */
    private function send_request($request) {
        try {
            $response = $this->communicator->send_request($request);
            
            if ($response === false) {
                error_log("OpenAI communicator returned false (no response).");
                return [
                    'success' => false,
                    'error' => get_string('error_communication', 'local_openai_assistant')
                ];
            }
            
            // Try to decode the response; if it's not valid JSON, attempt to extract a JSON object
            $decoded = json_decode($response, true);
            if ($decoded === null) {
                // The sidecar may have logged startup/info lines before writing JSON to stdout.
                // Attempt to extract the last balanced JSON object from the output.
                $pattern = '/\{(?:(?>[^{}]+)|(?R))*\}/s'; // recursive balanced braces
                if (preg_match_all($pattern, $response, $matches)) {
                    $last = end($matches[0]);
                    $maybe = json_decode($last, true);
                    if ($maybe !== null && (isset($maybe['success']) || isset($maybe['data']))) {
                        $decoded = $maybe;
                        error_log("OpenAI sidecar: extracted JSON object from stdout (length " . strlen($last) . ").");
                    } else {
                        error_log("OpenAI sidecar: found JSON-like object but decoding failed.");
                    }
                } else {
                    // No JSON object found; treat entire response as plain text reply
                    $trimmed = trim($response);
                    error_log("OpenAI sidecar returned non-JSON response; returning raw text. Trimmed length: " . strlen($trimmed));
                    return [
                        'success' => true,
                        'data' => stripcslashes($trimmed)
                    ];
                }
            }
            
            if ($decoded === null) {
                // still could not decode — return plain response
                $trimmed = trim($response);
                return [
                    'success' => true,
                    'data' => stripcslashes($trimmed)
                ];
            }
            
            // If the decoded response includes a 'data' field that itself contains JSON
            // (for example the sidecar returned a JSON object serialized into the data string),
            // normalize it so the UI receives readable text.
            if (isset($decoded['data']) && is_string($decoded['data'])) {
                $data_str = $decoded['data'];
                $data_trim = trim($data_str);
                
                // Attempt to decode inner JSON if present
                $inner = json_decode($data_trim, true);
                if ($inner !== null) {
                    // If inner has its own 'data' field, prefer that
                    if (isset($inner['data']) && is_string($inner['data'])) {
                        $decoded['data'] = stripcslashes($inner['data']);
                    }
                    // If inner looks like OpenAI choices structure, extract the content
                    else if (isset($inner['choices']) && is_array($inner['choices'])) {
                        if (isset($inner['choices'][0]['message']['content'])) {
                            $decoded['data'] = stripcslashes($inner['choices'][0]['message']['content']);
                        } else if (isset($inner['choices'][0]['text'])) {
                            $decoded['data'] = stripcslashes($inner['choices'][0]['text']);
                        } else {
                            $decoded['data'] = stripcslashes(json_encode($inner));
                        }
                    } else {
                        // Fallback: convert inner structure to a readable string
                        $decoded['data'] = stripcslashes(json_encode($inner));
                    }
                } else {
                    // Not JSON: unescape escaped sequences so newlines and tabs render correctly
                    $decoded['data'] = stripcslashes($data_str);
                }
            }
            
            return $decoded;
            
        } catch (Exception $e) {
            error_log("Exception while calling OpenAI sidecar: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
