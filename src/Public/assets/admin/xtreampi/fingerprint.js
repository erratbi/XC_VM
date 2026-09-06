(function () {
    'use strict';
    var root = document.querySelector('[data-sc-fingerprint]');
    if (!root) return;

    var streams = root.querySelector('[data-sc-fingerprint-streams]');
    var connections = root.querySelector('[data-sc-fingerprint-connections]');
    var activity = root.querySelector('[data-sc-fingerprint-activity]');
    var selected = null, start = 0, total = 0, size = 25, streamPending = 0, connectionPending = 0, timer;

    function clean(value) { return value == null || value === '' ? '—' : String(value).replace(/<[^>]*>/g, '').replace(/\s+/g, ' ').trim(); }
    function error(message) { var node = root.querySelector('[data-sc-fingerprint-error]'); node.textContent = message; node.hidden = false; }
    function state(target, message, span) { var row = document.createElement('tr'), cell = document.createElement('td'); target.replaceChildren(); cell.colSpan = span; cell.className = 'sc-table-state'; cell.textContent = message; row.appendChild(cell); target.appendChild(row); }
    function request(url) { return fetch(url, { credentials: 'same-origin', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }).then(function (response) { return response.json(); }); }

    function loadStreams() {
        var current = ++streamPending;
        var params = new URLSearchParams({ id: 'stream_unique', start: String(start), length: String(size), draw: '1', 'search[value]': document.getElementById('sc-fingerprint-search').value || '', category: document.getElementById('sc-fingerprint-category').value || '', 'order[0][column]': '0', 'order[0][dir]': 'asc' });
        state(streams, 'Loading…', 5);
        request('table?' + params.toString()).then(function (data) {
            if (current !== streamPending) return;
            var rows = Array.isArray(data.data) ? data.data : [];
            total = Number(data.recordsFiltered || data.recordsTotal || 0) || 0;
            streams.replaceChildren();
            if (!rows.length) state(streams, 'No matching streams.', 5);
            rows.forEach(function (value) {
                var row = document.createElement('tr');
                var id = String(value[0] == null ? '' : value[0]).match(/^\d+$/);
                [id ? id[0] : clean(value[0]), clean(value[1]), clean(value[2]), clean(value[3])].forEach(function (item) { var cell = document.createElement('td'); cell.textContent = item; row.appendChild(cell); });
                var cell = document.createElement('td'), button = document.createElement('button');
                button.type = 'button'; button.className = 'sc-row-action'; button.textContent = 'Select'; button.disabled = !id;
                button.addEventListener('click', function () { selected = id[0]; root.querySelector('[data-sc-fingerprint-selection]').textContent = 'Stream #' + selected; activity.hidden = false; loadConnections(); });
                cell.appendChild(button); row.appendChild(cell); streams.appendChild(row);
            });
            root.querySelector('[data-sc-fingerprint-range]').textContent = total ? 'Showing ' + (start + 1) + '–' + Math.min(start + rows.length, total) + ' of ' + total : 'Showing 0 of 0';
        }).catch(function () { if (current === streamPending) state(streams, 'The stream list could not be loaded.', 5); });
    }

    function loadConnections() {
        if (!selected) return;
        var current = ++connectionPending;
        state(connections, 'Loading…', 5);
        request('table?id=live_connections&stream_id=' + encodeURIComponent(selected) + '&fingerprint=true&start=0&length=100&draw=1').then(function (data) {
            if (current !== connectionPending) return;
            var rows = Array.isArray(data.data) ? data.data : [];
            connections.replaceChildren();
            if (!rows.length) state(connections, 'No active connections.', 5);
            rows.forEach(function (value) {
                var activityId = String(value[0] == null ? '' : value[0]);
                var row = document.createElement('tr');
                [value[2], value[3], value[7], value[8]].forEach(function (item) { var cell = document.createElement('td'); cell.textContent = clean(item); row.appendChild(cell); });
                var cell = document.createElement('td'), button = document.createElement('button');
                button.type = 'button'; button.className = 'sc-row-action'; button.textContent = 'Kill'; button.disabled = !/^(?:\d+|[a-f0-9]{32})$/i.test(activityId);
                button.addEventListener('click', function () {
                    if (!window.confirm('Kill this connection?')) return;
                    button.disabled = true;
                    request('api?action=line_activity&sub=kill&pid=' + encodeURIComponent(activityId)).then(function (result) {
                        if (result.result === true) loadConnections(); else { button.disabled = false; error('The connection could not be killed.'); }
                    }).catch(function () { button.disabled = false; error('The kill request failed.'); });
                });
                cell.appendChild(button); row.appendChild(cell); connections.appendChild(row);
            });
        }).catch(function () { if (current === connectionPending) state(connections, 'The activity table could not be loaded.', 5); });
    }

    root.querySelector('[data-sc-fingerprint-more]').addEventListener('click', function () { if (start + size < total) { start += size; loadStreams(); } });
    root.querySelectorAll('#sc-fingerprint-search,#sc-fingerprint-category').forEach(function (input) { input.addEventListener('input', function () { clearTimeout(timer); timer = setTimeout(function () { start = 0; loadStreams(); }, 250); }); });
    document.getElementById('sc-fingerprint-type').addEventListener('change', function () { document.querySelector('[data-sc-fingerprint-message]').hidden = this.value !== '3'; });
    root.querySelector('[data-sc-fingerprint-activate]').addEventListener('click', function () {
        if (!selected) return error('Select a stream first.');
        var sizeValue = Number(document.getElementById('sc-fingerprint-size').value), x = Number(document.getElementById('sc-fingerprint-x').value), y = Number(document.getElementById('sc-fingerprint-y').value), type = Number(document.getElementById('sc-fingerprint-type').value), message = document.getElementById('sc-fingerprint-custom').value;
        if (!(sizeValue > 0) || x < 0 || y < 0 || (type === 3 && !message.trim())) return error('Enter a valid size, position and message.');
        var payload = { id: Number(selected), font_size: sizeValue, font_color: document.getElementById('sc-fingerprint-colour').value, message: type === 3 ? message : '', type: type, xy_offset: x + 'x' + y }, button = this;
        button.disabled = true;
        request('api?action=fingerprint&data=' + encodeURIComponent(JSON.stringify(payload))).then(function (result) { button.disabled = false; if (result.result !== true && result.status !== 1) error('Fingerprint activation was rejected.'); else loadConnections(); }).catch(function () { button.disabled = false; error('Fingerprint activation failed.'); });
    });
    loadStreams();
}());
