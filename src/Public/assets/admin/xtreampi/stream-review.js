(function () {
    'use strict';
    var root = document.querySelector('[data-sc-stream-review]');
    if (!root) return;

    var selectionForm = root.querySelector('[data-sc-review-selection]');
    if (selectionForm) {
        var selected = new Set(), body = selectionForm.querySelector('[data-sc-review-selection-body]'), payload = selectionForm.querySelector('[data-sc-review-selected]'), count = selectionForm.querySelector('[data-sc-review-count]'), error = selectionForm.querySelector('[data-sc-review-selection-error]'), requestId = 0, start = 0, total = 0, pageSize = 100;
        var pager = document.createElement('div'), previous = document.createElement('button'), range = document.createElement('span'), next = document.createElement('button');
        pager.className = 'sc-form-actions'; previous.type = next.type = 'button'; previous.className = next.className = 'sc-button sc-button-secondary'; previous.textContent = 'Previous'; next.textContent = 'Next'; pager.append(previous, range, next); selectionForm.querySelector('.sc-toolbar').appendChild(pager);
        function clean(value) { return value == null || value === '' ? '—' : String(value).replace(/<[^>]*>/g, '').replace(/\s+/g, ' ').trim(); }
        function update() { count.textContent = selected.size + ' selected'; payload.value = JSON.stringify(Array.from(selected)); }
        function updatePager(rows) { range.textContent = total ? 'Showing ' + (start + 1) + '–' + Math.min(start + rows, total) + ' of ' + total : 'Showing 0 of 0'; previous.disabled = start === 0; next.disabled = start + rows >= total; }
        function state(message) { var row = document.createElement('tr'), cell = document.createElement('td'); body.replaceChildren(); cell.colSpan = 6; cell.className = 'sc-table-state'; cell.textContent = message; row.appendChild(cell); body.appendChild(row); }
        function load(reset) {
            if (reset) start = 0;
            var current = ++requestId, search = selectionForm.querySelector('[data-sc-review-search]'), category = selectionForm.querySelector('[data-sc-review-category]');
            var params = new URLSearchParams({ id: 'stream_list', draw: String(current), start: String(start), length: String(pageSize), no_url: 'true', 'search[value]': search.value || '', 'order[0][column]': '0', 'order[0][dir]': 'asc' });
            if (category.value) params.set('category', category.value);
            state('Loading…');
            fetch('table?' + params.toString(), { credentials: 'same-origin', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }).then(function (response) { return response.json(); }).then(function (data) {
                if (current !== requestId) return;
                var rows = Array.isArray(data.data) ? data.data : [];
                total = Number(data.recordsFiltered || data.recordsTotal || 0) || 0;
                body.replaceChildren();
                if (!rows.length) { state('No matching streams.'); updatePager(0); return; }
                rows.forEach(function (values) {
                    var id = String(values[0] == null ? '' : values[0]);
                    if (!/^\d+$/.test(id)) return;
                    var row = document.createElement('tr'), selector = document.createElement('input'), selectorCell = document.createElement('td');
                    selector.type = 'checkbox'; selector.checked = selected.has(id);
                    selector.addEventListener('change', function () { if (selector.checked) selected.add(id); else selected.delete(id); update(); });
                    selectorCell.appendChild(selector); row.appendChild(selectorCell);
                    [id, clean(values[2]), clean(values[3]), clean(values[4]), clean(values[5])].forEach(function (value) { var cell = document.createElement('td'); cell.textContent = value; row.appendChild(cell); });
                    body.appendChild(row);
                });
                update(); updatePager(rows.length);
            }).catch(function () { if (current === requestId) state('The stream list could not be loaded.'); });
        }
        selectionForm.querySelector('[data-sc-review-load]').addEventListener('click', function () { load(true); });
        selectionForm.querySelector('[data-sc-review-search]').addEventListener('keydown', function (event) { if (event.key === 'Enter') { event.preventDefault(); load(true); } });
        previous.addEventListener('click', function () { if (start > 0) { start = Math.max(0, start - pageSize); load(false); } });
        next.addEventListener('click', function () { if (start + pageSize < total) { start += pageSize; load(false); } });
        selectionForm.addEventListener('submit', function (event) { if (!selected.size) { event.preventDefault(); error.textContent = 'Select at least one stream to review.'; error.hidden = false; } else { update(); error.hidden = true; } });
    }

    root.querySelectorAll('[data-sc-review-row]').forEach(function (row) {
        var modified = row.querySelector('[data-sc-review-modified]'), toggle = row.querySelector('[data-sc-review-toggle]'), name = row.querySelector('[name^="name_"]'), channel = row.querySelector('[data-sc-review-channel]'), channelInput = row.querySelector('[data-sc-review-channel-input]'), epg = row.querySelector('[data-sc-review-epg]'), epgInput = row.querySelector('[data-sc-review-epg-input]'), cats = row.querySelector('[data-sc-review-categories]'), catsPayload = row.querySelector('[data-sc-review-categories-payload]'), bouquets = row.querySelector('[data-sc-review-bouquets]'), bouquetsPayload = row.querySelector('[data-sc-review-bouquets-payload]');
        function mark() { modified.value = '1'; toggle.checked = true; }
        function values(select) { return Array.prototype.map.call(select.selectedOptions, function (option) { return Number(option.value); }); }
        [name, channelInput, epgInput, cats, bouquets].forEach(function (input) { input.addEventListener('change', function () { if (input === channelInput) channel.value = channelInput.value; if (input === epgInput) epg.value = epgInput.value; if (input === cats) catsPayload.value = JSON.stringify(values(cats)); if (input === bouquets) bouquetsPayload.value = JSON.stringify(values(bouquets)); mark(); }); });
        toggle.addEventListener('change', function () { modified.value = toggle.checked ? '1' : '0'; });
    });
}());
