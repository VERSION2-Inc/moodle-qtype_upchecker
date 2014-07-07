<?php
defined('MOODLE_INTERNAL') || die();

class restore_qtype_upchecker_plugin extends restore_qtype_plugin {
    /**
     *
     * @return restore_path_element[]
     */
    protected function define_question_plugin_structure() {
        $paths = array();
        $paths[] = new restore_path_element('upchecker', $this->get_pathfor('/upchecker_attempts/upchecker_attempt'));

        return $paths;
    }
}
