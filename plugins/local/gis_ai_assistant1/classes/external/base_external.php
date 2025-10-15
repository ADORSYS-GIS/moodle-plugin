<?php
declare(strict_types=1);

/**
 * Base external class for GIS AI Assistant endpoints.
 *
 * @package     local_gis_ai_assistant1
 * @category    external
 * @since       2025.10
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_gis_ai_assistant1\external;

defined('MOODLE_INTERNAL') || die();

use local_gis_ai_assistant1\helpers\logger;
use external_api;

abstract class base_external extends external_api {
    /**
     * Wrap a success response payload.
     *
     * @param array $data
     * @return array
     */
    protected static function success(array $data = []): array {
        return array_merge(['success' => true], $data);
    }

    /**
     * Uniform exception handling.
     * Logs and throws a localized moodle_exception.
     *
     * @param \Throwable $e
     * @param string $messagekey language string key
     * @throws \moodle_exception
     */
    protected static function handle_exception(\Throwable $e, string $messagekey = 'unexpectederror'): void {
        logger::exception($e, $messagekey);
        throw new \moodle_exception($messagekey, 'local_gis_ai_assistant1', '', $e->getMessage());
    }
}
