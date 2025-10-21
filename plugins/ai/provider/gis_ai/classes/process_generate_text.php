<?php
// This file is part of Moodle - http://moodle.org/

declare(strict_types=1);

namespace aiprovider_gis_ai;

defined('MOODLE_INTERNAL') || die();

use aiprovider_gis_ai\helpers\sanitizer;
use aiprovider_gis_ai\helpers\env_loader;
use aiprovider_gis_ai\api\http_client;
use aiprovider_gis_ai\api\rust_bridge;
use aiprovider_gis_ai\api\response_normalizer;
use aiprovider_gis_ai\analytics\usage_tracker;

/**
 * Processor for core_ai\aiactions\generate_text.
 *
 * Note: We keep return type generic; placement extracts text via get_text()/__toString().
 */
final class process_generate_text extends abstract_processor {
    /**
     * Process the action and return a simple response object with get_text().
     * @param object $action The action instance (expected: \core_ai\aiactions\generate_text)
     * @return object
     */
    public function process($action) {
        global $USER, $DB;

        // Extract fields in a defensive way to avoid tight coupling.
        $prompt = null;
        if (is_object($action)) {
            if (method_exists($action, 'get_prompttext')) {
                $prompt = (string)$action->get_prompttext();
            } elseif (property_exists($action, 'prompttext')) {
                $prompt = (string)$action->prompttext;
            } elseif (property_exists($action, 'prompt')) {
                $prompt = (string)$action->prompt;
            }
        }
        $prompt = (string)($prompt ?? '');
        $prompt = sanitizer::sanitize_prompt($prompt);

        $userid = 0;
        if (is_object($action)) {
            if (method_exists($action, 'get_userid')) {
                $userid = (int)$action->get_userid();
            } elseif (property_exists($action, 'userid')) {
                $userid = (int)$action->userid;
            }
        }
        $contextid = 0;
        if (is_object($action)) {
            if (method_exists($action, 'get_contextid')) {
                $contextid = (int)$action->get_contextid();
            } elseif (property_exists($action, 'contextid')) {
                $contextid = (int)$action->contextid;
            }
        }

        $useremail = '';
        try {
            if ($userid > 0) {
                $useremail = (string)($DB->get_field('user', 'email', ['id' => $userid]) ?: '');
            }
            if ($useremail === '' && isset($USER->email)) {
                $useremail = (string)$USER->email;
            }
            if ($useremail !== '') {
                $useremail = sanitizer::sanitize_email($useremail);
            }
        } catch (\Throwable $e) {
            // If email resolution fails, proceed without it.
            $useremail = '';
        }

        // Decide backend: rust_bridge in ffi/api mode, otherwise HTTP client.
        $mode = strtolower(env_loader::get('AI_RUST_MODE', ''));
        $raw = [];
        if ($mode === 'ffi' || $mode === 'api') {
            $raw = rust_bridge::send_prompt($prompt, $useremail, []);
        } else {
            $raw = http_client::send_prompt($prompt, $useremail, [], false);
        }

        $norm = response_normalizer::process($raw);
        // Log usage best-effort.
        try {
            usage_tracker::log_interaction($userid ?: (int)($USER->id ?? 0), $prompt, $norm, true, $contextid, 0);
        } catch (\Throwable $e) {
            // Non-fatal.
        }

        // Return a lightweight response wrapper exposing get_text and __toString.
        return new class($norm['content']) {
            private string $text;
            public function __construct(string $text) { $this->text = $text; }
            public function get_text(): string { return $this->text; }
            public function __toString(): string { return $this->text; }
        };
    }
}
