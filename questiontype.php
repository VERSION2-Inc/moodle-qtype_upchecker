<?php
defined('MOODLE_INTERNAL') || die();

class qtype_upchecker extends question_type {
    /**
     *
     * @return string[]
     */
    public function extra_question_fields() {
        return array(
                'question_upchecker',
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
        );
    }
}
