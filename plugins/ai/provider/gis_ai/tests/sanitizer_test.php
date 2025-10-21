<?php
// This file is part of Moodle - http://moodle.org/

declare(strict_types=1);

use aiprovider_gis_ai\helpers\sanitizer;

defined('MOODLE_INTERNAL') || die();

final class aiprovider_gis_ai_sanitizer_test extends \basic_testcase {
    public function test_sanitize_prompt_masks_bad_words(): void {
        // Arrange
        putenv('BAD_WORDS_LIST=foo,bar');
        $input = 'This Foo is not allowed. Bars are not allowed either!';

        // Act
        $out = sanitizer::sanitize_prompt($input, 200);

        // Assert
        $this->assertStringContainsString('***', $out);
        $this->assertStringNotContainsString('Foo', $out);
        $this->assertStringNotContainsString('Bars', $out);
    }
}
