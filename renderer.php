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

class qtype_upchecker_renderer extends qtype_renderer {
    /**
     *
     * @param question_attempt $qa
     * @param question_display_options $options
     * @return string
     */
    public function formulation_and_controls(question_attempt $qa, question_display_options $options) {
        global $CFG, $PAGE;

        /* @var $question qtype_upchecker_question */
        $question = $qa->get_question();
        $questiontext = $question->format_questiontext($qa);

        $o = '';
        $o .= $this->container($questiontext, 'qtext');

        if (!empty($question->questionurl)) {
            $o .= \html_writer::tag('iframe', '', array(
                    'src' => $question->questionurl,
                    'class' => 'questionframe',
            ));
        }

        $o .= $this->item(uc::str('caution'), $question->caution);
        $o .= $this->item(uc::str('hint'), $question->hint);
        $o .= $this->item(uc::str('example'), $question->example);

        if (empty($options->readonly)) {
            if ($question->is_open()) {
                require_once($CFG->dirroot . '/lib/form/filemanager.php');

                if ($question->is_overdue()) {
                    $o .= $this->container(uc::str('overduepenalized', sprintf('%.2f', $question->lategrade)));
                }

                $pickeroptions = (object)array(
                        'mainfile' => null,
                        'maxfiles' => 1,
                        'itemid' => $qa->prepare_response_files_draft_itemid('answer', $options->context->id),
                        'context' => $options->context,
                        'return_types' => FILE_INTERNAL
                );
                $fm = new form_filemanager($pickeroptions);
                $filesrenderer = $this->page->get_renderer('core', 'files');
                $o .= $this->container_start('uc-clear');
                $o .= $filesrenderer->render($fm)
                    .html_writer::empty_tag(
                            'input', array(
                                    'type' => 'hidden',
                                    'name' => $qa->get_qt_field_name('answer'),
                                    'value' => $pickeroptions->itemid
                            )
                    );
                $o .= $this->container_end();
            } else {
                $o .= uc::str('overduecantsubmit');
            }
        } else {
            if ($file = $question->get_uploaded_file()) {
                $o .= $this->action_link(
                        $qa->get_response_file_url($file),
                        $this->pix_icon(file_file_icon($file), get_mimetype_description($file))
                        .$file->get_filename()
                );
            }
        }

        return $o;
    }

    /**
     *
     * @param question_attempt $qa
     * @return string
     */
    public function specific_feedback(question_attempt $qa) {
        $o = '';

        $question = $qa->get_question();
        $o .= \html_writer::start_tag('pre');
        $o .= $question->upcheckerattempt->feedback;
        $o .= \html_writer::end_tag('pre');

        return $o;
    }

    /**
     *
     * @param string $label
     * @param string $content
     * @param boolean $pre
     * @return string
     */
    private function item($label, $content, $pre = false) {
        if (!$content) {
            return '';
        }

        return $this->container(
                $this->container($label, 'itemlabel')
                .$this->container($content, 'itemcontent'),
                'itemcontainer'
        );
    }
}
