<?php
// This file is part of Moodle - http://moodle.org/

declare(strict_types=1);

defined('MOODLE_INTERNAL') || die();

use aiprovider_gis_ai\api\response_normalizer;

final class aiprovider_gis_ai_response_normalizer_test extends \basic_testcase {
    public function test_process_responses_shape(): void {
        $raw = [
            'output' => [
                ['content' => [['text' => 'Hello']]],
                ['text' => 'World'],
            ],
            'usage' => ['total_tokens' => 42],
        ];
        $norm = response_normalizer::process($raw);
        $this->assertSame("Hello\n\nWorld", $norm['content']);
        $this->assertSame(42, $norm['tokens']);
    }

    public function test_process_choices_shape(): void {
        $raw = [
            'choices' => [
                ['message' => ['content' => 'Hi there']],
            ],
            'usage' => ['tokens' => 5],
        ];
        $norm = response_normalizer::process($raw);
        $this->assertSame('Hi there', $norm['content']);
        $this->assertSame(5, $norm['tokens']);
    }
}
