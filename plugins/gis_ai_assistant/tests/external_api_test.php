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
 * Unit tests for external API.
 *
 * @package    local_gis_ai_assistant
 * @copyright  2025 Adorsys GIS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_gis_ai_assistant;

use local_gis_ai_assistant\external\chat_api;

defined('MOODLE_INTERNAL') || die();

/**
 * Test cases for external API.
 *
 * @group local_gis_ai_assistant
 */
class external_api_test extends \advanced_testcase {

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
        set_config('enable_analytics', 1, 'local_gis_ai_assistant');
        
        // Mock API key for tests.
        $_SERVER['OPENAI_API_KEY'] = 'test-api-key';
        $_SERVER['OPENAI_BASE_URL'] = 'https://api.openai.com/v1';
    }

    /**
     * Test send_message parameters validation.
     */
    public function test_send_message_parameters() {
        $params = chat_api::send_message_parameters();
        $this->assertInstanceOf(\external_function_parameters::class, $params);
        
        // Test parameter structure.
        $keys = array_keys($params->keys);
        $this->assertContains('message', $keys);
        $this->assertContains('model', $keys);
        $this->assertContains('temperature', $keys);
        $this->assertContains('max_tokens', $keys);
    }

    /**
     * Test send_message returns structure.
     */
    public function test_send_message_returns() {
        $returns = chat_api::send_message_returns();
        $this->assertInstanceOf(\external_single_structure::class, $returns);
        
        // Test return structure.
        $keys = array_keys($returns->keys);
        $this->assertContains('success', $keys);
        $this->assertContains('content', $keys);
        $this->assertContains('model', $keys);
        $this->assertContains('usage', $keys);
    }

    /**
     * Test send_message with disabled AI.
     */
    public function test_send_message_disabled_ai() {
        set_config('enabled', 0, 'local_gis_ai_assistant');
        
        $this->expectException(\local_gis_ai_assistant\exceptions\ai_exception::class);
        chat_api::send_message('Test message');
    }

    /**
     * Test send_message without capability.
     */
    public function test_send_message_no_capability() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        
        $this->expectException(\required_capability_exception::class);
        chat_api::send_message('Test message');
    }

    /**
     * Test analytics parameters validation.
     */
    public function test_analytics_parameters() {
        $params = chat_api::get_analytics_parameters();
        $this->assertInstanceOf(\external_function_parameters::class, $params);
        
        $keys = array_keys($params->keys);
        $this->assertContains('period', $keys);
    }

    /**
     * Test analytics returns structure.
     */
    public function test_analytics_returns() {
        $returns = chat_api::get_analytics_returns();
        $this->assertInstanceOf(\external_single_structure::class, $returns);
        
        $keys = array_keys($returns->keys);
        $this->assertContains('success', $keys);
        $this->assertContains('total_requests', $keys);
        $this->assertContains('total_tokens', $keys);
        $this->assertContains('top_users', $keys);
    }

    /**
     * Test analytics without capability.
     */
    public function test_analytics_no_capability() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        
        $this->expectException(\required_capability_exception::class);
        chat_api::get_analytics();
    }

    /**
     * Test streaming parameters validation.
     */
    public function test_streaming_parameters() {
        $params = chat_api::send_message_stream_parameters();
        $this->assertInstanceOf(\external_function_parameters::class, $params);
        
        $keys = array_keys($params->keys);
        $this->assertContains('message', $keys);
        $this->assertContains('model', $keys);
    }

    /**
     * Test streaming returns structure.
     */
    public function test_streaming_returns() {
        $returns = chat_api::send_message_stream_returns();
        $this->assertInstanceOf(\external_single_structure::class, $returns);
        
        $keys = array_keys($returns->keys);
        $this->assertContains('success', $keys);
        $this->assertContains('session_id', $keys);
        $this->assertContains('stream_url', $keys);
    }

    /**
     * Clean up after tests.
     */
    protected function tearDown(): void {
        unset($_SERVER['OPENAI_API_KEY']);
        unset($_SERVER['OPENAI_BASE_URL']);
        parent::tearDown();
    }
}
