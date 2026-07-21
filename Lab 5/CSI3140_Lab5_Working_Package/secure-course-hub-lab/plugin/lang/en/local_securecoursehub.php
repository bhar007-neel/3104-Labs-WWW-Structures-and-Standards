<?php
// English language strings for Secure Course Hub.
//
// All user-visible text lives here. Error strings are written to be useful to
// the user while revealing nothing about the server, the database or other users.

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Secure Course Hub';

// Headings and interface labels.
$string['myrequests'] = 'My requests';
$string['courserequests'] = 'Course request queue';
$string['createrequestheading'] = 'Create a new request';
$string['editrequestheading'] = 'Edit request';
$string['title'] = 'Title';
$string['description'] = 'Description';
$string['status'] = 'Status';
$string['response'] = 'Teacher response';
$string['student'] = 'Student';
$string['created'] = 'Created';
$string['modified'] = 'Modified';
$string['actions'] = 'Actions';
$string['create'] = 'Create request';
$string['savechanges'] = 'Save changes';
$string['cancel'] = 'Cancel';
$string['edit'] = 'Edit';
$string['delete'] = 'Delete';
$string['update'] = 'Update';
$string['filter'] = 'Filter';
$string['filterbystatus'] = 'Filter by status';
$string['allstatuses'] = 'All statuses';
$string['currentuser'] = 'Signed in as {$a}';
$string['norequests'] = 'No requests were found.';
$string['unknownuser'] = 'Unknown user';
$string['confirmdelete'] = 'Delete this open request?';

// Status labels.
$string['statusopen'] = 'Open';
$string['statusinprogress'] = 'In progress';
$string['statusresolved'] = 'Resolved';

// Success messages.
$string['requestcreated'] = 'The request was created.';
$string['requestupdated'] = 'The request was updated.';
$string['requestdeleted'] = 'The request was deleted.';
$string['statusupdated'] = 'The request status was updated.';

// Validation and authorisation messages. These are the only server messages
// ever returned to a client, and none of them expose internal detail.
$string['validationerror'] = 'The submitted data is invalid.';
$string['titleinvalid'] = 'The title is required and must be 80 characters or fewer.';
$string['descriptioninvalid'] = 'The description is required and must be 2000 characters or fewer.';
$string['responseinvalid'] = 'The response must be 500 characters or fewer.';
$string['statusinvalid'] = 'The selected status is invalid.';
$string['requestnotopen'] = 'Only open requests can be changed or deleted by their owner.';
$string['notowner'] = 'You cannot access or modify a request that belongs to another user.';
$string['notenrolled'] = 'You do not have access to this course.';
$string['invalidaction'] = 'The requested action is not supported.';
$string['invalidjson'] = 'The request body was not valid JSON.';
$string['invalidsesskeymessage'] = 'The security token was missing or invalid. Reload the page and try again.';
$string['notfound'] = 'The requested record was not found.';
$string['accessdenied'] = 'You do not have permission to perform this action.';
$string['sessionexpired'] = 'Your session has expired. Sign in again and retry.';
$string['servererror'] = 'The operation could not be completed.';
$string['postonly'] = 'This endpoint accepts POST requests only.';

// Client-side messages passed to the JavaScript module.
$string['jsnetworkerror'] = 'The request could not reach the server. Check your connection and try again.';
$string['jsinvalidresponse'] = 'The server returned an unreadable response.';
$string['jsgenericerror'] = 'The request failed.';

// Capability descriptions.
$string['securecoursehub:viewown'] = 'View Secure Course Hub and own requests';
$string['securecoursehub:createrequest'] = 'Create course requests';
$string['securecoursehub:managecourserequests'] = 'Manage requests in an authorised course';

// Privacy API metadata.
$string['privacy:metadata:local_securecoursehub_req'] =
    'Course help requests raised by users, together with the staff response.';
$string['privacy:metadata:local_securecoursehub_req:courseid'] = 'The course the request belongs to.';
$string['privacy:metadata:local_securecoursehub_req:userid'] = 'The user who created the request.';
$string['privacy:metadata:local_securecoursehub_req:title'] = 'The short title of the request.';
$string['privacy:metadata:local_securecoursehub_req:description'] = 'The description supplied by the user.';
$string['privacy:metadata:local_securecoursehub_req:status'] = 'The workflow status of the request.';
$string['privacy:metadata:local_securecoursehub_req:response'] = 'The response written by teaching staff.';
$string['privacy:metadata:local_securecoursehub_req:timecreated'] = 'The time the request was created.';
$string['privacy:metadata:local_securecoursehub_req:timemodified'] = 'The time the request was last changed.';
