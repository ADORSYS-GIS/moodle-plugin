<?php
namespace aiprovider_gisai;

use core_ai\hook\after_ai_provider_form_hook;

/**
 * Hook listener for GIS AI provider.
 *
 * @package    aiprovider_gisai
 * @copyright  2024 GIS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_listener {

    /**
     * Hook listener for the GIS AI instance setup form.
     *
     * @param after_ai_provider_form_hook $hook The hook to add to the AI instance setup.
     */
    public static function set_form_definition_for_aiprovider_gisai(after_ai_provider_form_hook $hook): void {
        if ($hook->plugin !== 'aiprovider_gisai') {
            return;
        }

        $mform = $hook->mform;

        // API Endpoint.
        $mform->addElement(
            'text',
            'apiendpoint',
            get_string('apiendpoint', 'aiprovider_gisai'),
            ['size' => 50]
        );
        $mform->setType('apiendpoint', PARAM_URL);
        $mform->addHelpButton('apiendpoint', 'apiendpoint', 'aiprovider_gisai');
        $mform->setDefault('apiendpoint', 'https://api.openai.com/v1');
        $mform->addRule('apiendpoint', get_string('required'), 'required', null, 'client');

        // API Key.
        $mform->addElement(
            'passwordunmask',
            'apikey',
            get_string('apikey', 'aiprovider_gisai'),
            ['size' => 75]
        );
        $mform->addHelpButton('apikey', 'apikey', 'aiprovider_gisai');
        $mform->addRule('apikey', get_string('required'), 'required', null, 'client');

        // Model.
        $mform->addElement(
            'text',
            'model',
            get_string('model', 'aiprovider_gisai'),
            ['size' => 30]
        );
        $mform->setType('model', PARAM_TEXT);
        $mform->addHelpButton('model', 'model', 'aiprovider_gisai');
        $mform->setDefault('model', 'gpt-4o');
        $mform->addRule('model', get_string('required'), 'required', null, 'client');
    }
}
