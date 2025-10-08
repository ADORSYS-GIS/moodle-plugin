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
     * Send request to Rust sidecar (simplified for dual mode)
     */
    private function send_request($request) {
        try {
            $response = $this->communicator->send_request($request);
            
            if ($response === false || empty($response)) {
                error_log("OpenAI communicator returned empty response");
                return [
                    'success' => false,
                    'error' => 'No response from sidecar'
                ];
            }
            
            // Decode JSON response
            $decoded = json_decode($response, true);
            
            if ($decoded === null) {
                error_log("OpenAI sidecar returned invalid JSON: " . substr($response, 0, 200));
                return [
                    'success' => false,
                    'error' => 'Invalid JSON response from sidecar'
                ];
            }
            
            // Validate response structure
            if (!isset($decoded['success'])) {
                error_log("OpenAI sidecar response missing 'success' field");
                return [
                    'success' => false,
                    'error' => 'Invalid response structure'
                ];
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
