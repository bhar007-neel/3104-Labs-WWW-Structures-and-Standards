<?php
// Course navigation integration.

defined('MOODLE_INTERNAL') || die();

function local_securecoursehub_extend_navigation_course(
    navigation_node $navigation,
    stdClass $course,
    context_course $context
): void {
    if (!isloggedin() || isguestuser()) {
        return;
    }

    $canview = has_capability('local/securecoursehub:viewown', $context);
    $canmanage = has_capability('local/securecoursehub:managecourserequests', $context);
    if (!$canview && !$canmanage) {
        return;
    }

    $url = new moodle_url('/local/securecoursehub/index.php', ['courseid' => $course->id]);
    $navigation->add(
        get_string('pluginname', 'local_securecoursehub'),
        $url,
        navigation_node::TYPE_CUSTOM,
        null,
        'local_securecoursehub'
    );
}
