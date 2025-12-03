<?php
namespace aiprovider_gisai;

use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Process generate text action.
 *
 * @package    aiprovider_gisai
 * @copyright  2025 GIS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class process_generate_text extends abstract_processor {
    /**
     * Create the request object to send to the API.
     *
     * @param string $userid The user id
     * @return RequestInterface
     */
    #[\Override]
    protected function create_request_object(string $userid): RequestInterface {
        // Create the user object.
        $userobj = new \stdClass();
        $userobj->role = 'user';
        $userobj->content = $this->action->get_configuration('prompttext');

        // Create the request object.
        $requestobj = new \stdClass();
        $requestobj->model = $this->get_model();
        $requestobj->user = $userid;

        // If there is a system string available, use it.
        $systeminstruction = $this->get_system_instruction();
        if (!empty($systeminstruction)) {
            $systemobj = new \stdClass();
            $systemobj->role = 'system';
            $systemobj->content = $systeminstruction;
            $requestobj->messages = [$systemobj, $userobj];
        } else {
            // Default system message.
            $systemobj = new \stdClass();
            $systemobj->role = 'system';
            $systemobj->content = 'You are a helpful assistant in a Moodle learning environment.';
            $requestobj->messages = [$systemobj, $userobj];
        }

        // Add temperature if not set.
        $requestobj->temperature = 0.7;

        return new Request(
            'POST',
            '/chat/completions',
            [
                'Content-Type' => 'application/json',
            ],
            json_encode($requestobj)
        );
    }

    /**
     * Handle a successful response from the external AI api.
     *
     * @param ResponseInterface $response The response object.
     * @return array The response.
     */
    protected function handle_api_success(ResponseInterface $response): array {
        $responsebody = $response->getBody();
        $bodyobj = json_decode($responsebody->getContents());

        return [
            'success' => true,
            'id' => $bodyobj->id ?? '',
            'fingerprint' => $bodyobj->system_fingerprint ?? '',
            'generatedcontent' => $bodyobj->choices[0]->message->content ?? '',
            'finishreason' => $bodyobj->choices[0]->finish_reason ?? '',
            'prompttokens' => $bodyobj->usage->prompt_tokens ?? 0,
            'completiontokens' => $bodyobj->usage->completion_tokens ?? 0,
            'model' => $bodyobj->model ?? $this->get_model(),
        ];
    }
}
