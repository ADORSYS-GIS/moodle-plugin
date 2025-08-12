<?php
namespace local_ai_openai_v1;

defined('MOODLE_INTERNAL') || die();

class process_manager {
    private static $process = null;
    private static $pipes = null;
    private static $is_ready = false;

    private function __construct() { }

    public static function get_instance() {
        static $instance = null;
        if ($instance === null) {
            $instance = new self();
        }
        $instance->ensure_process_running();
        return $instance;
    }

    private function ensure_process_running() {
        if (self::$process !== null && is_resource(self::$process)) {
            $status = proc_get_status(self::$process);
            if ($status['running']) {
                if (self::$is_ready) {
                    return;
                }
                $this->wait_for_ready_signal();
                return;
            }
        }
        $this->start_process();
    }

    private function start_process() {
        debugging("AI Provider: Starting new Rust AI process.", DEBUG_NORMAL);

        $rust_binary_path = get_config('local_ai_openai_v1', 'rust_binary_path');
        if (!file_exists($rust_binary_path) || !is_executable($rust_binary_path)) {
            throw new \Exception("Rust binary not found or not executable at: {$rust_binary_path}");
        }

        $descriptorspec = [
            0 => ["pipe", "r"], // stdin
            1 => ["pipe", "w"], // stdout
            2 => ["pipe", "w"]  // stderr
        ];

        // Pass Moodle config to Rust via environment variables
        $env = [
            'OPENAI_API_KEY' => get_config('local_ai_openai_v1', 'api_key'),
            'OPENAI_BASE_URL' => get_config('local_ai_openai_v1', 'base_url'),
            'RUST_LOG' => 'info' // Optional: for rust logging crates
        ];

        self::$process = proc_open($rust_binary_path, $descriptorspec, $pipes, null, $env);

        if (!is_resource(self::$process)) {
            throw new \Exception("Failed to start Rust AI process.");
        }

        self::$pipes = $pipes;
        stream_set_blocking(self::$pipes[1], false);
        stream_set_blocking(self::$pipes[2], false);
        self::$is_ready = false;
        $this->wait_for_ready_signal();
    }

    private function wait_for_ready_signal($timeout = 10) {
        if (self::$is_ready) return;

        $start = microtime(true);
        while (microtime(true) - $start < $timeout) {
            $stdout = stream_get_contents(self::$pipes[1]);
            if ($stdout) {
                $response = json_decode(trim($stdout), true);
                if ($response && isset($response['status']) && $response['status'] === 'ready') {
                    self::$is_ready = true;
                    debugging("AI Provider: Rust process is ready.", DEBUG_NORMAL);
                    return;
                }
            }
            $stderr = stream_get_contents(self::$pipes[2]);
            if ($stderr) {
                debugging("Rust STDERR (startup): " . trim($stderr), DEBUG_NORMAL);
            }
            usleep(50000); // 50ms
        }
        throw new \Exception("Timeout waiting for Rust AI process to become ready.");
    }

    public function process_prompt(string $prompt): array {
        $this->ensure_process_running();
        $request_id = uniqid('ai_req_', true);

        $request = [
            'id' => $request_id,
            'prompt' => $prompt,
        ];
        $json_request = json_encode($request) . "\n";

        fwrite(self::$pipes[0], $json_request);
        fflush(self::$pipes[0]);

        return $this->read_response($request_id, 60); // 60-second timeout
    }

    private function read_response(string $request_id, int $timeout) {
        $start = microtime(true);
        $buffer = '';
        while (microtime(true) - $start < $timeout) {
            $buffer .= stream_get_contents(self::$pipes[1]);
            if (($newline_pos = strpos($buffer, "\n")) !== false) {
                $line = substr($buffer, 0, $newline_pos);
                $buffer = substr($buffer, $newline_pos + 1);
                $response = json_decode(trim($line), true);
                if ($response && isset($response['id']) && $response['id'] === $request_id) {
                    return $response;
                }
            }
            $stderr = stream_get_contents(self::$pipes[2]);
            if ($stderr) {
                debugging("Rust STDERR (runtime): " . trim($stderr), DEBUG_NORMAL);
            }
            usleep(50000);
        }
        throw new \Exception("Timeout waiting for response with ID '{$request_id}'.");
    }

    public function __destruct() {
        if (self::$process !== null && is_resource(self::$process)) {
            proc_terminate(self::$process);
            self::$process = null;
        }
    }
}