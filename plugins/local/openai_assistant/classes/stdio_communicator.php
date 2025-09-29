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
            $err = json_encode(['success' => false, 'error' => 'Failed to encode request to JSON']);
            error_log("OpenAI sidecar: failed to encode request to JSON");
            return $err;
        }
        
        // Check if binary exists
        if (!file_exists($this->binary_path)) {
            $err_msg = "OpenAI sidecar binary not found at: " . $this->binary_path;
            error_log($err_msg);
            return json_encode(['success' => false, 'error' => $err_msg]);
        }
        
        // Check if binary is executable
        if (!is_executable($this->binary_path)) {
            $err_msg = "OpenAI sidecar binary is not executable: " . $this->binary_path;
            error_log($err_msg);
            return json_encode(['success' => false, 'error' => $err_msg]);
        }
        
        // Build command with CLI flags from environment when available
        $cmd = escapeshellarg($this->binary_path);
        $api_key = getenv('OPENAI_API_KEY');
        $base_url = getenv('OPENAI_BASE_URL');
        $model = getenv('OPENAI_MODEL');
        $max_tokens = getenv('MAX_TOKENS');
        $summarize_threshold = getenv('SUMMARIZE_THRESHOLD');

        if ($api_key) {
            $cmd .= ' --api-key=' . escapeshellarg($api_key);
        }
        if ($base_url) {
            $cmd .= ' --base-url=' . escapeshellarg($base_url);
        }
        if ($model) {
            $cmd .= ' --model=' . escapeshellarg($model);
        }
        if ($max_tokens) {
            $cmd .= ' --max-tokens=' . escapeshellarg($max_tokens);
        }
        if ($summarize_threshold) {
            $cmd .= ' --summarize-threshold=' . escapeshellarg($summarize_threshold);
        }

        // Prepare a masked version for logging so secrets are not exposed
        $cmd_log = $cmd;
        if ($api_key) {
            // Replace the quoted api key value with <REDACTED>
            $cmd_log = preg_replace("/(--api-key=)('.*?')/", "$1'<REDACTED>'", $cmd_log);
        }
        error_log("OpenAI sidecar command: " . $cmd_log);
        
        // Open process
        $descriptorspec = [
            0 => ['pipe', 'r'], // stdin
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w'], // stderr
        ];
        
        $process = proc_open($cmd, $descriptorspec, $pipes);
        
        if (!is_resource($process)) {
            $err_msg = "Failed to start OpenAI sidecar process";
            error_log($err_msg);
            return json_encode(['success' => false, 'error' => $err_msg]);
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
            $err_msg = "OpenAI sidecar error (code $return_code). Cmd: $cmd. Stderr: $stderr";
            error_log($err_msg);
            return json_encode(['success' => false, 'error' => $err_msg]);
        }
        
        return trim($response);
    }
}