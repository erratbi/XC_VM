(function () {
    'use strict';
    var root = document.querySelector('[data-sc-ondemand]');
    if (!root) return;
    var body = root.querySelector('[data-sc-ondemand-body]'), range = root.querySelector('[data-sc-ondemand-range]'), pageLabel = root.querySelector('[data-sc-ondemand-page]');
    var page = 1, total = 0, request = 0, timer;
    function text(value) { return value == null || value === '' ? '—' : String(value).replace(/\s+/g, ' ').trim(); }
    function state(message) { body.replaceChildren(); var row = document.createElement('tr'), cell = document.createElement('td'); cell.colSpan = 8; cell.className = 'sc-table-state'; cell.textContent = message; row.appendChild(cell); body.appendChild(row); }
    function streamLink(value) { var id = String(value == null ? '' : value).match(/\d+/); if (!id) return null; var link = document.createElement('a'); link.href = 'stream_view?id=' + encodeURIComponent(id[0]); link.textContent = text(value); return link; }
    function load() {
        var current = ++request, params = new URLSearchParams({ id: 'ondemand', draw: '1', start: String((page - 1) * Number(document.getElementById('sc-ondemand-size').value || 25)), length: document.getElementById('sc-ondemand-size').value || '25', 'search[value]': document.getElementById('sc-ondemand-search').value || '', 'order[0][column]': '0', 'order[0][dir]': 'desc' });
        ['server', 'category', 'filter'].forEach(function (key) { var input = document.getElementById('sc-ondemand-' + key); if (input.value) params.set(key, input.value); });
        state('Loading…');
        fetch('table?' + params.toString(), { credentials: 'same-origin', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }).then(function (response) { return response.json(); }).then(function (data) {
            if (current !== request) return;
            var rows = Array.isArray(data.data) ? data.data : []; total = Number(data.recordsFiltered || data.recordsTotal || 0) || 0; body.replaceChildren();
            if (!rows.length) state('No matching streams.');
            rows.forEach(function (values) { var row = document.createElement('tr'); for (var i = 0; i < 8; i += 1) { var cell = document.createElement('td'), value = values[i]; if (i === 0) { var link = streamLink(value); if (link) cell.appendChild(link); else cell.textContent = text(value); } else cell.textContent = text(value); row.appendChild(cell); } body.appendChild(row); });
            var size = Number(document.getElementById('sc-ondemand-size').value || 25), pages = Math.max(1, Math.ceil(total / size)); range.textContent = total ? 'Showing ' + ((page - 1) * size + 1) + '–' + Math.min(page * size, total) + ' of ' + total : 'Showing 0 of 0'; pageLabel.textContent = 'Page ' + page + ' of ' + pages; root.querySelector('[data-sc-ondemand-prev]').disabled = page <= 1; root.querySelector('[data-sc-ondemand-next]').disabled = page >= pages;
        }).catch(function () { if (current === request) state('The scanner table could not be loaded.'); });
    }
    root.querySelectorAll('input,select').forEach(function (input) { input.addEventListener('input', function () { clearTimeout(timer); timer = setTimeout(function () { page = 1; load(); }, 250); }); input.addEventListener('change', function () { page = 1; load(); }); });
    root.querySelector('[data-sc-ondemand-prev]').addEventListener('click', function () { if (page > 1) { page -= 1; load(); } }); root.querySelector('[data-sc-ondemand-next]').addEventListener('click', function () { if (page < Math.ceil(total / Number(document.getElementById('sc-ondemand-size').value || 25))) { page += 1; load(); } }); load();
}());
