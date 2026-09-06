(function () {
    'use strict';
    function formDataForSubmit(form, submitter) {
        var payload = new FormData(form);
        if (submitter && submitter.name) payload.set(submitter.name, submitter.value || '');
        return payload;
    }
    function appendTextareaValues(payload, textarea) {
        var name = textarea.getAttribute('name');
        if (!name) return;
        var values = textarea.value.split(/[\s,]+/).map(function (value) { return value.trim(); }).filter(Boolean);
        payload.delete(name);
        values.forEach(function (value) { payload.append(name, value); });
    }
    function jsonResponse(response) { return response.text().then(function (text) { try { return JSON.parse(text); } catch (e) { return {}; } }); }
    document.querySelectorAll('[data-sc-migration-search]').forEach(function (input) {
        input.addEventListener('input', function () {
            var query = input.value.trim().toLowerCase();
            var table = input.closest('section').querySelector('[data-sc-migration-table]');
            if (!table) return;
            table.querySelectorAll('[data-sc-row]').forEach(function (row) { row.hidden = query !== '' && row.textContent.toLowerCase().indexOf(query) < 0; });
        });
    });
    document.querySelectorAll('[data-sc-generate-code]').forEach(function (button) {
        var input = button.closest('label').querySelector('[data-sc-code]');
        if (!input) return;
        function generate() {
            var alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
            var value = '';
            for (var index = 0; index < 8; index += 1) value += alphabet.charAt(Math.floor(Math.random() * alphabet.length));
            input.value = value;
        }
        button.addEventListener('click', generate);
        if (!input.value) generate();
    });
    document.querySelectorAll('[data-sc-delete]').forEach(function (button) {
        button.addEventListener('click', function () {
            if (!window.confirm('Delete this record?')) return;
            fetch(button.getAttribute('data-delete-url'), { credentials: 'same-origin', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }).then(jsonResponse).then(function (data) {
                if (data.result === true) { var row = button.closest('tr'); if (row) row.remove(); } else window.alert('The record could not be deleted.');
            }).catch(function () { window.alert('The request failed.'); });
        });
    });
    document.querySelectorAll('[data-sc-reload]').forEach(function (button) { button.addEventListener('click', function () { button.disabled = true; fetch(button.getAttribute('data-reload-url'), { credentials: 'same-origin', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }).then(jsonResponse).then(function (data) { button.disabled = false; if (!data.result) window.alert('The provider reload failed.'); }).catch(function () { button.disabled = false; window.alert('The provider reload failed.'); }); }); });
    document.querySelectorAll('[data-sc-provider-import]').forEach(function (button) {
        var section = button.closest('[data-sc-provider-id]');
        var status = section && section.querySelector('[data-sc-provider-status]');
        var providerId = section && section.getAttribute('data-sc-provider-id');
        if (!providerId) return;
        button.addEventListener('click', function () {
            button.disabled = true;
            if (status) status.textContent = 'Importing EPG source…';
            fetch('api?action=provider_import_epg&provider_id=' + encodeURIComponent(providerId), { credentials: 'same-origin', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }).then(jsonResponse).then(function (data) {
                if (data.status === 1) { if (status) status.textContent = 'EPG source imported (ID ' + String(data.data && data.data.id || '') + ').'; button.textContent = 'EPG imported'; }
                else if (data.status === 2) { if (status) status.textContent = 'EPG source already exists (ID ' + String(data.data && data.data.id || '') + ').'; button.disabled = false; }
                else { if (status) status.textContent = 'The provider EPG source could not be imported.'; button.disabled = false; }
            }).catch(function () { if (status) status.textContent = 'The provider EPG request failed.'; button.disabled = false; });
        });
    });
    document.querySelectorAll('form[data-sc-bulk-form]').forEach(function (form) {
        var selected = form.querySelector('[data-sc-selected]');
        var selectedInput = form.querySelector('[data-sc-selected-input]');
        form.querySelectorAll('[data-sc-enable]').forEach(function (toggle) {
            toggle.addEventListener('change', function () {
                var value = form.querySelector('[data-sc-value][name="' + toggle.getAttribute('data-sc-enable') + '"]');
                if (value) value.disabled = !toggle.checked;
            });
        });
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            var ids = (selectedInput.value || '').split(/[\s,]+/).map(function (value) { return value.trim(); }).filter(function (value, index, all) { return value !== '' && all.indexOf(value) === index; });
            if (!ids.length) { window.alert('Select at least one record to edit.'); return; }
            if (ids.some(function (value) { return !/^\d+$/.test(value); })) { window.alert('Selected IDs must be numeric.'); return; }
            selected.value = JSON.stringify(ids);
            var invalidValue = false;
            form.querySelectorAll('[data-sc-enable]').forEach(function (toggle) {
                if (!toggle.checked) return;
                var name = toggle.getAttribute('data-sc-enable');
                var value = form.querySelector('[data-sc-value][name="' + name + '"]');
                // An empty server tree is the documented SET-all-off operation. The
                // legacy category/day handlers cannot represent an empty array
                // without coercing it to an invalid ID, so keep those explicit.
                if (value && /\[\]$/.test(name) && !value.value.trim()) invalidValue = true;
            });
            if (invalidValue) { window.alert('Enter at least one value for each selected field.'); return; }
            var payload = formDataForSubmit(form, event.submitter || form.querySelector('[type="submit"]'));
            payload.set(selected.getAttribute('name'), JSON.stringify(ids));
            form.querySelectorAll('[data-sc-enable]').forEach(function (toggle) {
                if (!toggle.checked) return;
                var name = toggle.getAttribute('data-sc-enable');
                var value = form.querySelector('[data-sc-value][name="' + name + '"]');
                if (!value) return;
                if (name === 'server_tree_data') payload.set(name, value.value.trim() || '[]');
                else if (name === 'bouquets_selected') {
                    var bouquetIds = value.value.split(/[\s,]+/).map(function (id) { return id.trim(); }).filter(function (id) { return /^\d+$/.test(id); });
                    payload.set(name, JSON.stringify(bouquetIds));
                } else if (/\[\]$/.test(name)) appendTextareaValues(payload, value);
                else if (value.type === 'checkbox') {
                    if (value.checked) payload.set(name, value.value);
                    else payload.delete(name);
                } else payload.set(name, value.value);
            });
            form.querySelectorAll('textarea[name$="[]"]:not([disabled])').forEach(function (textarea) { appendTextareaValues(payload, textarea); });
            var submit = form.querySelector('button[type="submit"]'); if (submit) submit.disabled = true;
            fetch(form.action, { method: 'POST', body: payload, credentials: 'same-origin', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }).then(jsonResponse).then(function (data) { if (data.location) window.location.href = data.location; else { if (submit) submit.disabled = false; window.alert('The selected records were not changed.'); } }).catch(function () { if (submit) submit.disabled = false; window.alert('The request failed.'); });
        });
    });
    document.querySelectorAll('form.sc-form:not([data-sc-bulk-form])').forEach(function (form) {
        if (form.classList.contains('sc-user-editor')) return;
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            var payload = formDataForSubmit(form, event.submitter || form.querySelector('[type="submit"]'));
            form.querySelectorAll('textarea[name="whitelist[]"]').forEach(function (textarea) { appendTextareaValues(payload, textarea); });
            var submit = form.querySelector('[type="submit"]'); if (submit) submit.disabled = true;
            fetch(form.action, { method: 'POST', body: payload, credentials: 'same-origin', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }).then(jsonResponse).then(function (data) { if (data.location) window.location.href = data.location; else { if (submit) submit.disabled = false; var error = form.querySelector('[data-sc-form-error]'); if (error) { error.textContent = 'The request was rejected. Check the fields and try again.'; error.hidden = false; } } }).catch(function () { if (submit) submit.disabled = false; var error = form.querySelector('[data-sc-form-error]'); if (error) { error.textContent = 'The request failed. Please try again.'; error.hidden = false; } });
        });
    });
}());
