// Teacher-side dynamic status/response update.

const showMessage = (message, isError = false) => {
    const box = document.getElementById('securecoursehub-message');
    if (!box) {
        return;
    }

    box.textContent = message;
    box.classList.remove('alert', 'alert-success', 'alert-danger');
    box.classList.add('alert', isError ? 'alert-danger' : 'alert-success');
};

export const init = (endpoint) => {
    document.querySelectorAll('.securecoursehub-update-button').forEach((button) => {
        button.addEventListener('click', async () => {
            const requestId = Number(button.dataset.requestId);
            const courseId = Number(button.dataset.courseId);
            const statusField = document.getElementById(`securecoursehub-status-${requestId}`);
            const responseField = document.getElementById(`securecoursehub-response-${requestId}`);
            const responsePreview = document.getElementById(`securecoursehub-response-preview-${requestId}`);

            if (!requestId || !courseId || !statusField || !responseField) {
                showMessage('The page is missing required request data.', true);
                return;
            }

            button.disabled = true;
            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        action: 'update_status',
                        id: requestId,
                        courseid: courseId,
                        status: statusField.value,
                        response: responseField.value,
                        sesskey: M.cfg.sesskey,
                    }),
                });

                let result;
                try {
                    result = await response.json();
                } catch (error) {
                    throw new Error('The server returned an invalid response.');
                }

                if (!response.ok || !result.success) {
                    throw new Error(result.error || 'The request failed.');
                }

                if (responsePreview) {
                    responsePreview.textContent = result.response || '';
                }
                showMessage(result.message || 'The request was updated.');
            } catch (error) {
                showMessage(error.message || 'The request could not reach the server.', true);
            } finally {
                button.disabled = false;
            }
        });
    });
};
