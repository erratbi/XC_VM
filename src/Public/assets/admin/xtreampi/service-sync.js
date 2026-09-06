(function () {
    'use strict';

    var root = document.querySelector('[data-sc-service-sync]');
    if (!root) return;

    var itemName = root.getAttribute('data-item-name') || 'item';
    var itemUrl = root.getAttribute('data-item-url') || 'api';
    var result = root.querySelector('[data-sc-sync-result]');
    var busy = false;

    function setResult(message, error) {
        result.textContent = message;
        result.classList.toggle('is-error', Boolean(error));
    }

    function setBusy(value) {
        busy = value;
        root.querySelectorAll('button').forEach(function (button) { button.disabled = value; });
    }

    function request(url) {
        return fetch(url, { credentials: 'same-origin', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }).then(function (response) {
            if (!response.ok) throw new Error('Request failed');
            return response.json();
        }).then(function (data) {
            if (!data || data.result !== true) throw new Error('Request rejected');
            return data;
        });
    }

    function runGlobal(action) {
        if (busy) return;
        var messages = {
            enable: 'Are you sure you want to enable all ' + itemName + 's?',
            disable: 'Are you sure you want to disable all ' + itemName + 's?',
            kill: 'Are you sure you want to kill all running processes?'
        };
        if (!window.confirm(messages[action])) return;
        var url = root.getAttribute('data-' + action + '-url');
        if (!url) return;
        setBusy(true);
        setResult('Processing…', false);
        request(url).then(function () {
            setResult(action === 'kill' ? 'Running processes have been killed.' : 'All ' + itemName + 's have been ' + (action === 'enable' ? 'enabled.' : 'disabled.'), false);
            if (action !== 'kill') window.location.reload();
        }).catch(function () {
            setResult('The request could not be completed. Please try again.', true);
        }).finally(function () { setBusy(false); });
    }

    function runItem(action, id, button) {
        if (busy || !id) return;
        var confirmation = action === 'delete'
            ? 'Are you sure you want to delete this ' + itemName + '?'
            : 'Are you sure you want to run this ' + itemName + ' now?';
        if (!window.confirm(confirmation)) return;
        setBusy(true);
        setResult(action === 'delete' ? 'Deleting ' + itemName + '…' : 'Starting ' + itemName + '…', false);
        var params = new URLSearchParams({ sub: action, folder_id: id });
        request(itemUrl + '&' + params.toString()).then(function () {
            if (action === 'delete') {
                var row = root.querySelector('[data-sc-sync-row="' + Number(id) + '"]');
                if (row) row.remove();
                setResult('The ' + itemName + ' was deleted.', false);
            } else {
                setResult('The ' + itemName + ' has been started in the background.', false);
            }
        }).catch(function () {
            setResult('The ' + itemName + ' action could not be completed. Please try again.', true);
        }).finally(function () { setBusy(false); });
    }

    root.querySelectorAll('[data-sc-sync-global]').forEach(function (button) { button.addEventListener('click', function () { runGlobal(button.getAttribute('data-sc-sync-global')); }); });
    root.querySelectorAll('[data-sc-sync-action]').forEach(function (button) { button.addEventListener('click', function () { runItem(button.getAttribute('data-sc-sync-action'), button.getAttribute('data-sc-sync-id'), button); }); });
}());
