<?php
namespace aiprovider_gisai;

use core\http_client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

/**
 * Abstract processor for GIS AI provider.
 *
 * @package    aiprovider_gisai
 * @copyright  2025 GIS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class abstract_processor extends \core_ai\process_base {
    /**
     * Get the endpoint URI.
     *
     * @return UriInterface
     */
    protected function get_endpoint(): UriInterface {
        $endpoint = $this->provider->config['apiendpoint'] 
            ?? get_config('aiprovider_gisai', 'apiendpoint');
        
        if (empty($endpoint)) {
            $endpoint = 'https://api.openai.com';
        }
        
        return new Uri($endpoint);
    }

    /**
     * Get the name of the model to use.
     *
     * @return string
     */
    protected function get_model(): string {
        return $this->provider->config['model'] 
            ?? get_config('aiprovider_gisai', 'model') 
            ?? 'gpt-4o';
    }

    /**
     * Get the system instructions.
     *
     * @return string
     */
    protected function get_system_instruction(): string {
        $actionclass = get_class($this->action);
        return $actionclass::get_system_instruction();
    }

    /**
     * Create the request object to send to the API.
     *
     * @param string $userid The user id.
     * @return RequestInterface The request object
     */
    abstract protected function create_request_object(string $userid): RequestInterface;

    /**
     * Handle a successful response from the external AI api.
     *
     * @param ResponseInterface $response The response object.
     * @return array The response.
     */
    abstract protected function handle_api_success(ResponseInterface $response): array;

    /**
     * Query the AI API.
     *
     * @return array
     */
    #[\Override]
    protected function query_ai_api(): array {
        $request = $this->create_request_object(
            $this->provider->generate_userid($this->action->get_configuration('userid'))
        );
        $request = $this->provider->add_authentication_headers($request);

        $client = new \core\http_client(['ignoresecurity' => true]);
        $endpoint = $this->get_endpoint();
        try {
            // Call the external AI service.
            $response = $client->send($request, [
                'base_uri' => $endpoint,
                RequestOptions::HTTP_ERRORS => false,
            ]);
        } catch (RequestException $e) {
            // Handle any exceptions.
            return [
                'success' => false,
                'errorcode' => $e->getCode() ?: 500,
                'errormessage' => $e->getMessage(),
            ];
        }

        // Double-check the response codes.
        $status = $response->getStatusCode();
        if ($status === 200) {
            return $this->handle_api_success($response);
        } else {
            return $this->handle_api_error($response, $request);
        }
    }

    /**
     * Handle an error from the external AI api.
     *
     * @param ResponseInterface $response The response object.
     * @return array The error response.
     */
    protected function handle_api_error(ResponseInterface $response, ?RequestInterface $request = null): array {
        $status = $response->getStatusCode();
        $body = $response->getBody()->getContents();
        
        // Log the error for debugging.
        debugging('GIS AI Provider API Error: ' . $status . ' - ' . $body, DEBUG_DEVELOPER);

        // Construct detailed debug message as requested.
        $errormessage = "DEBUG INFO:\n";
        if ($request) {
            $errormessage .= "Method: " . $request->getMethod() . "\n";
            $errormessage .= "URI: " . $request->getUri() . "\n";
            $errormessage .= "Request Headers: " . json_encode($request->getHeaders()) . "\n";
            
            // Rewind body stream to ensure we can read it
            if ($request->getBody()->isSeekable()) {
                $request->getBody()->rewind();
            }
            $errormessage .= "Request Body: " . $request->getBody()->getContents() . "\n";
        }
        $errormessage .= "Response Status: " . $status . "\n";
        $errormessage .= "Response Body: " . $body;

        return [
            'success' => false,
            'errorcode' => $status ?: 500,
            'errormessage' => $errormessage,
        ];
    }
}
