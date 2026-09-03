(function () {
    'use strict';

    var root = document.querySelector('[data-sc-streams]');
    if (!root) return;

    var q = root.querySelector('[data-search]'),
        category = root.querySelector('[data-category]'),
        filter = root.querySelector('[data-filter]'),
        entries = root.querySelector('[data-entries]'),
        rows = root.querySelector('[data-rows]'),
        range = root.querySelector('[data-range]'),
        pageLabel = root.querySelector('[data-page]'),
        prev = root.querySelector('[data-prev]'),
        next = root.querySelector('[data-next]'),
        page = 1,
        total = 0,
        timer,
        canEdit = root.dataset.canEdit === '1';

    function el(tag, cls, text) {
        var n = document.createElement(tag);
        if (cls) n.className = cls;
        if (text !== undefined) n.textContent = text;
        return n;
    }

    function state(text) {
        rows.replaceChildren();
        var tr = el('tr'),
            td = el('td', 'sc-table-state', text);
        td.colSpan = 7;
        tr.append(td);
        rows.append(tr);
    }

    function cell(tr, text, cls) {
        tr.append(el('td', cls || '', text));
    }

    function stripHtml(html) {
        if (!html) return '';
        if (typeof html !== 'string') return String(html);
        if (html.indexOf('<') === -1) return html.trim();
        var tmp = document.createElement('div');
        tmp.innerHTML = html;
        return (tmp.textContent || tmp.innerText || '').trim();
    }

    var STATUS_LABELS = {
        '-1': 'No servers',
        '0': 'Stopped',
        '1': 'Online',
        '2': 'Starting',
        '3': 'Down',
        '4': 'On demand',
        '5': 'Direct source',
        '6': 'Creating…',
        '7': 'Direct stream'
    };

    function resolveStatusLabel(item) {
        var code = String(item.status);
        if (STATUS_LABELS[code]) {
            return STATUS_LABELS[code];
        }
        var raw = stripHtml(item.statusLabel);
        return raw || 'Unknown';
    }

    function statusClass(status) {
        var n = Number(status);
        if (n === 1) return 'is-active';
        if (n === 2) return 'is-warning';
        if (n === 3) return 'is-banned';
        if (n === 4 || n === 5 || n === 6 || n === 7) return 'is-neutral';
        return 'is-disabled';
    }

    function resolveUptime(item, statusLabel) {
        var uptime = stripHtml(item.uptime);
        if (!uptime || uptime === statusLabel || uptime.toUpperCase() === 'STOPPED' || Number(item.status) !== 1) {
            return '—';
        }
        return uptime;
    }

    function row(item) {
        var tr = el('tr'),
            td = el('td'),
            identity = el('div', 'sc-table-identity'),
            streamName = stripHtml(item.name) || 'Untitled stream',
            title = el('a', '', streamName);

        title.href = 'stream_view?id=' + encodeURIComponent(item.id);
        var meta = '#' + item.id;
        if (item.category) {
            meta += ' • ' + stripHtml(item.category);
        }
        identity.append(title, el('small', '', meta));
        td.append(identity);
        tr.append(td);

        var serverName = stripHtml(item.server) || '—';
        cell(tr, serverName, 'sc-table-secondary');

        var label = resolveStatusLabel(item);
        td = el('td');
        td.append(el('span', 'sc-row-status ' + statusClass(item.status), label));
        tr.append(td);

        cell(tr, String(item.connections || 0));
        cell(tr, resolveUptime(item, label), 'sc-table-secondary');
        cell(tr, item.bitrate ? item.bitrate + ' Kbps' : '—', 'sc-table-secondary');

        td = el('td', 'sc-table-actions');
        if (canEdit) {
            var edit = el('a', 'sc-row-action', 'Edit');
            edit.href = 'stream?id=' + encodeURIComponent(item.id);
            td.append(edit);
        }
        tr.append(td);
        return tr;
    }

    function load() {
        var size = Number(entries.value) || 25,
            params = new URLSearchParams({
                draw: '1',
                id: 'streams',
                view: 'streamcreed',
                start: String((page - 1) * size),
                length: String(size),
                'search[value]': q.value.trim(),
                category: category.value,
                filter: filter.value,
                'order[0][column]': '0',
                'order[0][dir]': 'desc'
            });

        state('Loading streams…');
        fetch('table?' + params, {
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(function (r) {
                if (!r.ok) throw new Error();
                return r.json();
            })
            .then(function (data) {
                var items = Array.isArray(data.data) ? data.data : [];
                total = Number(data.recordsFiltered) || 0;
                rows.replaceChildren();
                if (items.length) {
                    items.forEach(function (item) {
                        rows.append(row(item));
                    });
                } else {
                    state('No live streams match these filters.');
                }
                var pages = Math.max(1, Math.ceil(total / size)),
                    first = total ? (page - 1) * size + 1 : 0;
                range.textContent = 'Showing ' + first + '–' + Math.min(page * size, total) + ' of ' + total;
                pageLabel.textContent = 'Page ' + page + ' of ' + pages;
                prev.disabled = page === 1;
                next.disabled = page >= pages;
            })
            .catch(function () {
                total = 0;
                range.textContent = '';
                pageLabel.textContent = 'Page 1';
                prev.disabled = true;
                next.disabled = true;
                state('Live streams could not be loaded.');
            });
    }

    q.oninput = function () {
        clearTimeout(timer);
        timer = setTimeout(function () {
            page = 1;
            load();
        }, 300);
    };

    category.onchange = filter.onchange = entries.onchange = function () {
        page = 1;
        load();
    };

    prev.onclick = function () {
        if (page > 1) {
            page--;
            load();
        }
    };

    next.onclick = function () {
        if (page * Number(entries.value) < total) {
            page++;
            load();
        }
    };

    load();
})();
