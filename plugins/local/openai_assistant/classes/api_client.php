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
                return [
                    'success' => false,
                    'error' => get_string('error_communication', 'local_openai_assistant')
                ];
            }
            
            $decoded = json_decode($response, true);
            if ($decoded === null) {
                return [
                    'success' => false,
                    'error' => get_string('error_invalid_response', 'local_openai_assistant')
                ];
            }
            
            return $decoded;
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}