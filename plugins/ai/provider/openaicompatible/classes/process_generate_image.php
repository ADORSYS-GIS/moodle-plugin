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

use aiprovider_openaicompatible\aimodel\openai_image_base;
use core\http_client;
use core_ai\ai_image;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class process image generation.
 *
 * @package    aiprovider_openaicompatible
 * @copyright  2025   Adorsys GIS <gis-udm@adorsys.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class process_generate_image extends abstract_processor {
    /** @var int The number of images to generate. */
    private int $numberimages = 1;

    #[\Override]
    protected function query_ai_api(): array {
        $response = parent::query_ai_api();

        // If the request was successful, save the image data to a Moodle draft file.
        if ($response['success']) {
            $fileobj = $this->create_file_from_response(
                $this->action->get_configuration('userid'),
                $response
            );
            // Add the file to the response so placements can use it.
            $response['draftfile'] = $fileobj;
        }

        return $response;
    }

    /**
     * Convert the given aspect ratio to an image size compatible with the OpenAI API.
     *
     * Delegates to the model class if one is found for the configured model,
     * otherwise falls back to a default mapping.
     *
     * @param string $ratio The aspect ratio ('square', 'landscape', or 'portrait').
     * @return string The size string for the API request (e.g. '1024x1024').
     */
    private function calculate_size(string $ratio): string {
        $modelclass = helper::get_model_class($this->get_model());
        if ($modelclass instanceof openai_image_base) {
            return $modelclass->calculate_size($ratio);
        }
        // Fallback for unknown/custom models.
        if ($ratio === 'square') {
            $size = '1024x1024';
        } else if ($ratio === 'landscape') {
            $size = '1536x1024';
        } else if ($ratio === 'portrait') {
            $size = '1024x1536';
        } else {
            throw new \coding_exception('Invalid aspect ratio: ' . $ratio);
        }
        return $size;
    }

    /**
     * Convert the given quality setting to an API-compatible quality value.
     *
     * Delegates to the model class if one is found for the configured model,
     * otherwise falls back to a default mapping.
     *
     * @param string $quality The quality setting from the action ('standard' or 'hd').
     * @return string The quality value for the API request.
     */
    private function calculate_quality(string $quality): string {
        $modelclass = helper::get_model_class($this->get_model());
        if ($modelclass instanceof openai_image_base) {
            return $modelclass->calculate_quality($quality);
        }
        // Fallback for unknown/custom models.
        if ($quality === 'standard') {
            $processedquality = 'medium';
        } else if ($quality === 'hd') {
            $processedquality = 'high';
        } else {
            throw new \coding_exception('Invalid quality: ' . $quality);
        }
        return $processedquality;
    }

    #[\Override]
    protected function create_request_object(string $userid): RequestInterface {
        // Create the request object.
        $requestobj = new \stdClass();
        $requestobj->model = $this->get_model();
        $requestobj->user = $userid;
        $requestobj->prompt = $this->action->get_configuration('prompttext');
        $requestobj->n = $this->numberimages;
        $requestobj->quality = $this->calculate_quality($this->action->get_configuration('quality'));
        $requestobj->size = $this->calculate_size($this->action->get_configuration('aspectratio'));

        // Apply model-specific parameters (response_format and output_format).
        $modelclass = helper::get_model_class($this->get_model());
        if ($modelclass instanceof openai_image_base) {
            $responseformat = $modelclass->response_format();
            if ($responseformat !== null) {
                $requestobj->response_format = $responseformat;
            }
            $outputformat = $modelclass->get_output_format();
            if ($outputformat !== null) {
                $requestobj->output_format = $outputformat;
            }
        }

        // Append any extra model settings from admin config.
        $modelsettings = $this->get_model_settings();
        foreach ($modelsettings as $setting => $value) {
            $requestobj->$setting = $value;
        }

        return new Request(
            'POST',
            '',
            ['Content-Type' => 'application/json'],
            json_encode($requestobj)
        );
    }

    #[\Override]
    protected function handle_api_success(ResponseInterface $response): array {
        $responsebody = $response->getBody();
        $bodyobj = json_decode($responsebody->getContents());

        return [
            'success' => true,
            'b64json' => $bodyobj->data[0]->b64_json ?? null,
            'sourceurl' => $bodyobj->data[0]->url ?? null,
            'output_format' => $bodyobj->output_format ?? 'png',
            'revisedprompt' => $bodyobj->data[0]->revised_prompt ?? '',
            'model' => $this->get_model(),
            'errormessage' => '',
        ];
    }

    /**
     * Convert image data from the API response into a Moodle draft file.
     *
     * Handles both inline base64 (b64_json) and remote URL responses.
     * Placements can't interact with the provider AI directly, so the image
     * must be stored via the Moodle File API in the user's draft area.
     *
     * @param int $userid The user id.
     * @param array $response Response from the AI provider.
     * @return \stored_file The stored draft file.
     */
    private function create_file_from_response(
        int $userid,
        array $response,
    ): \stored_file {
        global $CFG;

        require_once("{$CFG->libdir}/filelib.php");

        if (!empty($response['b64json'])) {
            // Preferred path: inline base64, no secondary HTTP call needed.
            $b64json = $response['b64json'];
            $imagebytes = base64_decode($b64json);
            $outputformat = $response['output_format'] ?? 'png';
            $filename = substr(hash('sha512', $b64json), 0, 16) . '.' . $outputformat;
            $tempdst = make_request_directory() . DIRECTORY_SEPARATOR . $filename;
            file_put_contents($tempdst, $imagebytes);
        } else if (!empty($response['sourceurl'])) {
            // Fallback: download from remote URL.
            $url = $response['sourceurl'];
            $parsedurl = parse_url($url, PHP_URL_PATH);
            $filename = basename($parsedurl);
            $tempdst = make_request_directory() . DIRECTORY_SEPARATOR . $filename;
            $client = \core\di::get(http_client::class);
            $client->get($url, [
                'sink' => $tempdst,
                'timeout' => $CFG->repositorygetfiletimeout,
            ]);
        } else {
            throw new \moodle_exception('No image data returned from the AI API.');
        }

        // Add the AI watermark.
        $image = new ai_image($tempdst);
        $image->add_watermark()->save();

        // Store in the user's draft file area.
        $fileinfo = new \stdClass();
        $fileinfo->contextid = \context_user::instance($userid)->id;
        $fileinfo->filearea = 'draft';
        $fileinfo->component = 'user';
        $fileinfo->itemid = file_get_unused_draft_itemid();
        $fileinfo->filepath = '/';
        $fileinfo->filename = $filename;

        $fs = get_file_storage();
        return $fs->create_file_from_string($fileinfo, file_get_contents($tempdst));
    }
}
