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

class backup_qtype_upchecker_plugin extends backup_qtype_plugin {
    /**
     *
     * @return backup_plugin_element
     */
    protected function define_question_plugin_structure() {
        $plugin = $this->get_plugin_element(null, '../../qtype', 'upchecker');
        $pluginwrapper = new backup_nested_element($this->get_recommended_name());
        $plugin->add_child($pluginwrapper);

        $upchecker = new backup_nested_element('upchecker', ['id'], [
            'questionurl',
            'caution',
            'example',
            'hint',
            'duedate',
            'permitlate',
            'lategrade',
            'checkurl',
            'fileparam',
            'restparams',
            'uploadfilename',
            'gradetype',
            'gradetag',
            'feedbacktag',
            'storagetype'
        ]);

        $pluginwrapper->add_child($upchecker);
        $upchecker->set_source_table('question_upchecker', ['questionid' => backup::VAR_PARENTID]);

        return $plugin;
    }
}
