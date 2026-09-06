(function () {
    'use strict';
    var root = document.querySelector('[data-sc-settings]');
    if (!root) return;
    var tabs = Array.prototype.slice.call(root.querySelectorAll('[data-sc-settings-tab]'));
    var panels = Array.prototype.slice.call(root.querySelectorAll('[data-sc-settings-panel]'));
    var form = root.querySelector('form');
    function select(id) {
        var found = false;
        tabs.forEach(function (tab) { var active = tab.getAttribute('data-sc-settings-tab') === id; tab.setAttribute('aria-selected', active ? 'true' : 'false'); if (active) found = true; });
        panels.forEach(function (panel) { panel.hidden = panel.getAttribute('data-sc-settings-panel') !== id; });
        if (found && window.history && window.history.replaceState) window.history.replaceState(null, '', '#' + id);
    }
    tabs.forEach(function (tab) { tab.addEventListener('click', function () { select(tab.getAttribute('data-sc-settings-tab')); }); });
    if (form) {
        var saving = false;
        var submitButton = form.querySelector('button[type="submit"]');
        var errorBox = form.querySelector('[data-sc-settings-error]');
        var fallbackError = errorBox.getAttribute('data-default-message');
        function showError(message) {
            saving = false;
            submitButton.disabled = false;
            errorBox.textContent = message;
            errorBox.hidden = false;
            errorBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            if (saving) return;
            saving = true;
            submitButton.disabled = true;
            errorBox.hidden = true;
            fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                credentials: 'same-origin',
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function (response) {
                if (!response.ok) throw new Error(fallbackError);
                return response.json();
            }).then(function (response) {
                if (response && response.result === true && response.location) {
                    window.location.href = response.location;
                    return;
                }
                showError(response && response.message ? response.message : fallbackError);
            }).catch(function () {
                showError(fallbackError);
            });
        });
    }
    var initial = window.location.hash.replace('#', '');
    if (initial && root.querySelector('[data-sc-settings-tab="' + initial + '"]')) select(initial);
}());
