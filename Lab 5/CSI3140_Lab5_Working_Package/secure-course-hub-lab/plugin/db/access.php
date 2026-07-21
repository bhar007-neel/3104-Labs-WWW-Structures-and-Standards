<?php
// Capability definitions for Secure Course Hub.
//
// Every capability declared here is enforced server side; declaring a capability
// is never sufficient on its own. See index.php, ajax.php and request_service.php.

defined('MOODLE_INTERNAL') || die();

$capabilities = [
    // View the plugin page and one's own requests.
    'local/securecoursehub:viewown' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'student' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],

    // Create a request in a course the user can access.
    'local/securecoursehub:createrequest' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'student' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],

    // View and manage every request in an authorised course. This grants sight
    // of other users' request text, hence RISK_PERSONAL.
    'local/securecoursehub:managecourserequests' => [
        'captype' => 'write',
        'riskbitmask' => RISK_PERSONAL,
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],
];
