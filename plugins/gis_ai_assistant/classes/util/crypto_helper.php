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
 * Cryptography helper for secure data handling.
 *
 * @package    local_gis_ai_assistant
 * @copyright  2025 Adorsys GIS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_gis_ai_assistant\util;

defined('MOODLE_INTERNAL') || die();

/**
 * Helper class for encryption/decryption operations
 */
class crypto_helper {
    
    /**
     * Encrypt sensitive data using Moodle's encryption
     *
     * @param string $data Data to encrypt
     * @return string Encrypted data
     */
    public static function encrypt($data) {
        return encrypt_secret($data);
    }

    /**
     * Decrypt sensitive data using Moodle's decryption
     *
     * @param string $encrypted Encrypted data
     * @return string Decrypted data
     */
    public static function decrypt($encrypted) {
        return decrypt_secret($encrypted);
    }

    /**
     * Securely store API key in config
     *
     * @param string $key The API key to store
     * @return bool Success status
     */
    public static function store_api_key($key) {
        $encrypted = self::encrypt($key);
        return set_config('api_key_encrypted', $encrypted, 'local_gis_ai_assistant');
    }

    /**
     * Retrieve decrypted API key
     *
     * @return string|null Decrypted API key or null if not set
     */
    public static function get_api_key() {
        $encrypted = get_config('local_gis_ai_assistant', 'api_key_encrypted');
        if (empty($encrypted)) {
            return null;
        }
        return self::decrypt($encrypted);
    }
}
