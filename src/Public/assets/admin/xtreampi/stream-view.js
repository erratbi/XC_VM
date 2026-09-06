(function () {
    'use strict';
    var root = document.querySelector('[data-sc-stream-view]');
    if (!root) return;

    var streamId = root.getAttribute('data-stream-id') || '0';
    var streamType = Number(root.getAttribute('data-stream-type') || 1);
    var canEdit = root.getAttribute('data-can-edit') === '1';
    var body = root.querySelector('[data-sc-stream-servers]');
    var pollStatus = root.querySelector('[data-sc-stream-poll-status]');
    var error = root.querySelector('[data-sc-stream-error]');
    var sequence = 0, timer, active = true, loading = false;

    function clean(value) { return value == null || value === '' ? '—' : String(value).replace(/<[^>]*>/g, '').replace(/\s+/g, ' ').trim(); }
    function showError(message) { if (error) { error.textContent = message; error.hidden = false; } }
    function setState(message) { var row = document.createElement('tr'), cell = document.createElement('td'); body.replaceChildren(); cell.colSpan = 8; cell.className = 'sc-table-state'; cell.textContent = message; row.appendChild(cell); body.appendChild(row); }
    function actionType() { return streamType === 2 ? 'movie' : (streamType === 5 ? 'episode' : 'stream'); }
    function tableId() { return streamType === 2 ? 'movies' : (streamType === 4 ? 'radios' : (streamType === 5 ? 'episodes' : 'streams')); }
    function playerUrl() { var type = streamType === 1 || streamType === 3 || streamType === 4 ? 'live' : (streamType === 5 ? 'series' : 'movie'); var url = 'player?type=' + type + '&id=' + encodeURIComponent(streamId); if (streamType === 2 || streamType === 5) url += '&container=mp4'; return url; }
    function request(url) { return fetch(url, { credentials: 'same-origin', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }).then(function (response) { return response.json(); }); }

    function callAction(sub, serverId, button) {
        if (sub === 'delete' && !window.confirm('Delete this stream?')) return;
        if (sub === 'purge' && !window.confirm('Kill all connections on this server?')) return;
        button.disabled = true;
        request('api?action=' + actionType() + '&sub=' + encodeURIComponent(sub) + '&stream_id=' + encodeURIComponent(streamId) + '&server_id=' + encodeURIComponent(serverId)).then(function (data) {
            button.disabled = false;
            if (data.result === true) load(); else showError('The stream action was rejected.');
        }).catch(function () { button.disabled = false; showError('The stream action failed.'); });
    }

    function render(values) {
        body.replaceChildren();
        if (!values.length) { setState('No active server records.'); return; }
        values.forEach(function (item) {
            if (Array.isArray(item)) item = { server: item[3], connections: item[4], statusLabel: item[5], uptime: item[6] };
            var row = document.createElement('tr');
            ['server', 'connections', 'statusLabel', 'uptime', 'bitrate', 'video_codec', 'audio_codec'].forEach(function (field) { var cell = document.createElement('td'); cell.textContent = clean(item[field]); row.appendChild(cell); });
            var actions = document.createElement('td'), serverId = item.serverId == null ? '' : item.serverId, entries = [];
            if (item.can_play !== false) entries.push(['play', 'Play']);
            if (canEdit) {
                if (streamType === 2 || streamType === 5) { if (item.encode_action) entries.push([item.encode_action, item.encode_action === 'stop' ? 'Stop' : 'Start']); entries.push(['purge', 'Kill'], ['delete', 'Delete']); }
                else if (item.can_stop === false) entries.push(['start', 'Start']);
                else entries.push(['stop', 'Stop'], ['restart', 'Restart'], ['purge', 'Kill'], ['delete', 'Delete']);
            }
            entries.forEach(function (entry) {
                var button = document.createElement('button'); button.type = 'button'; button.className = 'sc-row-action'; button.textContent = entry[1];
                button.addEventListener('click', function () { if (entry[0] === 'play') window.open(playerUrl(), '_blank', 'noopener'); else callAction(entry[0], serverId, button); });
                actions.appendChild(button);
            });
            row.appendChild(actions); body.appendChild(row);
        });
    }

    function load() {
        if (loading) return;
        loading = true;
        var current = ++sequence;
        var params = new URLSearchParams({ id: tableId(), stream_id: streamId, single: 'true', view: 'xtreampi', draw: String(current), start: '0', length: '100', 'order[0][column]': '0', 'order[0][dir]': 'asc' });
        if (streamType === 3) params.set('created', 'true');
        request('table?' + params.toString()).then(function (data) {
            if (!active || current !== sequence) return;
            render(Array.isArray(data.data) ? data.data : []);
            if (pollStatus) pollStatus.textContent = 'Updated ' + new Date().toLocaleTimeString();
        }).catch(function () { if (active && current === sequence) { setState('Live status is unavailable.'); showError('The stream status request failed; retrying.'); } }).finally(function () { loading = false; });
    }

    root.querySelector('[data-sc-stream-play]').addEventListener('click', function () { window.open(playerUrl(), '_blank', 'noopener'); });
    root.querySelectorAll('[data-sc-source-row]').forEach(function (row) {
        var index = row.getAttribute('data-source-index'), result = row.querySelector('[data-sc-source-result]');
        row.querySelector('[data-sc-source-override]').addEventListener('click', function () { var button = this; button.disabled = true; request('api?action=stream&sub=force&stream_id=' + encodeURIComponent(streamId) + '&force_id=' + encodeURIComponent(index)).then(function (data) { button.disabled = false; if (data.result !== true) showError('The source override was rejected.'); else load(); }).catch(function () { button.disabled = false; showError('The source override failed.'); }); });
        row.querySelector('[data-sc-source-probe]').addEventListener('click', function () { var button = this; button.disabled = true; fetch('api?action=check_stream&stream=' + encodeURIComponent(streamId) + '&id=' + encodeURIComponent(index), { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } }).then(function (response) { return response.text(); }).then(function (value) { button.disabled = false; result.textContent = clean(value); }).catch(function () { button.disabled = false; result.textContent = 'Probe failed'; }); });
    });
    root.querySelector('[data-sc-stream-scan-all]')?.addEventListener('click', function () { root.querySelectorAll('[data-sc-source-probe]').forEach(function (button) { button.click(); }); });
    load();
    timer = window.setInterval(load, 5000);
    window.addEventListener('beforeunload', function () { active = false; window.clearInterval(timer); sequence += 1; });
}());
