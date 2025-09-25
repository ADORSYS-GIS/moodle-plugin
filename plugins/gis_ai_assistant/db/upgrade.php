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
 * Upgrade script for local_gis_ai_assistant plugin.
 *
 * @package    local_gis_ai_assistant
 * @copyright  2025 Adorsys GIS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute local_gis_ai_assistant upgrade steps between versions.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_gis_ai_assistant_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2025092500) {
        // Define indices for the conversations table
        $table = new xmldb_table('local_gis_ai_assistant_conversations');
        
        // Add userid-timecreated index for performance
        $index = new xmldb_index('userid_timecreated', XMLDB_INDEX_NOTUNIQUE, ['userid', 'timecreated']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Add model index for analytics queries
        $index = new xmldb_index('model', XMLDB_INDEX_NOTUNIQUE, ['model']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Add timecreated index for time-based queries
        $index = new xmldb_index('timecreated', XMLDB_INDEX_NOTUNIQUE, ['timecreated']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Update rate limits table structure
        $table = new xmldb_table('local_gis_ai_assistant_rate_limits');
        
        // Add IP-based rate limiting
        $field = new xmldb_field('ip_address', XMLDB_TYPE_CHAR, '45', null, XMLDB_NOTNULL, null, null, 'userid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add index on IP address
        $index = new xmldb_index('ip_window', XMLDB_INDEX_NOTUNIQUE, ['ip_address', 'window_start']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Add foreign key on userid to user table
        $key = new xmldb_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
        if ($dbman->table_exists($table) && !$dbman->find_key_name($table, $key)) {
            $dbman->add_key($table, $key);
        }

        // Add unique composite index (userid, window_start).
        $index = new xmldb_index('user_window', XMLDB_INDEX_UNIQUE, ['userid', 'window_start']);
        if ($dbman->table_exists($table) && !$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Savepoint reached.
        upgrade_plugin_savepoint(true, 2025092500, 'local', 'gis_ai_assistant');
    }

    return true;
}
