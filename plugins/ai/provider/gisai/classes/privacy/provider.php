<?php
namespace aiprovider_gisai\privacy;

/**
 * Privacy provider for GIS AI provider.
 *
 * @package    aiprovider_gisai
 * @copyright  2025 GIS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements \core_privacy\local\metadata\null_provider {
    /**
     * Get the language string identifier with the component's language file to explain why this plugin stores no data.
     *
     * @return string
     */
    public static function get_reason(): string {
        return 'privacy:metadata';
    }
}
