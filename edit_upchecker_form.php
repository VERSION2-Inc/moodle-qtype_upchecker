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

require_once $CFG->dirroot . '/question/type/upchecker/locallib.php';
use upchecker\upchecker as uc;

class qtype_upchecker_edit_form extends question_edit_form {
    /**
     *
     * @return string
     */
    public function qtype() {
        return 'upchecker';
    }

    /**
     *
     * @param MoodleQuickForm $mform
     */
    protected function definition_inner($mform) {
        $textareaattr =  array('cols' => 50, 'rows' => 6);
        $mform->addElement('header', 'uploadhdr',
                get_string('uploadoption', 'qtype_upchecker'));
        $mform->addElement('textarea', 'caution', get_string('caution', 'qtype_upchecker'),
              $textareaattr ); //                 array('course' =>$this->coursefilesid) );
        $mform->setType('caution', PARAM_RAW);
        $mform->addHelpButton('caution', 'caution', 'qtype_upchecker');

        $mform->addElement('textarea', 'example',
                get_string('example', 'qtype_upchecker'),
              $textareaattr);
        $mform->setType('example', PARAM_RAW);
        $mform->addHelpButton('example', 'example', 'qtype_upchecker');

        $mform->addElement('textarea', 'hint',
                get_string('hint', 'qtype_upchecker'),
              $textareaattr
//                 array('course' =>$this->coursefilesid)
                );
        $mform->setType('hint', PARAM_RAW);
        $mform->addHelpButton('hint', 'hint', 'qtype_upchecker');

        $mform->addElement('date_time_selector', 'duedate', uc::str('limitdatetime'),
                array('optional' => true));

        $mform->addElement('select', 'permitlate',
                get_string('afterlimit', 'qtype_upchecker'),
                array(1 => get_string('accept', 'qtype_upchecker'),
                        0 => get_string('notaccept',
                                'qtype_upchecker')));
        $mform->setType('permitlate', PARAM_INT);

        $gradeopts = question_bank::fraction_options();
        $mform->addElement('select', 'lategrade',
                get_string('limitpoint', 'qtype_upchecker'),
                $gradeopts);

//         $mform->setType('lategrade', PARAM_NUMBER);

        $mform->addElement('text', 'checkurl',
                get_string('checkurl', 'qtype_upchecker'),
                array('size' => 60));
        $mform->setType('checkurl', PARAM_URL);
        $mform->addHelpButton('checkurl', 'checkurl', 'qtype_upchecker');

        $mform->addElement('text', 'fileparam',
                get_string('filepostname', 'qtype_upchecker'));
        $mform->setType('fileparam', PARAM_TEXT);
        $mform->addHelpButton('fileparam', 'filepostname', 'qtype_upchecker');

        $mform->addElement('text', 'restparams',
                get_string('restparams', 'qtype_upchecker'),
                array('size' => 60));
        $mform->setType('restparams', PARAM_TEXT);
        $mform->addHelpButton('restparams', 'restparams', 'qtype_upchecker');

        $mform->addElement('header', 'markinghdr',
                get_string('markingoption', 'qtype_upchecker'));
        $gradetypes = array(
                'manual' => uc::str('manualgrading'),
                'xml' => uc::str('xml'),
                'text' => uc::str('text')
        );
        $mform->addElement('select', 'gradetype',
                get_string('markingmethod', 'qtype_upchecker'),
                $gradetypes);
        $mform->addHelpButton('gradetype', 'markingmethod', 'qtype_upchecker');
//         $mform->setType('gradetype', PARAM_INT);

        $mform->addElement('text', 'gradetag',
                get_string('xmlgradeelement', 'qtype_upchecker'));
        $mform->setType('gradetag', PARAM_TEXT);
        $mform->addHelpButton('gradetag', 'xmlgradeelement', 'qtype_upchecker');
        $mform->addElement('text', 'feedbacktag',
                get_string('xmlfeedbackelement', 'qtype_upchecker'));
        $mform->setType('feedbacktag', PARAM_TEXT);
        $mform->addHelpButton('feedbacktag', 'xmlfeedbackelement', 'qtype_upchecker');
        /*
         $mform->addElement('text', 'answertag',
                 get_string('xmlanswerelement', 'qtype_upchecker'));
        $mform->setType('answertag', PARAM_TEXT);
        $mform->setHelpButton(
                'answertag',
                array('answertag',
                        get_string('xmlanswerelement', 'qtype_upchecker'),
                        'qtype_upchecker'));
        */

        $mform->addElement('text', 'questionurl', uc::str('questionhtml'),
                array('size' => 60));
        $mform->setType('questionurl', PARAM_TEXT);
        $mform->addElement('text', 'uploadfilename', uc::str('uploadfilename'),
                array('size' => 60));
        $mform->setType('uploadfilename', PARAM_TEXT);
        $mform->addHelpButton('uploadfilename', 'uploadfilename', 'qtype_upchecker');

        //        $mform->addElement('static', 'answersinstruct', get_string('correctanswers', 'quiz'), get_string('filloutoneanswer', 'quiz'));
        $mform->closeHeaderBefore('answersinstruct');

        $opts = array(
                'local' => uc::str('moodle'),
                'dropbox' => uc::str('dropbox')
        );
        $mform->addElement('select', 'storagetype', uc::str('storagetype'), $opts);
        $mform->setType('storagetype', PARAM_ALPHA);
//         $mform->setDefault('storagetype', 'dropbox');

        $mform->addElement('hidden', 'storagelogin', '');
        $mform->setType('storagelogin', PARAM_TEXT);
        $mform->addElement('hidden', 'storagepassword', '');
        $mform->setType('storagepassword', PARAM_TEXT);
    }
}
