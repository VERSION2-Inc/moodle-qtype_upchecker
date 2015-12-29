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

class restore_qtype_upchecker_plugin extends restore_qtype_plugin {
    /**
     *
     * @return restore_path_element[]
     */
    protected function define_question_plugin_structure() {
        return [
            new restore_path_element('upchecker', $this->get_pathfor('/upchecker'))
        ];
    }

    public function process_upchecker($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $oldquestionid = $this->get_old_parentid('question');
        $newquestionid = $this->get_new_parentid('question');
        $questioncreated = (bool)$this->get_mappingid('question_created', $oldquestionid);

        if ($questioncreated) {
            $data->questionid = $newquestionid;
            $newitemid = $DB->insert_record('question_upchecker', $data);
            $this->set_mapping('question_upchecker', $oldid, $newitemid);
        }
    }
}
