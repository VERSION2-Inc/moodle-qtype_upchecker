<?php
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
