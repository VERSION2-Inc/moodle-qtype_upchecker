<?php
defined('MOODLE_INTERNAL') || die();

/**
 *
 * @param \stdClass $course
 * @param \stdClass $cm
 * @param context_module $context
 * @param string $filearea
 * @param array $args
 * @param boolean $forcedownload
 * @param array $options
 */
function qtype_upchecker_pluginfile(\stdClass $course, \stdClass $cm, context_module $context, $filearea,
        array $args, $forcedownload, array $options = array()) {
    global $CFG;
    require_once $CFG->libdir . '/questionlib.php';

    question_pluginfile($course, $context, 'qtype_upchecker', $filearea, $args, $forcedownload, $options);
}
