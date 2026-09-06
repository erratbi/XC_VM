(function () {
    'use strict';

    var root = document.querySelector('[data-sc-backups]');
    if (!root) return;

    var form = root.querySelector('[data-sc-backup-settings]');
    var settingsError = root.querySelector('[data-sc-backup-settings-error]');
    var saveButton = root.querySelector('[data-sc-backup-save]');
    var rows = root.querySelector('[data-sc-backup-rows]');
    var summary = root.querySelector('[data-sc-backup-summary]');
    var result = root.querySelector('[data-sc-backup-result]');
    var refreshButton = root.querySelector('[data-sc-backup-refresh]');
    var createButton = root.querySelector('[data-sc-backup-create]');
    var restoreResult = root.querySelector('[data-sc-backup-restore-result]');
    var restoreTitle = root.querySelector('[data-sc-backup-restore-title]');
    var restoreMessage = root.querySelector('[data-sc-backup-restore-message]');
    var tableEndpoint = root.getAttribute('data-table-endpoint') || 'table';
    var apiEndpoint = root.getAttribute('data-api-endpoint') || 'api';
    var operationBusy = false;
    var restorePending = false;

    function node(tag, className, text) {
        var element = document.createElement(tag);
        if (className) element.className = className;
        if (typeof text !== 'undefined') element.textContent = text;
        return element;
    }

    function state(message, error) {
        rows.replaceChildren();
        var row = document.createElement('tr');
        var cell = node('td', 'sc-table-state' + (error ? ' is-error' : ''), message);
        cell.colSpan = 6;
        row.appendChild(cell);
        rows.appendChild(row);
    }

    function request(url, options) {
        return fetch(url, Object.assign({
            credentials: 'same-origin',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }, options || {})).then(function (response) {
            if (!response.ok) throw new Error('Request failed');
            return response.json();
        });
    }

    function statusCell(value) {
        var source = String(value == null ? '' : value);
        var label = 'Unavailable';
        var className = 'sc-row-status is-disabled';
        var title = '';
        var match = source.match(/title=['\"]([^'\"]*)/i);
        if (match) title = match[1];
        if (/text-success/.test(source)) {
            label = 'Available';
            className = 'sc-row-status is-active';
        } else if (/text-warning/.test(source)) {
            label = title || 'Uploading';
            className = 'sc-row-status is-warning';
        } else if (/text-danger/.test(source)) {
            label = title || 'Unavailable';
            className = 'sc-row-status is-banned';
        }
        var cell = document.createElement('td');
        cell.appendChild(node('span', className, label));
        return cell;
    }

    function actionButton(label, className, handler) {
        var button = node('button', 'sc-row-action' + (className ? ' ' + className : ''), label);
        button.type = 'button';
        button.disabled = operationBusy || restorePending;
        button.addEventListener('click', handler);
        return button;
    }

    function render(data) {
        var items = Array.isArray(data.data) ? data.data : [];
        rows.replaceChildren();
        if (!items.length) {
            state('No backups are available yet. Create a backup to add one.', false);
        } else {
            items.forEach(function (item) {
                var row = document.createElement('tr');
                row.appendChild(node('td', 'sc-table-secondary', item[0] == null ? '—' : String(item[0])));
                var filename = String(item[1] == null ? '' : item[1]);
                row.appendChild(node('td', '', filename || '—'));
                row.appendChild(node('td', 'sc-table-secondary', item[2] == null ? '—' : String(item[2])));
                row.appendChild(statusCell(item[3]));
                row.appendChild(statusCell(item[4]));
                var actions = node('td', 'sc-table-actions');
                actions.appendChild(actionButton('Restore', 'is-warning', function () { restoreBackup(filename); }));
                actions.appendChild(actionButton('Delete', 'is-danger', function () { deleteBackup(filename); }));
                row.appendChild(actions);
                rows.appendChild(row);
            });
        }
        summary.textContent = items.length === 1 ? '1 backup available' : items.length + ' backups available';
    }

    function load() {
        if (restorePending) return;
        state('Loading backups…', false);
        var params = new URLSearchParams({ draw: '1', id: 'backups', view: 'xtreampi', start: '0', length: '1000' });
        request(tableEndpoint + '?' + params.toString()).then(render).catch(function () {
            state('Backups could not be loaded. Refresh to retry.', true);
            summary.textContent = 'Unable to load backups';
        });
    }

    function api(sub, filename) {
        var params = new URLSearchParams({ action: 'backup', sub: sub, filename: filename || '' });
        return request(apiEndpoint + '?' + params.toString());
    }

    function setOperation(message) {
        result.textContent = message || '';
        root.querySelectorAll('[data-sc-backup-create], [data-sc-backup-refresh], [data-sc-backup-rows] button').forEach(function (button) { button.disabled = operationBusy || restorePending; });
    }

    function createBackup() {
        if (operationBusy || restorePending || !window.confirm('Create a database backup now?')) return;
        operationBusy = true;
        createButton.disabled = true;
        setOperation('Creating backup…');
        api('backup', '').then(function (data) {
            if (!data || data.result !== true) throw new Error('Request rejected');
            result.textContent = 'Backup creation started. It may take a few minutes; refresh the inventory when it is complete.';
        }).catch(function () {
            operationBusy = false;
            createButton.disabled = false;
            setOperation('The backup request could not be confirmed. Refresh the inventory before trying again.');
        });
    }

    function deleteBackup(filename) {
        if (operationBusy || restorePending || !filename || !window.confirm('Are you sure you want to delete this backup?')) return;
        operationBusy = true;
        setOperation('Deleting backup…');
        api('delete', filename).then(function (data) {
            if (!data || data.result !== true) throw new Error('Request rejected');
            operationBusy = false;
            setOperation('Backup successfully deleted.');
            load();
        }).catch(function () {
            operationBusy = false;
            setOperation('The backup could not be deleted. Retry when the server is available.');
            load();
        });
    }

    function restoreBackup(filename) {
        if (operationBusy || restorePending || !filename || !window.confirm('Are you sure you want to restore from this backup? This will erase your current database.')) return;
        operationBusy = true;
        setOperation('Restoring backup…');
        api('restore', filename).then(function (data) {
            if (!data || data.result !== true) throw new Error('Request rejected');
            restorePending = true;
            operationBusy = false;
            restoreResult.hidden = false;
            restoreTitle.textContent = 'Restore response received';
            restoreMessage.textContent = 'Exit XtreamPi and verify the service before signing in again.';
            setOperation('Restore response received. Exit XtreamPi before signing in again.');
        }).catch(function () {
            operationBusy = false;
            restorePending = true;
            restoreResult.hidden = false;
            restoreTitle.textContent = 'Restore status could not be confirmed';
            restoreMessage.textContent = 'Do not retry until you have verified the database and service health.';
            setOperation('Restore status could not be confirmed. Verify the system before taking another action.');
        });
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        if (saveButton.disabled) return;
        settingsError.hidden = true;
        saveButton.disabled = true;
        request(form.action, { method: 'POST', body: new FormData(form) }).then(function (data) {
            if (!data || data.result !== true || !data.location) throw new Error('Save failed');
            window.location.href = data.location;
        }).catch(function () {
            saveButton.disabled = false;
            settingsError.textContent = 'Backup settings could not be saved. Please try again.';
            settingsError.hidden = false;
            settingsError.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        });
    });

    root.querySelectorAll('[data-sc-backup-number]').forEach(function (input) {
        input.addEventListener('input', function () { input.value = input.value.replace(/[^\d]/g, ''); });
    });
    refreshButton.addEventListener('click', load);
    createButton.addEventListener('click', createBackup);
    load();
}());
