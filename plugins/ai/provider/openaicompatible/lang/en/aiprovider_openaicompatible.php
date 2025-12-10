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
 * Strings for component aiprovider_openaicompatible, language 'en'.
 *
 * @package    aiprovider_openaicompatible
 * @copyright  2025   Adorsys GIS <gis-udm@adorsys.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['action:explain_text:endpoint'] = 'API endpoint';
$string['action:explain_text:model'] = 'AI model';
$string['action:explain_text:model_help'] = 'The model used to explain the provided text.';
$string['action:explain_text:systeminstruction'] = 'System instruction';
$string['action:explain_text:systeminstruction_help'] = 'This instruction is sent to the AI model along with the user\'s prompt. Editing this instruction is not recommended unless absolutely required.';
$string['action:generate_image:endpoint'] = 'API endpoint';
$string['action:generate_image:model'] = 'AI model';
$string['action:generate_image:model_help'] = 'The model used to generate images from user prompts.';
$string['action:generate_text:endpoint'] = 'API endpoint';
$string['action:generate_text:model'] = 'AI model';
$string['action:generate_text:model_help'] = 'The model used to generate the text response.';
$string['action:generate_text:systeminstruction'] = 'System instruction';
$string['action:generate_text:systeminstruction_help'] = 'This instruction is sent to the AI model along with the user\'s prompt. Editing this instruction is not recommended unless absolutely required.';
$string['action:summarise_text:endpoint'] = 'API endpoint';
$string['action:summarise_text:model'] = 'AI model';
$string['action:summarise_text:model_help'] = 'The model used to summarise the provided text.';
$string['action:summarise_text:systeminstruction'] = 'System instruction';
$string['action:summarise_text:systeminstruction_help'] = 'This instruction is sent to the AI model along with the user\'s prompt. Editing this instruction is not recommended unless absolutely required.';
$string['apiendpoint'] = 'API Endpoint';
$string['apiendpoint_help'] = 'The base URL for your OpenAI-compatible API (e.g., https://api.openai.com/v1 or https://ai.kivoyo.com/api/v1).';
$string['apikey'] = 'OpenAI API key';
$string['model'] = 'Model Name';
$string['model_help'] = 'The ID of the model to use (e.g., gpt-4, claude-3-opus, llama-3).';
$string['apikey_help'] = 'Get a key from your <a href="https://platform.openai.com/account/api-keys" target="_blank">OpenAI API keys</a>.';
$string['custom_model_name'] = 'Custom model name';
$string['extraparams'] = 'Extra parameters';
$string['extraparams_help'] = 'Extra parameters can be configured here. We support JSON format. For example:
<pre>
{
    "temperature": 0.5,
    "max_completion_tokens": 100
}
</pre>';
$string['invalidjson'] = 'Invalid JSON string';
$string['orgid'] = 'OpenAI organization ID';
$string['orgid_help'] = 'Get your OpenAI organization ID from your <a href="https://platform.openai.com/account/org-settings" target="_blank">OpenAI account</a>.';
$string['pluginname'] = 'OpenAI Compatible API provider';
$string['privacy:metadata'] = 'The OpenAI API provider plugin does not store any personal data.';
$string['privacy:metadata:aiprovider_openaicompatible:externalpurpose'] = 'This information is sent to the OpenAI API in order for a response to be generated. Your OpenAI account settings may change how OpenAI stores and retains this data. No user data is explicitly sent to OpenAI or stored in Moodle LMS by this plugin.';
$string['privacy:metadata:aiprovider_openaicompatible:model'] = 'The model used to generate the response.';
$string['privacy:metadata:aiprovider_openaicompatible:numberimages'] = 'When generating images the number of images used in the response.';
$string['privacy:metadata:aiprovider_openaicompatible:prompttext'] = 'The user entered text prompt used to generate the response.';
$string['privacy:metadata:aiprovider_openaicompatible:responseformat'] = 'The format of the response. When generating images.';
$string['settings'] = 'Settings';
$string['settings_frequency_penalty'] = 'frequency_penalty';
$string['settings_frequency_penalty_help'] = 'The frequency penalty adjusts how often words are repeated. The higher the penalty, the less repetitions in the generated text.';
$string['settings_help'] = 'Adjust the settings below to customise how requests are sent to OpenAI.';
$string['settings_max_completion_tokens'] = 'max_completion_tokens';
$string['settings_max_completion_tokens_help'] = 'The maximum number of tokens used in the generated text.';
$string['settings_presence_penalty'] = 'presence_penalty';
$string['settings_presence_penalty_help'] = 'The presence penalty encourages the model to use new words by increasing the likelihood of choosing words it hasn\'t used before. A higher value makes the generated text more diverse, while a lower value allows more repetition.';
$string['settings_top_p'] = 'top_p';
$string['settings_top_p_help'] = 'top_p (nucleus sampling) determines how many possible words to consider. A high value (e.g. 0.9) means the model looks at more words, which makes the generated text more diverse.';
