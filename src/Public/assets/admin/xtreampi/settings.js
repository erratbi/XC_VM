(function () {
    'use strict';
    var root = document.querySelector('[data-sc-settings]');
    if (!root) return;
    var tabs = Array.prototype.slice.call(root.querySelectorAll('[data-sc-settings-tab]'));
    var panels = Array.prototype.slice.call(root.querySelectorAll('[data-sc-settings-panel]'));
    var form = root.querySelector('form');
    root.querySelectorAll('[data-static-picker]').forEach(function (picker) {
        var items = [], search = picker.querySelector('[data-static-search]'), options = picker.querySelector('[data-static-options]'), values = picker.querySelector('[data-static-values]'), empty = picker.querySelector('[data-static-empty]');
        try { items = JSON.parse(picker.querySelector('[data-static-items]').textContent); } catch (ignore) {}
        function isSelected(id) { return Array.prototype.some.call(values.querySelectorAll('input[type="hidden"]'), function (input) { return String(input.value) === String(id); }); }
        function refresh() {
            if (empty) empty.hidden = values.children.length > 0;
            var term = search.value.trim().toLowerCase();
            options.replaceChildren();
            items.filter(function (item) { return !isSelected(item.id) && (!term || String(item.text).toLowerCase().indexOf(term) !== -1); }).slice(0, 50).forEach(function (item) {
                var option = document.createElement('button');
                option.type = 'button';
                option.textContent = item.text;
                option.addEventListener('click', function () {
                    var token = document.createElement('span'), input = document.createElement('input'), remove = document.createElement('button');
                    var isAll = String(item.id).toUpperCase() === 'ALL';
                    if (isAll) values.replaceChildren();
                    else values.querySelectorAll('span[data-id="ALL"], span[data-id="all"]').forEach(function (allToken) { allToken.remove(); });
                    token.dataset.id = item.id;
                    input.type = 'hidden';
                    input.name = picker.dataset.name;
                    input.value = item.id;
                    remove.type = 'button';
                    remove.textContent = '×';
                    remove.setAttribute('aria-label', 'Remove');
                    remove.addEventListener('click', function () { token.remove(); refresh(); });
                    token.append(document.createTextNode(item.text), input, remove);
                    values.appendChild(token);
                    search.value = '';
                    refresh();
                });
                options.appendChild(option);
            });
            options.hidden = options.children.length === 0;
        }
        values.querySelectorAll('button').forEach(function (remove) { remove.addEventListener('click', function () { remove.closest('span').remove(); refresh(); }); });
        search.addEventListener('focus', refresh);
        search.addEventListener('input', refresh);
        document.addEventListener('click', function (event) { if (!picker.contains(event.target)) options.hidden = true; });
        refresh();
        options.hidden = true;
    });
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
