(function () {
    'use strict';

    var root = document.querySelector('[data-sc-ip-editor]');
    var form = root && root.querySelector('form');
    if (!form || root.dataset.canManage !== '1') return;

    var submit = form.querySelector('[type="submit"]');
    var error = form.querySelector('[data-sc-ip-error]');

    function showError(message) {
        if (!error) return;
        error.textContent = message;
        error.hidden = false;
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        if (submit) submit.disabled = true;
        if (error) error.hidden = true;

        var payload = new FormData(form);
        if (!payload.has('submit_ip')) payload.append('submit_ip', submit ? submit.value : 'Block');

        fetch(form.action, {
            method: 'POST',
            body: payload,
            credentials: 'same-origin',
            headers: { Accept: 'application/json, text/javascript, */*; q=0.01', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (response) {
            return response.text();
        }).then(function (text) {
            var data = null;
            try { data = JSON.parse(text); } catch (ignore) {}
            if (data && data.result === true && data.location) {
                window.location.href = data.location;
                return;
            }

            if (data && String(data.status) === String(form.dataset.statusInvalidIp)) {
                showError('Please enter a valid IP address / CIDR.');
            } else {
                showError(data && data.message ? String(data.message) : 'An error occurred while processing your request.');
            }
            if (submit) submit.disabled = false;
        }).catch(function () {
            showError('The IP address could not be added to the block list.');
            if (submit) submit.disabled = false;
        });
    });
}());
