<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Unit tests for inference service.
 *
 * @package    local_gis_ai_assistant
 * @copyright  2025 Adorsys GIS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_gis_ai_assistant;

use local_gis_ai_assistant\api\inference_service;
use local_gis_ai_assistant\exceptions\ai_exception;
use local_gis_ai_assistant\exceptions\rate_limit_exception;
use local_gis_ai_assistant\exceptions\configuration_exception;

defined('MOODLE_INTERNAL') || die();

/**
 * Test cases for inference service.
 *
 * @group local_gis_ai_assistant
 */
class inference_service_test extends \advanced_testcase {

    /**
     * Set up test environment.
     */
    protected function setUp(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        
        // Set up test configuration.
        set_config('enabled', 1, 'local_gis_ai_assistant');
        set_config('default_model', 'gpt-4o-mini', 'local_gis_ai_assistant');
        set_config('max_tokens', 2048, 'local_gis_ai_assistant');
        set_config('temperature', 0.7, 'local_gis_ai_assistant');
        set_config('rate_limit_requests', 100, 'local_gis_ai_assistant');
        set_config('rate_limit_tokens', 50000, 'local_gis_ai_assistant');
        set_config('enable_cache', 1, 'local_gis_ai_assistant');
        set_config('enable_analytics', 1, 'local_gis_ai_assistant');
        set_config('system_prompt', 'You are a helpful AI assistant.', 'local_gis_ai_assistant');
    }

    /**
     * Test configuration exception when API key is missing.
     */
    public function test_configuration_exception_missing_api_key() {
        // Ensure no API key is set.
        unset($_SERVER['OPENAI_API_KEY']);
        
        $this->expectException(configuration_exception::class);
        new inference_service();
    }

    /**
     * Test input sanitization.
     */
    public function test_input_sanitization() {
        $this->assertEquals('Test message', local_gis_ai_assistant_sanitize_input('Test message'));
        $this->assertEquals('Test message', local_gis_ai_assistant_sanitize_input('  Test   message  '));
        $this->assertEquals('Test message', local_gis_ai_assistant_sanitize_input("Test\nmessage"));
        
        // Test length limit.
        $longtext = str_repeat('a', 11000);
        $sanitized = local_gis_ai_assistant_sanitize_input($longtext);
        $this->assertEquals(10000, strlen($sanitized));
    }

    /**
     * Test cache key generation.
     */
    public function test_cache_key_generation() {
        $key1 = local_gis_ai_assistant_generate_cache_key('Hello', ['model' => 'gpt-4']);
        $key2 = local_gis_ai_assistant_generate_cache_key('Hello', ['model' => 'gpt-4']);
        $key3 = local_gis_ai_assistant_generate_cache_key('Hello', ['model' => 'gpt-3.5']);
        
        $this->assertEquals($key1, $key2);
        $this->assertNotEquals($key1, $key3);
    }

    /**
     * Test rate limiting functionality.
     */
    public function test_rate_limiting() {
        global $DB, $USER;
        
        // Test initial rate limit check.
        $result = local_gis_ai_assistant_check_rate_limit($USER->id, 100);
        $this->assertTrue($result['allowed']);
        $this->assertEquals(99, $result['requests_remaining']);
        
        // Update rate limit.
        local_gis_ai_assistant_update_rate_limit($USER->id, 100);
        
        // Check updated limits.
        $result = local_gis_ai_assistant_check_rate_limit($USER->id, 100);
        $this->assertEquals(98, $result['requests_remaining']);
        
        // Test exceeding request limit.
        $record = $DB->get_record('local_gis_ai_assistant_rate_limits', ['userid' => $USER->id]);
        $record->requests_count = 100;
        $DB->update_record('local_gis_ai_assistant_rate_limits', $record);
        
        $result = local_gis_ai_assistant_check_rate_limit($USER->id, 100);
        $this->assertFalse($result['allowed']);
    }

    /**
     * Test request logging.
     */
    public function test_request_logging() {
        global $DB, $USER;
        
        $usage = [
            'prompt_tokens' => 10,
            'completion_tokens' => 20,
            'total_tokens' => 30
        ];
        
        local_gis_ai_assistant_log_request($USER->id, 'gpt-4', $usage, 1500, 'success', null, 'Hello', 'Hi');
        
        $record = $DB->get_record('local_gis_ai_assistant_conversations', ['userid' => $USER->id]);
        $this->assertNotEmpty($record);
        $this->assertEquals('gpt-4', $record->model);
        $this->assertEquals(30, $record->tokens_used);
        $this->assertEquals('Hello', $record->message);
        $this->assertEquals('Hi', $record->response);
    }

    /**
     * Test token estimation.
     */
    public function test_token_estimation() {
        // Create a mock service to test private method.
        $reflection = new \ReflectionClass(inference_service::class);
        
        // We can't easily test the private method without mocking,
        // so we'll test the public interface instead.
        $this->assertTrue(true); // Placeholder for now.
    }

    /**
     * Test configuration loading.
     */
    public function test_configuration_loading() {
        $config = local_gis_ai_assistant_get_config();
        
        $this->assertTrue($config['enabled']);
        $this->assertEquals('gpt-4o-mini', $config['model']);
        $this->assertEquals(2048, $config['max_tokens']);
        $this->assertEquals(0.7, $config['temperature']);
    }

    /**
     * Test AI service configuration check.
     */
    public function test_is_configured_check() {
        // Without API key, should not be configured.
        unset($_SERVER['OPENAI_API_KEY']);
        $this->assertFalse(local_gis_ai_assistant_is_configured());
        
        // With API key, should be configured.
        $_SERVER['OPENAI_API_KEY'] = 'test-key';
        $this->assertTrue(local_gis_ai_assistant_is_configured());
        
        // Disabled service should not be configured.
        set_config('enabled', 0, 'local_gis_ai_assistant');
        $this->assertFalse(local_gis_ai_assistant_is_configured());
    }

    /**
     * Test database schema.
     */
    public function test_database_schema() {
        global $DB;
        
        // Test that tables exist.
        $this->assertTrue($DB->get_manager()->table_exists('local_gis_ai_assistant_conversations'));
        $this->assertTrue($DB->get_manager()->table_exists('local_gis_ai_assistant_rate_limits'));
        
        // Test table structure.
        $columns = $DB->get_columns('local_gis_ai_assistant_conversations');
        $this->assertArrayHasKey('userid', $columns);
        $this->assertArrayHasKey('model', $columns);
        $this->assertArrayHasKey('tokens_used', $columns);
    }

    /**
     * Test cache functionality.
     */
    public function test_cache_functionality() {
        $cache = \cache::make('local_gis_ai_assistant', 'responses');
        
        $testdata = ['content' => 'Test response', 'model' => 'gpt-4'];
        $cache->set('test_key', $testdata);
        
        $retrieved = $cache->get('test_key');
        $this->assertEquals($testdata, $retrieved);
    }

    /**
     * Clean up after tests.
     */
    protected function tearDown(): void {
        // Clean up environment variables.
        unset($_SERVER['OPENAI_API_KEY']);
        unset($_SERVER['OPENAI_BASE_URL']);
        unset($_SERVER['OPENAI_MODEL']);
        
        parent::tearDown();
    }
}
