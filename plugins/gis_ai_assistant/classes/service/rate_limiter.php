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
 * Rate limiting service
 *
 * @package    local_gis_ai_assistant
 * @copyright  2025 Adorsys GIS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_gis_ai_assistant\service;

use local_gis_ai_assistant\exceptions\rate_limit_exception;

defined('MOODLE_INTERNAL') || die();

/**
 * Service for managing rate limits
 */
class rate_limiter {
    /** @var int Default window size in seconds */
    const DEFAULT_WINDOW_SIZE = 3600; // 1 hour

    /** @var int Default max requests per window */
    const DEFAULT_MAX_REQUESTS = 100;

    /** @var int Default max tokens per window */
    const DEFAULT_MAX_TOKENS = 50000;

    /** @var \cache Cache instance for IP-based limits */
    private $ipcache;

    /** @var \cache Cache instance for user-based limits */
    private $usercache;

    /**
     * Constructor
     */
    public function __construct() {
        $this->ipcache = \cache::make('local_gis_ai_assistant', 'ip_ratelimits');
        $this->usercache = \cache::make('local_gis_ai_assistant', 'user_ratelimits');
    }

    /**
     * Check and update rate limits for a request
     *
     * @param int $userid User ID
     * @param int $tokens Number of tokens being requested
     * @throws rate_limit_exception If limits exceeded
     */
    public function check_rate_limit($userid, $tokens = 0) {
        $this->check_ip_limit();
        $this->check_user_limit($userid, $tokens);
    }

    /**
     * Check IP-based rate limit
     *
     * @throws rate_limit_exception If IP limit exceeded
     */
    private function check_ip_limit() {
        $ip = getremoteaddr();
        $key = "ip_$ip";
        
        $data = $this->ipcache->get($key) ?: [
            'count' => 0,
            'window_start' => time()
        ];

        // Reset window if expired
        if (time() - $data['window_start'] > self::DEFAULT_WINDOW_SIZE) {
            $data = [
                'count' => 0,
                'window_start' => time()
            ];
        }

        // Check limit
        if ($data['count'] >= self::DEFAULT_MAX_REQUESTS) {
            throw new rate_limit_exception('IP rate limit exceeded');
        }

        // Update counter
        $data['count']++;
        $this->ipcache->set($key, $data);
    }

    /**
     * Check user-based rate limit
     *
     * @param int $userid User ID
     * @param int $tokens Number of tokens being requested
     * @throws rate_limit_exception If user limit exceeded
     */
    private function check_user_limit($userid, $tokens) {
        $key = "user_$userid";
        
        $data = $this->usercache->get($key) ?: [
            'request_count' => 0,
            'token_count' => 0,
            'window_start' => time()
        ];

        // Reset window if expired
        if (time() - $data['window_start'] > self::DEFAULT_WINDOW_SIZE) {
            $data = [
                'request_count' => 0,
                'token_count' => 0,
                'window_start' => time()
            ];
        }

        // Check limits
        if ($data['request_count'] >= self::DEFAULT_MAX_REQUESTS) {
            throw new rate_limit_exception('User request limit exceeded');
        }

        if ($data['token_count'] + $tokens > self::DEFAULT_MAX_TOKENS) {
            throw new rate_limit_exception('User token limit exceeded');
        }

        // Update counters
        $data['request_count']++;
        $data['token_count'] += $tokens;
        $this->usercache->set($key, $data);
    }

    /**
     * Get current rate limit status for a user
     *
     * @param int $userid User ID
     * @return array Rate limit status
     */
    public function get_limit_status($userid) {
        $key = "user_$userid";
        $data = $this->usercache->get($key) ?: [
            'request_count' => 0,
            'token_count' => 0,
            'window_start' => time()
        ];

        return [
            'requests_remaining' => self::DEFAULT_MAX_REQUESTS - $data['request_count'],
            'tokens_remaining' => self::DEFAULT_MAX_TOKENS - $data['token_count'],
            'window_reset' => $data['window_start'] + self::DEFAULT_WINDOW_SIZE
        ];
    }
}
