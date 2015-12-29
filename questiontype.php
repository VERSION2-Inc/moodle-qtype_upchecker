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
