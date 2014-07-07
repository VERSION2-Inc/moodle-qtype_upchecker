<?php
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

        $options = new backup_nested_element('upchecker_options');
//         $option = new backup_nested_element('upchecker_option', array('id'), array(
        $option = new backup_nested_element('upchecker', array('id'), array(
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
        ));

        $attempts = new backup_nested_element('upchecker_attempts');
        $attempt = new backup_nested_element('upchecker_attempt', array('id'), array(
                'questionattempt',
                'serverresult',
                'feedback'
        ));

//         $pluginwrapper->add_child($options);
//         $options->add_child($option);
//         $pluginwrapper->add_child($attempts);
//         $attempts->add_child($attempt);
        $pluginwrapper->add_child($option);

        $option->set_source_table('question_upchecker', array('questionid' => backup::VAR_PARENTID));
        $attempt->set_source_table('question_upchecker_attempts', array('question' => backup::VAR_PARENTID));
        //questionattemptも注釈しないと

        $attempt->annotate_ids('questionattempt', 'questionattempt');

        return $plugin;
    }
}
