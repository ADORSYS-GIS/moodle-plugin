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
 * CLI tool to normalize persisted AI conversation history newlines/escapes.
 *
 * Usage examples (run from Moodle root):
 *   php local/gis_ai_assistant/cli/normalize_history.php --dry-run
 *   php local/gis_ai_assistant/cli/normalize_history.php --apply
 *   php local/gis_ai_assistant/cli/normalize_history.php --apply --userid=123
 *   php local/gis_ai_assistant/cli/normalize_history.php --apply --limit=500
 *
 * @package    local_gis_ai_assistant
 * @copyright  2025 Adorsys GIS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir.'/clilib.php');

list($options, $unrecognized) = cli_get_params([
    'apply' => false,
    'userid' => 0,
    'limit' => 0,
    'dry-run' => false,
    'help' => false,
], [
    'a' => 'apply',
    'u' => 'userid',
    'l' => 'limit',
    'h' => 'help',
]);

if (!empty($unrecognized)) {
    cli_error("Unknown options: \n  - " . implode("\n  - ", $unrecognized));
}

if (!empty($options['help'])) {
    $help = "Normalize persisted AI conversation history newlines/escapes.\n\n".
            "Options:\n".
            "  --apply, -a        Apply changes (default is dry-run)\n".
            "  --userid=ID, -u    Only process a specific user ID\n".
            "  --limit=N, -l      Limit number of records processed\n".
            "  --dry-run          Force dry-run (overrides --apply)\n".
            "  --help, -h         Show this help\n\n".
            "Examples:\n".
            "  php local/gis_ai_assistant/cli/normalize_history.php --dry-run\n".
            "  php local/gis_ai_assistant/cli/normalize_history.php --apply --userid=123\n";
    echo $help; exit(0);
}

$apply = !empty($options['apply']) && empty($options['dry-run']);
$userid = (int)$options['userid'];
$limit  = (int)$options['limit'];

$conditions = [];
$params = [];
if ($userid > 0) {
    $conditions[] = 'userid = :userid';
    $params['userid'] = $userid;
}
$where = $conditions ? ('WHERE ' . implode(' AND ', $conditions)) : '';
$limitsql = $limit > 0 ? " LIMIT ".$limit : '';

$selectsql = "SELECT id, userid, message, response FROM {local_gis_ai_assistant_conversations} {$where} ORDER BY timecreated DESC{$limitsql}";

$records = $DB->get_records_sql($selectsql, $params);
$total = count($records);
$changed = 0;
$updated = 0;

function normalize_text_preserve_newlines(string $s): string {
    // Normalize EOLs
    $s = str_replace(["\r\n", "\r"], "\n", $s);
    // Decode literal backslash-escaped sequences to real control chars
    $s = preg_replace('/\\\r\\\n|\\\r|\\\n/', "\n", $s);
    $s = preg_replace('/\\\t/', "\t", $s);
    // Trim trailing spaces at end of lines
    $s = preg_replace('/[ \t]+\n/', "\n", $s);
    // Reduce 3+ blank lines to max two
    $s = preg_replace("/\n{3,}/", "\n\n", $s);
    return $s;
}

foreach ($records as $r) {
    $orig = (string)$r->response;
    $norm = normalize_text_preserve_newlines($orig);
    if ($norm !== $orig) {
        $changed++;
        if ($apply) {
            $r->response = $norm;
            $DB->update_record('local_gis_ai_assistant_conversations', $r);
            $updated++;
        }
    }
}

cli_writeln("Records scanned: {$total}");
cli_writeln("Records needing normalization: {$changed}");
if ($apply) {
    cli_writeln("Records updated: {$updated}");
} else {
    cli_writeln("Dry-run only. Re-run with --apply to update records.");
}
