<?php
namespace local_openai_assistant;

defined('MOODLE_INTERNAL') || die();

class stdio_communicator {
    private $binary_path;
    private $http_url;
    private $http_timeout;
    private $connection_timeout;
    
    public function __construct() {
        // Path to the mounted binary in Docker
        $this->binary_path = '/bitnami/moodle/openai-sidecar/openai-moodle-sidecar';
        
        // HTTP configuration with better defaults
        $this->http_url = getenv('OPENAI_SIDECAR_HTTP_URL') ?: 'http://127.0.0.1:8081/ai';
        $this->http_timeout = intval(getenv('OPENAI_SIDECAR_HTTP_TIMEOUT') ?: '30');
        $this->connection_timeout = intval(getenv('OPENAI_SIDECAR_CONNECT_TIMEOUT') ?: '5');
    }
    
    /**
     * Send request to Rust sidecar - HTTP first, stdio fallback
     */
    public function send_request($request) {
        // Encode request as JSON
        $json_request = json_encode($request);
        if ($json_request === false) {
            error_log("OpenAI sidecar: failed to encode request to JSON");
            return json_encode(['success' => false, 'error' => 'Failed to encode request to JSON']);
        }
        
        // Try HTTP first (more efficient)
        $http_response = $this->try_http_request($json_request);
        if ($http_response !== false) {
            return $http_response;
        }
        
        // Fallback to stdio if HTTP fails
        error_log("OpenAI sidecar: HTTP failed, falling back to stdio mode");
        return $this->try_stdio_request($json_request);
    }
    
    /**
     * Attempt HTTP request to long-running server
     */
    private function try_http_request($json_request) {
        $ch = curl_init($this->http_url);
        if ($ch === false) {
            error_log("OpenAI sidecar: Failed to initialize cURL");
            return false;
        }
        
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $json_request,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => $this->connection_timeout,
            CURLOPT_TIMEOUT => $this->http_timeout,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => false, // For local development
        ]);
        
        $response = curl_exec($ch);
        $curl_errno = curl_errno($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($response !== false && $http_code >= 200 && $http_code < 300) {
            error_log("OpenAI sidecar: HTTP request successful (code: {$http_code})");
            return trim($response);
        }
        
        error_log("OpenAI sidecar HTTP failed: errno={$curl_errno}, http_code={$http_code}, error={$curl_error}");
        return false;
    }
    
    /**
     * Fallback stdio request (spawns process)
     */
    private function try_stdio_request($json_request) {
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
        
        // Build command - force stdio mode to avoid HTTP server conflicts
        $cmd = escapeshellarg($this->binary_path);
        $this->add_env_args($cmd);

        // Prepare a masked version for logging
        $cmd_log = $this->mask_sensitive_args($cmd);
        error_log("OpenAI sidecar stdio command: " . $cmd_log);
        
        // Open process with stdio pipes
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
        
        // Send request and close stdin
        fwrite($pipes[0], $json_request . "\n");
        fclose($pipes[0]);
        
        // Read response with timeout
        stream_set_timeout($pipes[1], $this->http_timeout);
        $response = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        
        // Read stderr for debugging
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        
        // Close process
        $return_code = proc_close($process);
        
        if ($return_code !== 0) {
            $err_msg = "OpenAI sidecar stdio error (code $return_code). Stderr: $stderr";
            error_log($err_msg);
            return json_encode(['success' => false, 'error' => $err_msg]);
        }
        
        if (!empty($stderr)) {
            error_log("OpenAI sidecar stderr: " . $stderr);
        }
        
        return trim($response);
    }
    
    /**
     * Add environment-based arguments to command
     */
    private function add_env_args(&$cmd) {
        $env_mappings = [
            'OPENAI_API_KEY' => '--api-key',
            'OPENAI_BASE_URL' => '--base-url',
            'OPENAI_MODEL' => '--model',
            'MAX_TOKENS' => '--max-tokens',
            'SUMMARIZE_THRESHOLD' => '--summarize-threshold'
        ];
        
        foreach ($env_mappings as $env_var => $arg_name) {
            $value = getenv($env_var);
            if ($value !== false && $value !== '') {
                $cmd .= ' ' . $arg_name . '=' . escapeshellarg($value);
            }
        }
    }
    
    /**
     * Mask sensitive information in command for logging
     */
    private function mask_sensitive_args($cmd) {
        return preg_replace("/(--api-key=)('.*?')/", "$1'<REDACTED>'", $cmd);
    }
}