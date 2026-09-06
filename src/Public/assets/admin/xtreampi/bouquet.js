(function () {
    'use strict';

    var root = document.querySelector('[data-sc-bouquet-editor]');
    var form = root && root.querySelector('form');
    if (!form) return;

    var summary = root.querySelector('[data-sc-selection-summary]');
    var states = {};
    var draw = 0;

    function requestHeaders() {
        return { Accept: 'application/json, text/javascript, */*; q=0.01', 'X-Requested-With': 'XMLHttpRequest' };
    }

    function selectedCount() {
        return Object.keys(states).reduce(function (count, key) { return count + states[key].selected.size; }, 0);
    }

    function updateSelectionSummary() {
        var total = selectedCount();
        summary.textContent = total === 1 ? '1 item selected' : total + ' items selected';
        Object.keys(states).forEach(function (key) {
            var count = states[key].selected.size;
            var badge = root.querySelector('[data-sc-tab-count="' + key + '"]');
            if (badge) badge.textContent = String(count);
        });
    }

    function setLoading(state, message) {
        state.rows.replaceChildren();
        var row = document.createElement('tr');
        var cell = document.createElement('td');
        cell.className = 'sc-table-state';
        cell.colSpan = 3;
        cell.textContent = message;
        row.appendChild(cell);
        state.rows.appendChild(row);
        state.status.textContent = message;
    }

    function render(state) {
        state.rows.replaceChildren();
        if (!state.items.length) {
            var emptyRow = document.createElement('tr');
            var emptyCell = document.createElement('td');
            emptyCell.className = 'sc-table-state';
            emptyCell.colSpan = 3;
            emptyCell.textContent = 'No matching content found.';
            emptyRow.appendChild(emptyCell);
            state.rows.appendChild(emptyRow);
        }

        state.items.forEach(function (item) {
            var id = Number(item[0]);
            var row = document.createElement('tr');
            row.className = state.selected.has(id) ? 'is-selected' : '';
            row.tabIndex = 0;
            row.setAttribute('role', 'checkbox');
            row.setAttribute('aria-checked', state.selected.has(id) ? 'true' : 'false');
            var selection = document.createElement('td');
            selection.className = 'sc-picker-check';
            var checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.checked = state.selected.has(id);
            checkbox.setAttribute('aria-label', 'Include ' + String(item[1] || ('#' + id)));
            function setSelected(selected) {
                if (selected) state.selected.add(id); else state.selected.delete(id);
                checkbox.checked = selected;
                row.classList.toggle('is-selected', selected);
                row.setAttribute('aria-checked', selected ? 'true' : 'false');
                updateSelectionSummary();
                syncPageToggle(state);
            }
            checkbox.addEventListener('change', function () {
                setSelected(checkbox.checked);
            });
            row.addEventListener('click', function (event) {
                if (event.target !== checkbox) setSelected(!state.selected.has(id));
            });
            row.addEventListener('keydown', function (event) {
                if (event.key === ' ' || event.key === 'Enter') {
                    event.preventDefault();
                    setSelected(!state.selected.has(id));
                }
            });
            selection.appendChild(checkbox);
            row.appendChild(selection);
            var name = document.createElement('td');
            name.className = 'sc-picker-name';
            var title = document.createElement('strong');
            title.textContent = String(item[1] || ('#' + id));
            var identifier = document.createElement('small');
            identifier.textContent = '#' + id;
            name.appendChild(title);
            name.appendChild(identifier);
            row.appendChild(name);
            var category = document.createElement('td');
            category.className = 'sc-table-secondary';
            category.textContent = String(item[2] || 'No category');
            row.appendChild(category);
            state.rows.appendChild(row);
        });

        var first = state.total ? (state.page - 1) * state.pageSize + 1 : 0;
        var last = Math.min(state.page * state.pageSize, state.total);
        state.status.textContent = state.total ? 'Showing ' + first + '–' + last + ' of ' + state.total : 'No content found';
        state.pageLabel.textContent = 'Page ' + state.page + ' of ' + Math.max(1, Math.ceil(state.total / state.pageSize));
        state.previous.disabled = state.page <= 1;
        state.next.disabled = state.page * state.pageSize >= state.total;
        syncPageToggle(state);
    }

    function syncPageToggle(state) {
        var allSelected = state.items.length > 0 && state.items.every(function (item) { return state.selected.has(Number(item[0])); });
        state.pageCheckbox.checked = allSelected;
        state.pageCheckbox.indeterminate = !allSelected && state.items.some(function (item) { return state.selected.has(Number(item[0])); });
        state.togglePage.textContent = allSelected ? 'Clear page' : 'Select page';
    }

    function load(state) {
        var params = new URLSearchParams();
        params.set('draw', String(++draw));
        params.set('id', state.source);
        params.set('start', String((state.page - 1) * state.pageSize));
        params.set('length', String(state.pageSize));
        params.set('search[value]', state.search.value.trim());
        params.set('order[0][column]', '1');
        params.set('order[0][dir]', 'asc');
        if (state.category.value) params.set('category_id', state.category.value);
        setLoading(state, 'Loading content…');
        state.loading = true;
        fetch('table?' + params.toString(), { credentials: 'same-origin', headers: requestHeaders() })
            .then(function (response) { if (!response.ok) throw new Error('Request failed'); return response.json(); })
            .then(function (data) {
                state.items = Array.isArray(data.data) ? data.data : [];
                state.total = Number(data.recordsFiltered || 0);
                state.loaded = true;
                state.loading = false;
                render(state);
            })
            .catch(function () {
                state.loading = false;
                state.items = [];
                state.rows.replaceChildren();
                var row = document.createElement('tr');
                var cell = document.createElement('td');
                cell.className = 'sc-table-state is-error';
                cell.colSpan = 3;
                cell.textContent = 'Content could not be loaded. Try again.';
                row.appendChild(cell);
                state.rows.appendChild(row);
                state.status.textContent = 'Content could not be loaded.';
            });
    }

    function togglePage(state) {
        if (!state.items.length) return;
        var select = !state.items.every(function (item) { return state.selected.has(Number(item[0])); });
        state.items.forEach(function (item) {
            var id = Number(item[0]);
            if (select) state.selected.add(id); else state.selected.delete(id);
        });
        updateSelectionSummary();
        render(state);
    }

    Array.prototype.forEach.call(root.querySelectorAll('[data-sc-content-group]'), function (panel) {
        var key = panel.getAttribute('data-sc-content-group');
        var selected;
        try { selected = JSON.parse(panel.getAttribute('data-selected') || '[]'); } catch (ignore) { selected = []; }
        var state = states[key] = {
            key: key,
            panel: panel,
            source: panel.getAttribute('data-sc-table-source'),
            selected: new Set(selected.map(Number)),
            page: 1,
            pageSize: 25,
            total: 0,
            items: [],
            loaded: false,
            loading: false,
            search: panel.querySelector('[data-sc-picker-search]'),
            category: panel.querySelector('[data-sc-picker-category]'),
            rows: panel.querySelector('[data-sc-picker-rows]'),
            status: panel.querySelector('[data-sc-picker-status]'),
            pageLabel: panel.querySelector('[data-sc-picker-page]'),
            previous: panel.querySelector('[data-sc-picker-previous]'),
            next: panel.querySelector('[data-sc-picker-next]'),
            togglePage: panel.querySelector('[data-sc-picker-toggle-page]'),
            pageCheckbox: panel.querySelector('[data-sc-picker-toggle-page-checkbox]')
        };
        var searchTimer;
        state.search.addEventListener('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () { state.page = 1; load(state); }, 250);
        });
        state.category.addEventListener('change', function () { state.page = 1; load(state); });
        state.previous.addEventListener('click', function () { if (state.page > 1 && !state.loading) { state.page--; load(state); } });
        state.next.addEventListener('click', function () { if (state.page * state.pageSize < state.total && !state.loading) { state.page++; load(state); } });
        state.togglePage.addEventListener('click', function () { togglePage(state); });
        state.pageCheckbox.addEventListener('change', function () { togglePage(state); });
        panel.querySelector('[data-sc-picker-clear]').addEventListener('click', function () { state.selected.clear(); updateSelectionSummary(); render(state); });
    });

    Array.prototype.forEach.call(root.querySelectorAll('[data-sc-picker-tab]'), function (tab) {
        tab.addEventListener('click', function () {
            var key = tab.getAttribute('data-sc-picker-tab');
            Array.prototype.forEach.call(root.querySelectorAll('[data-sc-picker-tab]'), function (candidate) {
                var active = candidate === tab;
                candidate.setAttribute('aria-selected', active ? 'true' : 'false');
                candidate.tabIndex = active ? 0 : -1;
                states[candidate.getAttribute('data-sc-picker-tab')].panel.hidden = !active;
            });
            if (!states[key].loaded && !states[key].loading) load(states[key]);
        });
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        var data = {};
        Object.keys(states).forEach(function (key) { data[key] = Array.from(states[key].selected); });
        form.elements.bouquet_data.value = JSON.stringify(data);
        var error = form.querySelector('[data-sc-bouquet-error]');
        var submit = form.querySelector('[type=submit]');
        error.hidden = true;
        submit.disabled = true;
        fetch(form.action, { method: 'POST', body: new FormData(form), credentials: 'same-origin', headers: requestHeaders() })
            .then(function (response) { return response.text(); })
            .then(function (text) {
                var result;
                try { result = JSON.parse(text); } catch (ignore) { result = null; }
                if (result && result.location) { location.href = result.location; return; }
                submit.disabled = false;
                error.textContent = result && result.message ? result.message : 'The bouquet could not be saved.';
                error.hidden = false;
            })
            .catch(function () { submit.disabled = false; error.textContent = 'The bouquet could not be saved.'; error.hidden = false; });
    });

    updateSelectionSummary();
    var firstKey = Object.keys(states)[0];
    if (firstKey) load(states[firstKey]);
}());
