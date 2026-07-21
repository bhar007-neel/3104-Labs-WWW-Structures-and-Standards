// Secure Course Hub client module.
//
// Performs the state-changing operations over fetch() + JSON and updates the
// page without a full reload.
//
// Two rules are followed throughout:
//   1. Every value that came from the server is inserted with textContent or as
//      a DOM property. innerHTML is never used with server data, so an injected
//      script string is rendered as text and cannot execute.
//   2. The client hides controls a user may not use, but that is usability only.
//      The server repeats every authentication, capability, ownership, state and
//      sesskey check in ajax.php.
//
// Written as an AMD module so it runs directly from amd/build without a grunt
// build step.

define([], function() {
    'use strict';

    var config = {
        endpoint: '',
        courseid: 0,
        canmanage: false,
        strings: {}
    };

    /**
     * Show a success or error message in the page-level live region.
     *
     * @param {String} message Message text.
     * @param {Boolean} isError Whether to style the message as an error.
     */
    function showMessage(message, isError) {
        var box = document.getElementById('securecoursehub-message');
        if (!box) {
            return;
        }

        // textContent, never innerHTML: server text is data, not markup.
        box.textContent = message;
        box.className = 'securecoursehub-message alert ' + (isError ? 'alert-danger' : 'alert-success');
    }

    /**
     * POST a JSON payload and return the decoded success body.
     *
     * Distinguishes network failure, an unreadable body and a structured
     * server-side rejection, so the user always gets a meaningful message.
     *
     * @param {Object} payload Request payload; the sesskey is added here.
     * @return {Promise} Resolved with the parsed response body.
     */
    function postJson(payload) {
        payload.sesskey = M.cfg.sesskey;

        return fetch(config.endpoint, {
            method: 'POST',
            // Same-origin only. The session cookie travels with the request; no
            // authentication secret is ever copied into JavaScript.
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        }).catch(function() {
            throw new Error(config.strings.network);
        }).then(function(response) {
            return response.json().catch(function() {
                throw new Error(config.strings.invalidresponse);
            }).then(function(result) {
                // A 401 means the session expired or the user logged out; the
                // server supplies the wording, the client simply stops here.
                if (!response.ok || !result || result.success !== true) {
                    throw new Error((result && result.error) ? result.error : config.strings.generic);
                }
                return result;
            });
        });
    }

    /**
     * Create a table cell containing plain text.
     *
     * @param {String} text Cell text.
     * @return {Object} The new cell element.
     */
    function textCell(text) {
        var cell = document.createElement('td');
        cell.textContent = (text === null || text === undefined) ? '' : String(text);
        return cell;
    }

    /**
     * Build the Edit link and Delete form for a newly created own request.
     *
     * @param {Object} request Request row returned by the server.
     * @return {Object} The actions cell element.
     */
    function buildOwnActions(request) {
        var cell = document.createElement('td');

        var editLink = document.createElement('a');
        editLink.className = 'btn btn-secondary btn-sm';
        editLink.href = M.cfg.wwwroot + '/local/securecoursehub/index.php?courseid=' +
            encodeURIComponent(config.courseid) + '&editid=' + encodeURIComponent(request.id);
        editLink.textContent = config.strings.edit;
        cell.appendChild(editLink);

        var form = document.createElement('form');
        form.method = 'post';
        form.className = 'securecoursehub-inline-form';
        form.action = M.cfg.wwwroot + '/local/securecoursehub/index.php?courseid=' +
            encodeURIComponent(config.courseid);

        [
            {name: 'courseid', value: config.courseid},
            {name: 'sesskey', value: M.cfg.sesskey},
            {name: 'action', value: 'delete'},
            {name: 'id', value: request.id}
        ].forEach(function(field) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = field.name;
            input.value = field.value;
            form.appendChild(input);
        });

        var button = document.createElement('button');
        button.type = 'submit';
        button.className = 'btn btn-danger btn-sm securecoursehub-delete-button';
        button.setAttribute('data-confirm', config.strings.confirmdelete);
        button.textContent = config.strings.deletelabel;
        form.appendChild(button);

        cell.appendChild(form);
        return cell;
    }

    /**
     * Insert a newly created request at the top of the user's own table.
     *
     * @param {Object} request Request row returned by the server.
     */
    function prependOwnRow(request) {
        var table = document.getElementById('securecoursehub-own-table');
        if (!table) {
            return;
        }

        var body = table.tBodies[0] || table.appendChild(document.createElement('tbody'));
        var row = document.createElement('tr');
        row.setAttribute('data-request-id', request.id);

        row.appendChild(textCell(request.title));
        row.appendChild(textCell(request.description));
        row.appendChild(textCell(request.statuslabel));
        row.appendChild(textCell(request.response));
        row.appendChild(textCell(request.timemodifiedformatted));
        row.appendChild(buildOwnActions(request));

        body.insertBefore(row, body.firstChild);

        var empty = document.getElementById('securecoursehub-own-empty');
        if (empty) {
            empty.hidden = true;
            empty.textContent = '';
        }
    }

    /**
     * Wire the teacher status/response update buttons.
     *
     * This is the required state-changing fetch() operation: POST JSON, then
     * update the row in place with no page reload.
     */
    function initTeacherUpdates() {
        var buttons = document.querySelectorAll('.securecoursehub-update-button');

        Array.prototype.forEach.call(buttons, function(button) {
            button.addEventListener('click', function() {
                var id = Number(button.getAttribute('data-request-id'));
                var statusField = document.getElementById('securecoursehub-status-' + id);
                var responseField = document.getElementById('securecoursehub-response-' + id);
                var statusLabel = document.getElementById('securecoursehub-statuslabel-' + id);

                if (!id || !statusField) {
                    showMessage(config.strings.generic, true);
                    return;
                }

                button.disabled = true;

                postJson({
                    action: 'update_status',
                    id: id,
                    status: statusField.value,
                    response: responseField ? responseField.value : ''
                }).then(function(result) {
                    // Dynamic update: the status text changes without a reload.
                    if (statusLabel) {
                        statusLabel.textContent = result.request.statuslabel;
                    }
                    if (responseField) {
                        responseField.value = result.request.response;
                    }

                    var row = document.querySelector(
                        '#securecoursehub-course-table tr[data-request-id="' + id + '"]'
                    );
                    if (row && row.cells.length >= 6) {
                        row.cells[5].textContent = result.request.timemodifiedformatted;
                    }

                    showMessage(result.message, false);
                    return result;
                }).catch(function(error) {
                    showMessage(error.message || config.strings.generic, true);
                }).then(function() {
                    button.disabled = false;
                });
            });
        });
    }

    /**
     * Submit the create form over fetch() so the new row appears without a reload.
     *
     * The form still works with JavaScript disabled: without this handler it
     * posts to index.php, which applies the same checks.
     */
    function initCreateForm() {
        var form = document.getElementById('securecoursehub-request-form');
        if (!form || form.getAttribute('data-mode') !== 'create') {
            return;
        }

        form.addEventListener('submit', function(event) {
            var titleField = document.getElementById('securecoursehub-title');
            var descriptionField = document.getElementById('securecoursehub-description');
            if (!titleField || !descriptionField) {
                return;
            }

            event.preventDefault();

            var submit = form.querySelector('input[type="submit"]');
            if (submit) {
                submit.disabled = true;
            }

            postJson({
                action: 'create',
                courseid: config.courseid,
                title: titleField.value,
                description: descriptionField.value
            }).then(function(result) {
                prependOwnRow(result.request);
                form.reset();
                showMessage(result.message, false);
                return result;
            }).catch(function(error) {
                showMessage(error.message || config.strings.generic, true);
            }).then(function() {
                if (submit) {
                    submit.disabled = false;
                }
            });
        });
    }

    /**
     * Confirm before a delete form is submitted.
     */
    function initDeleteConfirmation() {
        document.addEventListener('click', function(event) {
            if (!event.target || !event.target.closest) {
                return;
            }

            var button = event.target.closest('.securecoursehub-delete-button');
            if (button && !window.confirm(button.getAttribute('data-confirm') || config.strings.confirmdelete)) {
                event.preventDefault();
            }
        });
    }

    return {
        /**
         * Entry point called from index.php via $PAGE->requires->js_call_amd().
         *
         * @param {Object} options Endpoint, course id, capability hint and strings.
         */
        init: function(options) {
            config = options || config;
            config.strings = config.strings || {};

            initCreateForm();
            initDeleteConfirmation();

            if (config.canmanage) {
                initTeacherUpdates();
            }
        }
    };
});
