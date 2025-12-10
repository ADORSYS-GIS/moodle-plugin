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

use aiprovider_openaicompatible\model\base;
use core_ai\hook\after_ai_action_settings_form_hook;
use core_ai\hook\after_ai_provider_form_hook;

/**
 * Hook listener for Open AI provider.
 *
 * @package    aiprovider_openaicompatible
 * @copyright  2025   Adorsys GIS <gis-udm@adorsys.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_listener {

    /**
     * Hook listener for the Open AI instance setup form.
     *
     * @param after_ai_provider_form_hook $hook The hook to add to the AI instance setup.
     */
    public static function set_form_definition_for_aiprovider_openaicompatible(after_ai_provider_form_hook $hook): void {
        if ($hook->plugin !== 'aiprovider_openaicompatible') {
            return;
        }

        $mform = $hook->mform;

        // API Endpoint setting.
        $mform->addElement(
            'text',
            'apiendpoint',
            get_string('apiendpoint', 'aiprovider_openaicompatible'),
            ['size' => 75],
        );
        $mform->setType('apiendpoint', PARAM_URL);
        $mform->addHelpButton('apiendpoint', 'apiendpoint', 'aiprovider_openaicompatible');
        $mform->setDefault('apiendpoint', 'https://api.openai.com/v1');
        $mform->addRule('apiendpoint', get_string('required'), 'required', null, 'client');

        // Model setting.
        $mform->addElement(
            'text',
            'model',
            get_string('model', 'aiprovider_openaicompatible'),
            ['size' => 30],
        );
        $mform->setType('model', PARAM_TEXT);
        $mform->addHelpButton('model', 'model', 'aiprovider_openaicompatible');
        $mform->addRule('model', get_string('required'), 'required', null, 'client');

        // Required setting to store OpenAI API key.
        $mform->addElement(
            'passwordunmask',
            'apikey',
            get_string('apikey', 'aiprovider_openaicompatible'),
            ['size' => 75],
        );
        $mform->addHelpButton('apikey', 'apikey', 'aiprovider_openaicompatible');
        $mform->addRule('apikey', get_string('required'), 'required', null, 'client');

        // Setting to store OpenAI organization ID.
        $mform->addElement(
            'text',
            'orgid',
            get_string('orgid', 'aiprovider_openaicompatible'),
            ['size' => 25],
        );
        $mform->setType('orgid', PARAM_TEXT);
        $mform->addHelpButton('orgid', 'orgid', 'aiprovider_openaicompatible');

    }

    /**
     * Hook listener for the Open AI action settings form.
     *
     * @param after_ai_action_settings_form_hook $hook The hook to add to config action settings.
     */
    public static function set_model_form_definition_for_aiprovider_openaicompatible(after_ai_action_settings_form_hook $hook): void {
        if ($hook->plugin !== 'aiprovider_openaicompatible') {
            return;
        }

        $mform = $hook->mform;
        if (isset($mform->_elementIndex['modeltemplate'])) {
            $model = $mform->getElementValue('modeltemplate');
            if (is_array($model)) {
                $model = $model[0];
            }

            if ($model == 'custom') {
                $mform->addElement('header', 'modelsettingsheader', get_string('settings', 'aiprovider_openaicompatible'));
                $settingshelp = \html_writer::tag('p', get_string('settings_help', 'aiprovider_openaicompatible'));
                $mform->addElement('html', $settingshelp);
                $mform->addElement(
                    'textarea',
                    'modelextraparams',
                    get_string('extraparams', 'aiprovider_openaicompatible'),
                    ['rows' => 5, 'cols' => 20],
                );
                $mform->setType('modelextraparams', PARAM_TEXT);
                $mform->addElement('static', 'modelextraparams_help', null, get_string('extraparams_help', 'aiprovider_openaicompatible'));
            } else {
                $targetmodel = helper::get_model_class($model);
                if ($targetmodel) {
                    if ($targetmodel->has_model_settings()) {
                        $mform->addElement('header', 'modelsettingsheader', get_string('settings', 'aiprovider_openaicompatible'));
                        $settingshelp = \html_writer::tag('p', get_string('settings_help', 'aiprovider_openaicompatible'));
                        $mform->addElement('html', $settingshelp);
                        $targetmodel->add_model_settings($mform);
                    }
                }
            }
        }
    }
}
