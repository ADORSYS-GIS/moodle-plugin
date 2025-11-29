<?php
// This file is part of Moodle - http://moodle.org/

declare(strict_types=1);

namespace aiplacement_gis_ai_chat\task;

defined('MOODLE_INTERNAL') || die();

use core\task\adhoc_task;
use aiprovider_gis_ai\helpers\logger;

/**
 * Ad-hoc task to process streaming chat requests.
 */
class process_stream_chat extends adhoc_task {

    /** @var string Stream ID */
    private $streamid;
    
    /** @var string Prompt text */
    private $prompt;
    
    /** @var int Context ID */
    private $contextid;

    /**
     * Constructor.
     *
     * @param string $streamid Stream ID
     * @param string $prompt Prompt text
     * @param int $contextid Context ID
     */
    public function __construct(string $streamid = '', string $prompt = '', int $contextid = 0) {
        $this->streamid = $streamid;
        $this->prompt = $prompt;
        $this->contextid = $contextid;
        
        // Set custom data for ad-hoc task
        $this->set_custom_data([
            'streamid' => $streamid,
            'prompt' => $prompt,
            'contextid' => $contextid,
        ]);
    }

    /**
     * Execute the task.
     */
    public function execute(): void {
        global $DB;

        try {
            // Get custom data from ad-hoc task
            $customData = $this->get_custom_data();
            if (!$customData) {
                logger::error('No custom data found in streaming task');
                return;
            }

            $this->streamid = $customData->streamid;
            $this->prompt = $customData->prompt;
            $this->contextid = $customData->contextid;

            // Get stream metadata from cache.
            $cache = \cache::make('aiplacement_gis_ai_chat', 'streams');
            $streamData = $cache->get($this->streamid);

            if (!$streamData) {
                logger::error('Stream data not found', ['streamid' => $this->streamid]);
                return;
            }

            // Update stream status to processing
            $streamData['status'] = 'processing';
            $cache->set($this->streamid, $streamData);

            // Process the AI request with streaming support
            $action = new \core_ai\aiactions\generate_text(
                contextid: $this->contextid,
                userid: $streamData['userid'],
                prompttext: $this->prompt
            );

            /** @var \core_ai\manager $manager */
            $manager = \core\di::get(\core_ai\manager::class);
            
            // Check if streaming is enabled in provider
            $enableStreaming = \aiprovider_gis_ai\helpers\env_loader::get('AI_ENABLE_STREAMING', '1') === '1';
            
            if ($enableStreaming && method_exists($manager, 'process_action_stream')) {
                // Use streaming processing if available
                $this->process_with_streaming($manager, $action, $cache, $streamData);
            } else {
                // Fallback to regular processing
                $this->process_regular($manager, $action, $cache, $streamData);
            }

        } catch (\Throwable $e) {
            logger::exception($e, 'Stream processing failed');

            // Update stream with error.
            if (isset($cache) && isset($this->streamid)) {
                $streamData = $cache->get($this->streamid) ?: [];
                $streamData['status'] = 'error';
                $streamData['error'] = $e->getMessage();
                $streamData['completed'] = time();
                $cache->set($this->streamid, $streamData);
            }
        }
    }

    /**
     * Process AI request with streaming support.
     */
    private function process_with_streaming($manager, $action, $cache, $streamData): void {
        // Update status to streaming
        $streamData['status'] = 'streaming';
        $cache->set($this->streamid, $streamData);

        // Simulate streaming by processing in chunks
        // In a real implementation, this would interface with a streaming AI API
        $response = $manager->process_action($action);
        
        $content = null;
        if (is_object($response)) {
            if (method_exists($response, 'get_text')) {
                $content = (string)$response->get_text();
            } elseif (method_exists($response, '__toString')) {
                $content = (string)$response;
            }
        }

        if ($content) {
            // Stream content in chunks
            $chunkSize = 50;
            $contentLength = strlen($content);
            
            for ($i = 0; $i < $contentLength; $i += $chunkSize) {
                $chunk = substr($content, $i, $chunkSize);
                $streamData['partial_content'] = $chunk;
                $cache->set($this->streamid, $streamData);
                
                // Small delay to simulate streaming
                usleep(100000); // 100ms
            }
        }

        // Final completion
        $streamData['status'] = 'completed';
        $streamData['content'] = $content ?? '';
        $streamData['completed'] = time();
        unset($streamData['partial_content']);
        $cache->set($this->streamid, $streamData);

        // Save conversation if conversation ID provided
        if (!empty($streamData['conversationid']) && $content) {
            $this->save_conversation_message($streamData['conversationid'], $streamData['userid'], $content);
        }
    }

    /**
     * Process AI request regularly (non-streaming).
     */
    private function process_regular($manager, $action, $cache, $streamData): void {
        $response = $manager->process_action($action);
        
        $content = null;
        if (is_object($response)) {
            if (method_exists($response, 'get_text')) {
                $content = (string)$response->get_text();
            } elseif (method_exists($response, '__toString')) {
                $content = (string)$response;
            }
        }

        // Save conversation if conversation ID provided
        if (!empty($streamData['conversationid']) && $content) {
            $this->save_conversation_message($streamData['conversationid'], $streamData['userid'], $content);
        }

        // Update stream with result
        $streamData['status'] = 'completed';
        $streamData['content'] = $content ?? '';
        $streamData['completed'] = time();
        $cache->set($this->streamid, $streamData);
    }

    /**
     * Save conversation message.
     */
    private function save_conversation_message(string $conversationid, int $userid, string $message): void {
        global $DB;
        
        $record = (object)[
            'conversationid' => $conversationid,
            'userid' => $userid,
            'message' => $message,
            'role' => 'assistant',
            'timecreated' => time(),
        ];
        $DB->insert_record('aiplacement_gis_ai_chat_messages', $record);
    }

    /**
     * Get task name.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_process_stream_chat', 'aiplacement_gis_ai_chat');
    }
}
