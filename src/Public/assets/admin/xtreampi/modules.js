(function () {
    'use strict';

    var root = document.querySelector('[data-sc-modules]');
    if (!root) return;

    var endpoint = window.location.href.split('#')[0];
    var busy = false;
    var result = root.querySelector('[data-sc-module-result]');
    var resultTitle = root.querySelector('[data-sc-module-result-title]');
    var resultMessage = root.querySelector('[data-sc-module-result-message]');

    function showResult(type, message) {
        var error = type === 'danger';
        result.hidden = false;
        result.className = 'sc-notice sc-module-result ' + (error ? 'sc-notice-danger' : (type === 'success' ? 'sc-notice-success' : 'sc-notice-info'));
        resultTitle.textContent = error ? 'Module action failed' : 'Module manager';
        resultMessage.textContent = message || (error ? 'The request could not be completed.' : 'Module action complete.');
        result.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function setBusy(value) {
        busy = value;
        root.querySelectorAll('button').forEach(function (button) {
            if (value) {
                button.dataset.scModuleWasDisabled = button.disabled ? '1' : '0';
                button.disabled = true;
                return;
            }
            button.disabled = button.dataset.scModuleWasDisabled === '1';
            delete button.dataset.scModuleWasDisabled;
        });
    }

    function request(formData) {
        return fetch(endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
            body: formData
        }).then(function (response) {
            if (!response.ok) throw new Error('Request failed');
            return response.json().catch(function () { throw new Error('Unexpected server response'); });
        });
    }

    function refreshList() {
        return fetch(endpoint, { credentials: 'same-origin' }).then(function (response) {
            if (!response.ok) throw new Error('Could not refresh modules');
            return response.text();
        }).then(function (html) {
            var freshDocument = new DOMParser().parseFromString(html, 'text/html');
            var freshList = freshDocument.querySelector('[data-sc-module-list]');
            var currentList = root.querySelector('[data-sc-module-list]');
            if (!freshList || !currentList) throw new Error('Could not refresh modules');
            currentList.replaceWith(freshList);
        });
    }

    var fileInput = root.querySelector('[data-sc-module-file-input]');
    var fileName = root.querySelector('[data-sc-module-file-name]');
    var uploadButton = root.querySelector('[data-sc-module-upload-button]');
    if (fileInput) fileInput.addEventListener('change', function () {
        var file = fileInput.files && fileInput.files[0];
        fileName.textContent = file ? file.name : 'Choose a module package';
        uploadButton.disabled = !file;
    });

    root.addEventListener('submit', function (event) {
        var form = event.target.closest('[data-sc-module-form]');
        if (!form) return;
        event.preventDefault();
        if (busy) return;
        if (!form.checkValidity()) { form.reportValidity(); return; }
        var confirmation = form.getAttribute('data-sc-module-confirm');
        if (confirmation && !window.confirm(confirmation)) return;

        var button = form.querySelector('[type="submit"]');
        var originalLabel = button ? button.innerHTML : '';
        setBusy(true);
        if (button) button.innerHTML = 'Working…';
        request(new FormData(form)).then(function (response) {
            showResult(response.type, response.message);
            if (response.type === 'danger') return null;
            return refreshList().catch(function () {
                showResult('warning', (response.message || 'Module action complete.') + ' Refresh the page to confirm the latest module state.');
            });
        }).catch(function () {
            showResult('danger', 'The module request could not be completed. Please try again.');
        }).finally(function () {
            setBusy(false);
            if (button && button.isConnected) button.innerHTML = originalLabel;
        });
    });
}());
