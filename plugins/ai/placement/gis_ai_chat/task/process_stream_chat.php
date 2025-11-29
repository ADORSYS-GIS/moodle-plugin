<?php
// This file is part of Moodle - http://moodle.org/

declare(strict_types=1);

namespace aiplacement_gis_ai_chat\task;

defined('MOODLE_INTERNAL') || die();

use core\task\scheduled_task;
use aiprovider_gis_ai\helpers\logger;

/**
 * Ad-hoc task to process streaming chat requests.
 */
class process_stream_chat extends scheduled_task {

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
    public function __construct(string $streamid, string $prompt, int $contextid) {
        $this->streamid = $streamid;
        $this->prompt = $prompt;
        $this->contextid = $contextid;
    }

    /**
     * Execute the task.
     */
    public function execute(): void {
        global $DB;

        try {
            // Get stream metadata from cache.
            $cache = \cache::make('aiplacement_gis_ai_chat', 'streams');
            $streamData = $cache->get($this->streamid);

            if (!$streamData) {
                logger::error('Stream data not found', ['streamid' => $this->streamid]);
                return;
            }

            // Update stream status.
            $streamData['status'] = 'processing';
            $cache->set($this->streamid, $streamData);

            // Process the AI request.
            $action = new \core_ai\aiactions\generate_text(
                contextid: $this->contextid,
                userid: $streamData['userid'],
                prompttext: $this->prompt
            );

            /** @var \core_ai\manager $manager */
            $manager = \core\di::get(\core_ai\manager::class);
            $response = $manager->process_action($action);

            // Extract content.
            $content = null;
            if (is_object($response)) {
                if (method_exists($response, 'get_text')) {
                    $content = (string)$response->get_text();
                } elseif (method_exists($response, '__toString')) {
                    $content = (string)$response;
                }
            }

            // Save conversation if conversation ID provided.
            if (!empty($streamData['conversationid']) && $content) {
                $record = (object)[
                    'conversationid' => $streamData['conversationid'],
                    'userid' => $streamData['userid'],
                    'message' => $content,
                    'role' => 'assistant',
                    'timecreated' => time(),
                ];
                $DB->insert_record('aiplacement_gis_ai_chat_messages', $record);
            }

            // Update stream with result.
            $streamData['status'] = 'completed';
            $streamData['content'] = $content ?? '';
            $streamData['completed'] = time();
            $cache->set($this->streamid, $streamData);

            logger::info('Stream processing completed', [
                'streamid' => $this->streamid,
                'content_length' => strlen($content ?? ''),
            ]);

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
     * Get task name.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_process_stream_chat', 'aiplacement_gis_ai_chat');
    }
}
