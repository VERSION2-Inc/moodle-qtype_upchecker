<?php
/**
 * Programming question type for Moodle
 *
 * @package    qtype
 * @subpackage upchecker
 * @copyright  VERSION2, Inc.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 *
 * @param int $oldversion
 * @return boolean
 */
function xmldb_qtype_upchecker_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2013050900) {

        // Define field storagetype to be dropped from question_upchecker
        $table = new xmldb_table('question_upchecker');

        $field = new xmldb_field('storagelogin');
        // Conditionally launch drop field storagetype
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        $field = new xmldb_field('storagepassword');
        // Conditionally launch drop field storagetype
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // upchecker savepoint reached
        upgrade_plugin_savepoint(true, 2013050900, 'qtype', 'upchecker');
    }

    return true;
}
