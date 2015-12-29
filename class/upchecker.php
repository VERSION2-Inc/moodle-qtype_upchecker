<?php
/**
 * Programming question type for Moodle
 *
 * @package    qtype
 * @subpackage upchecker
 * @copyright  VERSION2, Inc.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace upchecker;

defined('MOODLE_INTERNAL') || die();

class upchecker {
    /**
     *
     * @param string $identifier
     * @param string|\stdClass $a
     * @param string $component
     * @return string
     */
    public static function str($identifier, $a = null, $component = 'qtype_upchecker') {
        return get_string($identifier, $component, $a);
    }
}
