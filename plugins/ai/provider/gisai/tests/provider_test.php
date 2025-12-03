<?php
namespace aiprovider_gisai;

/**
 * Test provider class for GIS AI provider methods.
 *
 * @package    aiprovider_gisai
 * @copyright  2025 GIS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \aiprovider_gisai\provider
 */
final class provider_test extends \advanced_testcase {
    /**
     * Test get_action_list method.
     */
    public function test_get_action_list(): void {
        $actions = provider::get_action_list();
        
        $this->assertIsArray($actions);
        $this->assertContains(\core_ai\aiactions\generate_text::class, $actions);
    }

    /**
     * Test is_provider_configured method.
     */
    public function test_is_provider_configured(): void {
        $this->resetAfterTest();
        
        $manager = \core\di::get(\core_ai\manager::class);
        
        // Test with API key and endpoint.
        $config = [
            'apikey' => 'test-key',
            'apiendpoint' => 'https://api.openai.com/v1',
        ];
        $provider = $manager->create_provider_instance(
            '\\aiprovider_gisai\\provider',
            'test',
            true,
            $config,
            []
        );
        
        $this->assertTrue($provider->is_provider_configured());
        
        // Test with missing API key.
        $config = [
            'apikey' => '',
            'apiendpoint' => 'https://api.openai.com/v1',
        ];
        $provider2 = $manager->create_provider_instance(
            '\\aiprovider_gisai\\provider',
            'test2',
            true,
            $config,
            []
        );
        
        $this->assertFalse($provider2->is_provider_configured());
    }
}
