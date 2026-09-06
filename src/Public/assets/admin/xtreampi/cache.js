(function () {
    'use strict';

    var root = document.querySelector('[data-sc-cache]');
    if (!root) return;
    var result = root.querySelector('[data-sc-cache-result]');
    var busy = false;

    function setResult(message, error) { result.textContent = message; result.classList.toggle('is-error', Boolean(error)); }
    function setBusy(value) { busy = value; root.querySelectorAll('button').forEach(function (button) { button.disabled = value; }); }
    function request(url, options) {
        return fetch(url, Object.assign({ credentials: 'same-origin', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }, options || {})).then(function (response) {
            if (!response.ok) throw new Error('Request failed');
            return response.json();
        }).then(function (data) { if (!data || data.result !== true) throw new Error('Request rejected'); return data; });
    }
    function action(action) {
        if (busy) return;
        var confirmation = {
            disable_cache: 'Disable the cache system?',
            clear_redis: 'Clear the Redis database? This will drop all active connections.',
            disable_handler: 'Disable the Redis connection handler? This will disconnect active clients.'
        };
        if (confirmation[action] && !window.confirm(confirmation[action])) return;
        setBusy(true); setResult('Processing…', false);
        request('api?action=' + encodeURIComponent(action)).then(function () {
            window.location.reload();
        }).catch(function () {
            setBusy(false); setResult('The request could not be completed. Please try again.', true);
        });
    }
    root.querySelectorAll('[data-sc-cache-action]').forEach(function (button) { button.addEventListener('click', function () { action(button.getAttribute('data-sc-cache-action')); }); });
    var form = root.querySelector('[data-sc-cache-form]');
    if (form) form.addEventListener('submit', function (event) {
        event.preventDefault();
        if (busy) return;
        var error = form.querySelector('[data-sc-cache-form-error]');
        if (!form.checkValidity()) { form.reportValidity(); return; }
        setBusy(true); error.hidden = true;
        request(form.action, { method: 'POST', body: new FormData(form) }).then(function (data) {
            if (!data.location) throw new Error('Missing redirect');
            window.location.href = data.location;
        }).catch(function () {
            setBusy(false); error.textContent = 'Cache settings could not be saved. Please check the cron values and try again.'; error.hidden = false; error.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        });
    });
}());
