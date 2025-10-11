<?php
namespace local_aiassistant;

defined('MOODLE_INTERNAL') || die();

class rust_process_manager {
    // Static properties persist for the lifetime of a single PHP-FPM worker process.
    private static $process = null;
    private static $pipes = [];
    private static $is_ready = false;
    private static $rust_binary_path;

    // Private constructor to enforce the Singleton pattern.
    private function __construct() {
        self::$rust_binary_path = get_config('local_aiassistant', 'rust_binary_path');
        // Register a function to be called when the PHP script finishes.
        // This ensures our Rust process is cleaned up properly.
        register_shutdown_function([$this, 'cleanup']);
    }

    // The public entry point to get the one and only instance of this manager.
    public static function get_instance() {
        static $instance = null;
        if ($instance === null) {
            $instance = new self();
        }
        return $instance;
    }

    /**
     * The primary public method for sending a request to the Rust AI processor.
     * It accepts all the fields defined in our AIRequest Rust struct.
     *
     * @param int $userid The Moodle user ID.
     * @param string $message The user's primary message.
     * @param array $context Associative array of context (e.g., ['course_id' => 123]).
     * @param array $history A list of previous conversation turns.
     * @param string|null $large_input Optional large block of text.
     * @return array The decoded JSON response from the Rust process.
     * @throws \Exception On fatal errors or timeouts.
     */
    public function process_request(
        int $userid,
        string $message,
        array $context,
        array $history,
        ?string $large_input
    ) {
        // First, ensure the Rust process is running and has signaled it's ready.
        $this->ensure_process_running();
        
        // Construct the request object, matching the Rust struct perfectly.
        $request_id = uniqid('moodle_req_', true);
        $request = [
            'id' => $request_id,
            'user_id' => $userid,
            'message' => $message,
            'context' => (object)$context, // Cast to object for JSON compatibility.
            'conversation_history' => $history,
            'large_input_text' => $large_input,
        ];
        // Encode to JSON and add a newline, which is our message delimiter.
        $json_request = json_encode($request) . "\n";
        
        // A simple retry mechanism adds significant stability.
        for ($attempt = 1; $attempt <= 2; $attempt++) {
            try {
                // The '@' suppresses standard PHP warnings, as we handle errors ourselves.
                $bytes_written = @fwrite(self::$pipes[0], $json_request);
                if ($bytes_written === false || $bytes_written < strlen($json_request)) {
                    throw new \Exception("Failed to write full request to Rust process stdin pipe.");
                }
                // If write is successful, wait for the response.
                return $this->read_response($request_id, 120); // 2-minute timeout for AI.
            } catch (\Exception $e) {
                debugging("AI Assistant: Communication with Rust failed on attempt {$attempt}: " . $e->getMessage(), DEBUG_NORMAL);
                if ($attempt < 2) {
                    $this->restart_process(); // On first failure, restart the process and retry.
                } else {
                    // If the second attempt also fails, give up and throw a user-friendly error.
                    throw new \Exception(get_string('ai_service_unavailable', 'local_aiassistant'));
                }
            }
        }
    }

    /**
     * Checks if the Rust process is alive and ready; starts it if not.
     */
    private function ensure_process_running() {
        if (self::$process !== null && is_resource(self::$process)) {
            $status = proc_get_status(self::$process);
            if ($status && $status['running']) {
                if (!self::$is_ready) {
                    // It's running but hasn't sent the ready signal yet.
                    $this->wait_for_ready_signal();
                }
                return; // Everything is OK.
            }
        }
        // If we reach here, the process is dead or was never started.
        $this->start_process();
    }

    /**
     * Starts the Rust child process using proc_open.
     */
    private function start_process() {
        if (!file_exists(self::$rust_binary_path) || !is_executable(self::$rust_binary_path)) {
            debugging("AI Assistant: Rust binary not found or not executable at '" . self::$rust_binary_path . "'", DEBUG_CRITICAL);
            throw new \Exception("Rust AI binary is not configured correctly.");
        }

        debugging("AI Assistant: Starting new Rust AI process for this PHP-FPM worker.", DEBUG_NORMAL);
        
        $descriptorspec = [
            0 => ["pipe", "r"],  // stdin - PHP writes to this.
            1 => ["pipe", "w"],  // stdout - PHP reads from this.
            2 => ["pipe", "w"]   // stderr - PHP reads this for Rust's logs.
        ];

        // Pass the API key from Moodle's secure config to the Rust process
        // as a command-line argument. This is more direct than environment variables here.
        $api_key = get_config('local_aiassistant', 'openai_api_key');
        if (empty($api_key)) {
            throw new \Exception("OpenAI API Key is not set in AI Assistant plugin settings.");
        }
        
        // escapeshellarg is a CRITICAL security function to prevent command injection.
        $command = self::$rust_binary_path . ' ' . escapeshellarg($api_key);

        self::$process = proc_open($command, $descriptorspec, self::$pipes);
        
        if (!is_resource(self::$process)) {
            throw new \Exception("Failed to start Rust AI process. Check web server permissions and command: " . $command);
        }
        
        // Set the communication pipes to non-blocking mode. This prevents PHP
        // from freezing if it tries to read from an empty pipe.
        stream_set_blocking(self::$pipes[1], false); // stdout
        stream_set_blocking(self::$pipes[2], false); // stderr
        self::$is_ready = false;
        $this->wait_for_ready_signal();
    }

    /**
     * Listens on the stdout pipe for the initial "ready" JSON message from Rust.
     */
    private function wait_for_ready_signal($timeout_seconds = 30) {
        debugging("AI Assistant: Waiting for Rust AI process to signal readiness...", DEBUG_NORMAL);
        $start_time = microtime(true);
        $buffer = '';
        
        while ((microtime(true) - $start_time) < $timeout_seconds) {
            $status = proc_get_status(self::$process);
            if (!$status['running']) {
                $stderr = stream_get_contents(self::$pipes[2]);
                throw new \Exception("Rust AI process terminated unexpectedly during startup. STDERR: " . $stderr);
            }
            
            // Read any available data from both stdout and stderr.
            $buffer .= @fread(self::$pipes[1], 8192);
            $stderr_output = @fread(self::$pipes[2], 8192);
            if (!empty($stderr_output)) {
                debugging("Rust STDERR (startup): " . trim($stderr_output), DEBUG_NORMAL);
            }

            // Check if we have a complete line (ending in a newline) in our buffer.
            if (($newline_pos = strpos($buffer, "\n")) !== false) {
                $line = substr($buffer, 0, $newline_pos);
                $message = json_decode($line, true);
                if ($message && isset($message['status']) && $message['status'] === 'ready') {
                    debugging("AI Assistant: Rust AI process is ready! Message: " . $message['message'], DEBUG_NORMAL);
                    self::$is_ready = true;
                    return; // Success!
                }
            }
            usleep(50000); // Wait 50ms before checking again to avoid busy-looping.
        }
        throw new \Exception("Timeout waiting for Rust AI process to become ready.");
    }
    
    /**
     * Listens on the stdout pipe for a specific response matching the request ID.
     */
    private function read_response(string $request_id, int $timeout_seconds) {
        $start_time = microtime(true);
        $buffer = '';
        
        while ((microtime(true) - $start_time) < $timeout_seconds) {
            $status = proc_get_status(self::$process);
            if (!$status['running']) {
                $stderr = stream_get_contents(self::$pipes[2]);
                throw new \Exception("Rust process died while waiting for response. STDERR: " . $stderr);
            }
            
            $buffer .= @fread(self::$pipes[1], 8192);
            $stderr_output = @fread(self::$pipes[2], 8192);
            if (!empty($stderr_output)) {
                debugging("Rust STDERR (runtime): " . trim($stderr_output), DEBUG_NORMAL);
            }

            // Process every complete line we have in the buffer.
            while (($newline_pos = strpos($buffer, "\n")) !== false) {
                $line = substr($buffer, 0, $newline_pos);
                $buffer = substr($buffer, $newline_pos + 1); // Keep the rest for the next loop.
                
                $response = json_decode($line, true);
                // Check if the JSON is valid and if the ID matches the one we're waiting for.
                if ($response && isset($response['id']) && $response['id'] === $request_id) {
                    return $response; // Found our response!
                }
            }
            usleep(50000); // Wait 50ms before checking again.
        }
        
        throw new \Exception("Timeout waiting for response with ID '{$request_id}' from Rust process.");
    }
    
    /**
     * Forcefully restarts the Rust process. Called after a communication failure.
     */
    private function restart_process() {
        debugging("AI Assistant: Restarting Rust AI process...", DEBUG_NORMAL);
        $this->cleanup();
        $this->start_process();
    }

    /**
     * Cleans up the running process and its pipes.
     */
    public function cleanup() {
        if (self::$process !== null && is_resource(self::$process)) {
            // Close the pipes first.
            if (!empty(self::$pipes)) {
                @fclose(self::$pipes[0]); // stdin
                @fclose(self::$pipes[1]); // stdout
                @fclose(self::$pipes[2]); // stderr
            }
            // Terminate the process.
            proc_terminate(self::$process);
            proc_close(self::$process);
        }
        self::$process = null;
        self::$pipes = [];
        self::$is_ready = false;
    }
}