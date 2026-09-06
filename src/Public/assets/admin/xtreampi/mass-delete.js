(function () {
    'use strict';
    var root = document.querySelector('[data-sc-mass-delete]');
    if (!root) return;

    function clean(value) { return value == null || value === '' ? '—' : String(value).replace(/<[^>]*>/g, '').replace(/\s+/g, ' ').trim(); }
    function request(url, options) { return fetch(url, options).then(function (response) { return response.json(); }); }

    root.querySelectorAll('[data-sc-delete-group]').forEach(function (group) {
        var selected = new Set(), body = group.querySelector('[data-sc-delete-body]'), search = group.querySelector('[data-sc-delete-search]'), count = group.querySelector('[data-sc-delete-count]'), requestId = 0, start = 0, total = 0, pageSize = 100;
        var pager = document.createElement('div'), previous = document.createElement('button'), range = document.createElement('span'), next = document.createElement('button');
        pager.className = 'sc-form-actions'; previous.type = next.type = 'button'; previous.className = next.className = 'sc-button sc-button-secondary'; previous.textContent = 'Previous'; next.textContent = 'Next'; pager.append(previous, range, next); group.querySelector('.sc-toolbar').appendChild(pager);
        function update() { count.textContent = selected.size + ' selected'; }
        function updatePager(rows) { range.textContent = total ? 'Showing ' + (start + 1) + '–' + Math.min(start + rows, total) + ' of ' + total : 'Showing 0 of 0'; previous.disabled = start === 0; next.disabled = start + rows >= total; }
        function showState(message) { var row = document.createElement('tr'), cell = document.createElement('td'); body.replaceChildren(); cell.colSpan = 4; cell.className = 'sc-table-state'; cell.textContent = message; row.appendChild(cell); body.appendChild(row); }
        function load(reset) {
            if (reset) start = 0;
            var current = ++requestId;
            var params = new URLSearchParams({ id: group.getAttribute('data-table-id'), draw: String(current), start: String(start), length: String(pageSize), no_url: 'true', 'search[value]': search.value || '', 'order[0][column]': '0', 'order[0][dir]': 'asc' });
            group.querySelectorAll('[data-sc-delete-filter]').forEach(function (input) { if (input.value) params.set(input.getAttribute('data-sc-delete-filter'), input.value); });
            if (group.getAttribute('data-table-id') === 'stream_list') params.set('include_channels', 'true');
            showState('Loading…');
            request('table?' + params.toString(), { credentials: 'same-origin', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }).then(function (data) {
                if (current !== requestId) return;
                var rows = Array.isArray(data.data) ? data.data : [];
                total = Number(data.recordsFiltered || data.recordsTotal || 0) || 0;
                body.replaceChildren();
                if (!rows.length) { showState('No matching records.'); updatePager(0); return; }
                rows.forEach(function (values) {
                    var rawId = String(values[0] == null ? '' : values[0]), match = rawId.match(/^\d+$/), id = match ? match[0] : '';
                    if (!id) return;
                    var row = document.createElement('tr'), checkCell = document.createElement('td'), checkbox = document.createElement('input');
                    checkbox.type = 'checkbox'; checkbox.checked = selected.has(id);
                    checkbox.addEventListener('change', function () { if (checkbox.checked) selected.add(id); else selected.delete(id); update(); });
                    checkCell.appendChild(checkbox); row.appendChild(checkCell);
                    [id, clean(values[1]), clean(values[2])].forEach(function (value) { var cell = document.createElement('td'); cell.textContent = value; row.appendChild(cell); });
                    body.appendChild(row);
                });
                update(); updatePager(rows.length);
            }).catch(function () { if (current === requestId) showState('The table could not be loaded.'); });
        }

        group.querySelector('[data-sc-delete-load]').addEventListener('click', function () { load(true); });
        search.addEventListener('keydown', function (event) { if (event.key === 'Enter') { event.preventDefault(); load(true); } });
        previous.addEventListener('click', function () { if (start > 0) { start = Math.max(0, start - pageSize); load(false); } });
        next.addEventListener('click', function () { if (start + pageSize < total) { start += pageSize; load(false); } });
        group.querySelector('[data-sc-delete-form]').addEventListener('submit', function (event) {
            event.preventDefault();
            var error = group.querySelector('[data-sc-delete-error]'), form = event.currentTarget, submit = event.submitter || form.querySelector('[type="submit"]');
            if (!selected.size) { error.textContent = 'Select at least one record.'; error.hidden = false; return; }
            if (!window.confirm('Delete ' + selected.size + ' selected record(s)? This cannot be undone.')) return;
            error.hidden = true;
            group.querySelector('[data-sc-delete-payload]').value = JSON.stringify(Array.from(selected));
            submit.disabled = true;
            request(form.action, { method: 'POST', body: new FormData(form), credentials: 'same-origin', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }).then(function (data) {
                if (data.location) window.location.href = data.location;
                else { submit.disabled = false; error.textContent = 'The selected records were not deleted.'; error.hidden = false; }
            }).catch(function () { submit.disabled = false; error.textContent = 'The delete request failed. Please try again.'; error.hidden = false; });
        });
    });
}());
