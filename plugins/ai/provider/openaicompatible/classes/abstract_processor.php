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

namespace aiprovider_openaicompatible;

use core\http_client;
use core_ai\process_base;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

/**
 * Class process text generation.
 *
 * @package    aiprovider_openaicompatible
 * @copyright  2025   Adorsys GIS <gis-udm@adorsys.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class abstract_processor extends process_base {
    /**
     * Get the endpoint URI.
     *
     * @return UriInterface
     */
    protected function get_endpoint(): UriInterface {
        $endpoint = null;
        if (method_exists($this->provider, 'get_api_endpoint')) {
            $endpoint = $this->provider->get_api_endpoint();
        }
        
        if (empty($endpoint)) {
             $endpoint = $this->provider->actionconfig[$this->action::class]['settings']['endpoint'];
        }
        
        return new Uri($endpoint);
    }

    /**
     * Get the name of the model to use.
     *
     * @return string
     */
    protected function get_model(): string {
        $model = null;
        if (method_exists($this->provider, 'get_api_model')) {
            $model = $this->provider->get_api_model();
        }
        
        if (empty($model)) {
            $model = $this->provider->actionconfig[$this->action::class]['settings']['model'];
        }
        
        return $model;
    }

    /**
     * Get the model settings.
     *
     * @return array
     */
    protected function get_model_settings(): array {
        $settings = $this->provider->actionconfig[$this->action::class]['settings'];
        if (!empty($settings['modelextraparams'])) {
            // Custom model settings.
            $params = json_decode($settings['modelextraparams'], true);
            foreach ($params as $key => $param) {
                $settings[$key] = $param;
            }
        }

        // Unset unnecessary settings.
        unset(
            $settings['model'],
            $settings['endpoint'],
            $settings['systeminstruction'],
            $settings['providerid'],
            $settings['modelextraparams'],
        );
        return $settings;
    }

    /**
     * Get the system instructions.
     *
     * @return string
     */
    protected function get_system_instruction(): string {
        return $this->action::get_system_instruction();
    }

    /**
     * Create the request object to send to the OpenAI API.
     *
     * This object contains all the required parameters for the request.
     *
     *
     *
     * @param string $userid The user id.
     * @return RequestInterface The request object to send to the OpenAI API.
     */
    abstract protected function create_request_object(
        string $userid,
    ): RequestInterface;

    /**
     * Handle a successful response from the external AI api.
     *
     * @param ResponseInterface $response The response object.
     * @return array The response.
     */
    abstract protected function handle_api_success(ResponseInterface $response): array;

    #[\Override]
    protected function query_ai_api(): array {
        $request = $this->create_request_object(
            userid: $this->provider->generate_userid($this->action->get_configuration('userid')),
        );
        $request = $this->provider->add_authentication_headers($request);

        $client = \core\di::get(http_client::class);
        
        // Construct the full absolute URI manually to avoid Guzzle base_uri merging ambiguities.
        $endpoint = $this->get_endpoint();
        $endpointstr = (string) $endpoint;
        if (substr($endpointstr, -1) !== '/') {
            $endpointstr .= '/';
        }
        $newuri = new Uri($endpointstr . $request->getUri());
        $request = $request->withUri($newuri);

        try {
            // Call the external AI service.
            // We pass an empty base_uri because we've already set the full URI on the request.
            $response = $client->send($request, [
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

        // Double-check the response codes, in case of a non 200 that didn't throw an error.
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
     * @param RequestInterface|null $request The request object.
     * @return array The error response.
     */
    protected function handle_api_error(ResponseInterface $response, ?RequestInterface $request = null): array {
        $status = $response->getStatusCode();
        $errormessage = 'API Error: ' . $response->getReasonPhrase() . ' (' . $status . ')';
        
        // Append URI if available for debugging
        if ($request) {
            $requesturi = (string) $request->getUri();
            // If the request URI is absolute (starts with http), display it as is.
            if (str_starts_with($requesturi, 'http')) {
                $errormessage .= ' requesting ' . $requesturi;
            } else {
                // Otherwise prepend the endpoint.
                $effectiveurl = (string) $this->get_endpoint();
                if (substr($effectiveurl, -1) !== '/') {
                    $effectiveurl .= '/';
                }
                $effectiveurl .= $requesturi;
                $errormessage .= ' requesting ' . $effectiveurl;
            }
        }

        if ($status >= 500 && $status < 600) {
            // Keep reason phrase for 5xx
        } else {
            $bodycontent = $response->getBody()->getContents();
            $bodyobj = json_decode($bodycontent);
            if ($bodyobj && isset($bodyobj->error) && isset($bodyobj->error->message)) {
                $errormessage .= ' - ' . $bodyobj->error->message;
            } else {
                if (!empty($bodycontent) && strlen($bodycontent) < 200) {
                    $errormessage .= ' - ' . strip_tags($bodycontent);
                }
            }
        }

        return [
            'success' => false,
            'errorcode' => (string) $status,
            'errormessage' => (string) $errormessage,
        ];
    }
}
