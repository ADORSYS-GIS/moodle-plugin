<?php
namespace local_openai_assistant;

defined('MOODLE_INTERNAL') || die();

class stdio_communicator {
    private $binary_path;
    
    public function __construct() {
        // Path to the mounted binary in Docker
        $this->binary_path = '/bitnami/moodle/openai-sidecar/openai-moodle-sidecar';
    }
    
    /**
     * Send request to Rust sidecar via stdio
     */
    public function send_request($request) {
        // Encode request as JSON
        $json_request = json_encode($request);
        if ($json_request === false) {
            return false;
        }
        
        // Check if binary exists
        if (!file_exists($this->binary_path)) {
            error_log("OpenAI sidecar binary not found at: " . $this->binary_path);
            return false;
        }
        
        // Check if binary is executable
        if (!is_executable($this->binary_path)) {
            error_log("OpenAI sidecar binary is not executable: " . $this->binary_path);
            return false;
        }
        
        // Prepare command - environment variables are already in the container
        $cmd = escapeshellarg($this->binary_path);
        
        // Open process
        $descriptorspec = [
            0 => ['pipe', 'r'], // stdin
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w'], // stderr
        ];
        
        $process = proc_open($cmd, $descriptorspec, $pipes);
        
        if (!is_resource($process)) {
            error_log("Failed to start OpenAI sidecar process");
            return false;
        }
        
        // Send request
        fwrite($pipes[0], $json_request . "\n");
        fclose($pipes[0]);
        
        // Read response
        $response = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        
        // Read stderr for debugging
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        
        // Close process
        $return_code = proc_close($process);
        
        if ($return_code !== 0) {
            error_log("OpenAI sidecar error (code $return_code): $stderr");
            return false;
        }
        
        return trim($response);
    }
}