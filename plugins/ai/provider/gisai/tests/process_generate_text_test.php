<?php
namespace aiprovider_gisai;

use GuzzleHttp\Psr7\Response;

/**
 * Test Generate text provider class for GIS AI provider methods.
 *
 * @package    aiprovider_gisai
 * @copyright  2025 GIS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \aiprovider_gisai\provider
 * @covers     \aiprovider_gisai\process_generate_text
 * @covers     \aiprovider_gisai\abstract_processor
 */
final class process_generate_text_test extends \advanced_testcase {
    /** @var string A successful response in JSON format. */
    protected string $responsebodyjson;

    /** @var \core_ai\manager */
    private $manager;

    /** @var \core_ai\provider The provider that will process the action. */
    protected $provider;

    /** @var \core_ai\aiactions\base The action to process. */
    protected $action;

    /**
     * Set up the test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        // Load a response body from a file.
        $fixturepath = __DIR__ . '/fixtures/text_request_success.json';
        $this->responsebodyjson = file_get_contents($fixturepath);
        $this->manager = \core\di::get(\core_ai\manager::class);
        
        // Create provider instance.
        $config = [
            'apikey' => 'test-api-key',
            'apiendpoint' => 'https://api.openai.com/v1',
        ];
        $this->provider = $this->manager->create_provider_instance(
            '\\aiprovider_gisai\\provider',
            'test-gisai',
            true,
            $config,
            [
                \core_ai\aiactions\generate_text::class => [
                    'settings' => [
                        'model' => 'gpt-4o',
                        'endpoint' => 'https://api.openai.com/v1/chat/completions',
                    ],
                ],
            ]
        );
        
        $this->create_action();
    }

    /**
     * Create the action object.
     * @param int $userid The user id to use in the action.
     */
    private function create_action(int $userid = 1): void {
        $this->action = new \core_ai\aiactions\generate_text(
            1,
            $userid,
            'This is a test prompt'
        );
    }

    /**
     * Test create_request_object
     */
    public function test_create_request_object(): void {
        $processor = new process_generate_text($this->provider, $this->action);

        // We're working with a private method here, so we need to use reflection.
        $method = new \ReflectionMethod($processor, 'create_request_object');
        $request = $method->invoke($processor, '1');

        $body = (object) json_decode($request->getBody()->getContents());

        $this->assertEquals('This is a test prompt', $body->messages[1]->content);
        $this->assertEquals('user', $body->messages[1]->role);
    }

    /**
     * Test the API success response handler method.
     */
    public function test_handle_api_success(): void {
        $response = new Response(
            200,
            ['Content-Type' => 'application/json'],
            $this->responsebodyjson
        );

        // We're testing a private method, so we need to setup reflector magic.
        $processor = new process_generate_text($this->provider, $this->action);
        $method = new \ReflectionMethod($processor, 'handle_api_success');

        $result = $method->invoke($processor, $response);

        $this->assertTrue($result['success']);
        $this->assertEquals('chatcmpl-test123', $result['id']);
        $this->assertEquals('fp_test', $result['fingerprint']);
        $this->assertStringContainsString('This is a test response', $result['generatedcontent']);
        $this->assertEquals('stop', $result['finishreason']);
        $this->assertEquals(10, $result['prompttokens']);
        $this->assertEquals(50, $result['completiontokens']);
    }

    /**
     * Test the API error response handler method.
     */
    public function test_handle_api_error(): void {
        $responses = [
            500 => new Response(500, ['Content-Type' => 'application/json']),
            401 => new Response(
                401,
                ['Content-Type' => 'application/json'],
                json_encode(['error' => ['message' => 'Invalid Authentication']])
            ),
            429 => new Response(
                429,
                ['Content-Type' => 'application/json'],
                json_encode(['error' => ['message' => 'Rate limit reached']])
            ),
        ];

        $processor = new process_generate_text($this->provider, $this->action);
        $method = new \ReflectionMethod($processor, 'handle_api_error');

        foreach ($responses as $status => $response) {
            $result = $method->invoke($processor, $response);
            $this->assertEquals($status, $result['errorcode']);
        }
    }
}
