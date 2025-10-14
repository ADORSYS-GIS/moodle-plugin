<?php
defined('MOODLE_INTERNAL') || die();

function xmldb_local_gis_ai_assistant1_upgrade(int $oldversion): bool {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2025101401) {
        // Create analytics table gis_ai_logs if it does not exist.
        $table = new xmldb_table('gis_ai_logs');
        if (!$dbman->table_exists($table)) {
            $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->addFieldInfo('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->addFieldInfo('timestamp', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->addFieldInfo('prompt_hash', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, null);
            $table->addFieldInfo('response_hash', XMLDB_TYPE_CHAR, '64', null, null, null, null);
            $table->addFieldInfo('tokens', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
            $table->addFieldInfo('status', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
            $table->addFieldInfo('contextid', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
            $table->addFieldInfo('courseid', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');

            $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->addKeyInfo('userid_fk', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);

            $table->addIndexInfo('userid_idx', XMLDB_INDEX_NOTUNIQUE, ['userid']);
            $table->addIndexInfo('timestamp_idx', XMLDB_INDEX_NOTUNIQUE, ['timestamp']);

            $dbman->create_table($table);
        } else {
            // Ensure fields exist on existing installs.
            $field = new xmldb_field('response_hash', XMLDB_TYPE_CHAR, '64', null, null, null, null);
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
            $field = new xmldb_field('contextid', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
            $field = new xmldb_field('courseid', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
            $field = new xmldb_field('tokens', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
            $field = new xmldb_field('status', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
            // Ensure indexes exist.
            $index = new xmldb_index('userid_idx', XMLDB_INDEX_NOTUNIQUE, ['userid']);
            if (!$dbman->index_exists($table, $index)) {
                $dbman->add_index($table, $index);
            }
            $index = new xmldb_index('timestamp_idx', XMLDB_INDEX_NOTUNIQUE, ['timestamp']);
            if (!$dbman->index_exists($table, $index)) {
                $dbman->add_index($table, $index);
            }
        }

        // Savepoint reached.
        upgrade_plugin_savepoint(true, 2025101401, 'local', 'gis_ai_assistant1');
    }

    return true;
}
