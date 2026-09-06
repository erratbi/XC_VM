(function () {
    'use strict';
    var root = document.querySelector('[data-sc-remote-table]');
    if (!root) return;
    var columns = (root.getAttribute('data-table-columns') || '').split(','), head = root.querySelector('[data-sc-remote-head]'), body = root.querySelector('[data-sc-remote-body]'), range = root.querySelector('[data-sc-remote-range]'), pageLabel = root.querySelector('[data-sc-remote-page]'), previous = root.querySelector('[data-sc-remote-previous]'), next = root.querySelector('[data-sc-remote-next]'), page = 1, total = 0, size = 25, timer;
    columns.forEach(function (label) { var cell = document.createElement('th'); cell.textContent = label.trim(); head.appendChild(cell); });
    function state(message) { body.replaceChildren(); var row = document.createElement('tr'), cell = document.createElement('td'); cell.className = 'sc-table-state'; cell.colSpan = columns.length; cell.textContent = message; row.appendChild(cell); body.appendChild(row); }
    function appendCell(row, value) {
        var cell = document.createElement('td');
        if (value == null || value === '') { cell.textContent = '—'; row.appendChild(cell); return; }
        var raw = String(value);
        var template = document.createElement('template');
        template.innerHTML = raw;
        var anchors = template.content.querySelectorAll('a[href]');
        if (anchors.length === 1 && template.content.childElementCount === 1) {
            var anchor = anchors[0];
            var href = anchor.getAttribute('href') || '';
            if (/^(?:\.\/)?(?:user|line|stream_view|serie|server_view|mag|enigma|hmac)\?id=\d+(?:&[A-Za-z0-9_=-]+)*$/.test(href)) {
                var link = document.createElement('a');
                link.href = href;
                link.textContent = anchor.textContent.trim() || href;
                cell.appendChild(link);
                row.appendChild(cell);
                return;
            }
        }
        cell.textContent = template.content.textContent.replace(/\s+/g, ' ').trim() || '—';
        row.appendChild(cell);
    }
    function load() {
        var params = new URLSearchParams({ draw: '1', id: root.getAttribute('data-table-id'), view: root.getAttribute('data-table-view') || '', start: String((page - 1) * size), length: String(size), 'search[value]': '', 'order[0][column]': '0', 'order[0][dir]': 'desc' });
        (root.getAttribute('data-table-static') || '').split('&').forEach(function (pair) { var bits = pair.split('='); if (bits[0] && bits[1]) params.set(bits[0], bits[1]); });
        var mappings = (root.getAttribute('data-table-filters') || '').split('&');
        mappings.forEach(function (mapping) { var parts = mapping.split('='); var input = document.getElementById(parts[1]); if (input && input.value) params.set(parts[0], input.value); if (parts[0] === 'search' && input) params.set('search[value]', input.value); });
        state('Loading…');
        fetch('table?' + params.toString(), { credentials: 'same-origin', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }).then(function (response) { return response.json(); }).then(function (data) {
            var items = Array.isArray(data.data) ? data.data : []; total = parseInt(data.recordsFiltered || data.recordsTotal || 0, 10) || 0; body.replaceChildren();
            if (!items.length) { state('No matching records.'); }
            items.forEach(function (item) { var row = document.createElement('tr'); var tableId = root.getAttribute('data-table-id'); var fields = (root.getAttribute('data-table-fields') || '').split(','); var values = Array.isArray(item) ? item : fields.map(function (field) { return item[field]; }); var visibleCount = tableId === 'asns' ? 7 : ((tableId === 'restream_logs' || tableId === 'mag_events') ? (values.length - 1) : values.length); values.slice(0, visibleCount).forEach(function (value) { appendCell(row, value); });
                if (root.getAttribute('data-table-id') === 'asns' && item[7] != null) { var actions = document.createElement('td'); var match = String(item[7]).match(/api\((\d+),\s*['"](allow|block)['"]/); if (match) { var button = document.createElement('button'); button.type = 'button'; button.className = 'sc-row-action'; button.textContent = match[2] === 'allow' ? 'Allow' : 'Block'; button.addEventListener('click', function () { fetch('api?action=asn&sub=' + match[2] + '&id=' + encodeURIComponent(match[1]), { credentials: 'same-origin', headers: { Accept: 'application/json' } }).then(load); }); actions.appendChild(button); } row.appendChild(actions); }
                if (root.getAttribute('data-table-id') === 'restream_logs' && item[3]) { var action = document.createElement('td'); var button = document.createElement('button'); button.type = 'button'; button.className = 'sc-row-action'; button.textContent = 'Block IP'; button.addEventListener('click', function () { if (window.confirm('Block this IP address?')) fetch('api?action=mysql_syslog&sub=block&ip=' + encodeURIComponent(String(item[3])), { credentials: 'same-origin', headers: { Accept: 'application/json' } }).then(load); }); action.appendChild(button); row.appendChild(action); }
                if (tableId === 'mag_events' && item[4] != null) { var eventAction = document.createElement('td'); var deleteMatch = String(item[4]).match(/api\((\d+),\s*['"]delete['"]/); if (deleteMatch) { var deleteButton = document.createElement('button'); deleteButton.type = 'button'; deleteButton.className = 'sc-row-action'; deleteButton.textContent = 'Delete'; deleteButton.addEventListener('click', function () { if (window.confirm('Delete this event?')) fetch('api?action=mag_event&sub=delete&mag_id=' + encodeURIComponent(deleteMatch[1]), { credentials: 'same-origin', headers: { Accept: 'application/json' } }).then(load); }); eventAction.appendChild(deleteButton); } row.appendChild(eventAction); }
                if (tableId === 'streams' && (root.getAttribute('data-table-static') || '').indexOf('created=true') !== -1 && item.id != null) {
                    var channelAction = document.createElement('td');
                    var serverId = item.serverId == null ? -1 : item.serverId;
                    var requestChannelAction = function (sub, confirmMessage, button) {
                        if (confirmMessage && !window.confirm(confirmMessage)) return;
                        button.disabled = true;
                        fetch('api?action=stream&sub=' + encodeURIComponent(sub) + '&stream_id=' + encodeURIComponent(item.id) + '&server_id=' + encodeURIComponent(serverId), { credentials: 'same-origin', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }).then(function (response) { return response.json(); }).then(function (data) { if (data.result === true) load(); else { button.disabled = false; window.alert('The channel action was rejected.'); } }).catch(function () { button.disabled = false; window.alert('The channel action failed.'); });
                    };
                    var edit = document.createElement('a'); edit.className = 'sc-row-action'; edit.href = 'created_channel?id=' + encodeURIComponent(item.id); edit.textContent = 'Edit'; channelAction.appendChild(edit);
                    var lifecycle = item.can_stop ? [['stop', 'Stop'], ['restart', 'Restart'], ['purge', 'Kill connections']] : [['start', 'Start']];
                    lifecycle.forEach(function (entry) { var actionButton = document.createElement('button'); actionButton.type = 'button'; actionButton.className = 'sc-row-action'; actionButton.textContent = entry[1]; actionButton.addEventListener('click', function () { requestChannelAction(entry[0], entry[0] === 'purge' ? 'Kill all channel connections?' : '', actionButton); }); channelAction.appendChild(actionButton); });
                    var remove = document.createElement('button'); remove.type = 'button'; remove.className = 'sc-row-action'; remove.textContent = 'Delete'; remove.addEventListener('click', function () { requestChannelAction('delete', 'Delete this channel?', remove); }); channelAction.appendChild(remove); row.appendChild(channelAction);
                }
                body.appendChild(row);
            });
            var pages = Math.max(1, Math.ceil(total / size)); range.textContent = total ? 'Showing ' + ((page - 1) * size + 1) + '–' + Math.min(page * size, total) + ' of ' + total : 'Showing 0 of 0'; pageLabel.textContent = 'Page ' + page + ' of ' + pages; previous.disabled = page <= 1; next.disabled = page >= pages;
        }).catch(function () { state('The table could not be loaded.'); });
    }
    root.querySelectorAll('input,select').forEach(function (input) { input.addEventListener('input', function () { clearTimeout(timer); timer = setTimeout(function () { page = 1; load(); }, 250); }); input.addEventListener('change', function () { page = 1; load(); }); });
    root.querySelectorAll('[data-asn-bulk]').forEach(function (button) { button.addEventListener('click', function () { if (!window.confirm('Apply this ASN policy to all matching records?')) return; fetch('api?action=asn&sub=' + encodeURIComponent(button.getAttribute('data-asn-bulk')), { credentials: 'same-origin', headers: { Accept: 'application/json' } }).then(load); }); });
    previous.addEventListener('click', function () { if (page > 1) { page--; load(); } }); next.addEventListener('click', function () { if (page < Math.ceil(total / size)) { page++; load(); } }); load();
}());
