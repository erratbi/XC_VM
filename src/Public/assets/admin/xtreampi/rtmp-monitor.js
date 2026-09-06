(function () {
    'use strict';

    var root = document.querySelector('[data-sc-rtmp-monitor]');
    if (!root) return;

    var search = root.querySelector('[data-sc-rtmp-search]');
    var server = root.querySelector('[data-sc-rtmp-server]');
    var entries = root.querySelector('[data-sc-rtmp-entries]');
    var rows = Array.prototype.slice.call(root.querySelectorAll('[data-sc-rtmp-row]'));
    var empty = root.querySelector('[data-sc-rtmp-empty]');
    var summary = root.querySelector('[data-sc-rtmp-summary]');
    var pageLabel = root.querySelector('[data-sc-rtmp-page]');
    var previous = root.querySelector('[data-sc-rtmp-previous]');
    var next = root.querySelector('[data-sc-rtmp-next]');
    var result = root.querySelector('[data-sc-rtmp-result]');
    var resultTitle = root.querySelector('[data-sc-rtmp-result-title]');
    var resultMessage = root.querySelector('[data-sc-rtmp-result-message]');
    var dialog = root.querySelector('[data-sc-rtmp-whois-dialog]');
    var dialogTitle = root.querySelector('[data-sc-rtmp-whois-title]');
    var dialogBody = root.querySelector('[data-sc-rtmp-whois-body]');
    var page = 1;
    var busy = false;

    function showResult(message, error) {
        result.hidden = false;
        result.className = 'sc-notice sc-rtmp-result ' + (error ? 'sc-notice-danger' : 'sc-notice-success');
        resultTitle.textContent = error ? 'RTMP action failed' : 'RTMP monitor';
        resultMessage.textContent = message;
    }

    function filteredRows() {
        var term = search.value.trim().toLowerCase();
        return rows.filter(function (row) { return row.textContent.toLowerCase().indexOf(term) !== -1; });
    }

    function render() {
        var filtered = filteredRows();
        var size = Number(entries.value) || 10;
        var pages = Math.max(1, Math.ceil(filtered.length / size));
        if (page > pages) page = pages;
        var start = (page - 1) * size;
        var end = start + size;
        rows.forEach(function (row) { var index = filtered.indexOf(row); row.hidden = index < start || index >= end; });
        empty.hidden = filtered.length !== 0;
        summary.textContent = filtered.length ? 'Showing ' + (start + 1) + '–' + Math.min(end, filtered.length) + ' of ' + filtered.length + ' live streams' : 'No live streams';
        pageLabel.textContent = 'Page ' + page + ' of ' + pages;
        previous.disabled = page === 1;
        next.disabled = page === pages;
    }

    function closeDialog() { if (dialog && dialog.open) dialog.close(); }

    function showWhois(ip) {
        if (!ip || !dialog) return;
        dialogTitle.textContent = 'IP details: ' + ip;
        dialogBody.textContent = 'Loading…';
        if (!dialog.open && typeof dialog.showModal === 'function') dialog.showModal();
        fetch('api?action=ip_whois&isp=1&ip=' + encodeURIComponent(ip), { credentials: 'same-origin', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }).then(function (response) {
            if (!response.ok) throw new Error('Request failed');
            return response.json();
        }).then(function (response) {
            var data = response && response.data;
            if (!data) throw new Error('No IP details found');
            var lines = [
                ['Continent', data.continent && data.continent.names && data.continent.names.en],
                ['Country', data.country && data.country.names && data.country.names.en],
                ['City', data.city && data.city.names && data.city.names.en],
                ['Postcode', data.postal && data.postal.code],
                ['Location', data.location && data.location.latitude !== undefined ? data.location.latitude + ', ' + data.location.longitude : ''],
                ['ISP', data.isp && data.isp.isp],
                ['Organisation', data.isp && data.isp.organization],
                ['ASN', data.isp && data.isp.autonomous_system_number !== undefined ? 'AS' + data.isp.autonomous_system_number + (data.isp.autonomous_system_organization ? ' — ' + data.isp.autonomous_system_organization : '') : ''],
                ['Timezone', data.location && data.location.time_zone],
                ['Local time', data.location && data.location.time]
            ].filter(function (line) { return line[1]; });
            dialogBody.replaceChildren();
            lines.forEach(function (line) {
                var item = document.createElement('p');
                var label = document.createElement('strong');
                label.textContent = line[0] + ': ';
                item.appendChild(label);
                item.appendChild(document.createTextNode(line[1]));
                dialogBody.appendChild(item);
            });
            if (!lines.length) dialogBody.textContent = 'No IP details are available.';
        }).catch(function () { dialogBody.textContent = 'IP details could not be loaded.'; });
    }

    function killStream(name, row, button) {
        if (busy || !name) return;
        busy = true;
        button.disabled = true;
        fetch('api?action=rtmp_kill&server=' + encodeURIComponent(root.getAttribute('data-server-id')) + '&name=' + encodeURIComponent(name), { credentials: 'same-origin', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }).then(function (response) {
            if (!response.ok) throw new Error('Request failed');
            return response.json();
        }).then(function (response) {
            if (!response || response.result !== true) throw new Error('Request rejected');
            rows = rows.filter(function (item) { return item !== row; });
            row.remove();
            page = 1;
            render();
            showResult('Stream has been killed. It may reconnect unless its authentication is revoked.', false);
        }).catch(function () { showResult('The stream could not be killed. Please try again.', true); }).finally(function () { busy = false; button.disabled = false; });
    }

    search.addEventListener('input', function () { page = 1; render(); });
    entries.addEventListener('change', function () { page = 1; render(); });
    previous.addEventListener('click', function () { if (page > 1) { page -= 1; render(); } });
    next.addEventListener('click', function () { page += 1; render(); });
    server.addEventListener('change', function () { var location = new URL(window.location.href); location.searchParams.set('server', server.value); window.location.assign(location.toString()); });
    root.querySelector('[data-sc-rtmp-refresh]').addEventListener('click', function () { window.location.reload(); });
    root.addEventListener('click', function (event) {
        var whois = event.target.closest('[data-sc-rtmp-whois]');
        if (whois) { showWhois(whois.getAttribute('data-sc-rtmp-whois')); return; }
        var kill = event.target.closest('[data-sc-rtmp-kill]');
        if (kill) killStream(kill.getAttribute('data-sc-rtmp-kill'), kill.closest('[data-sc-rtmp-row]'), kill);
    });
    root.querySelector('[data-sc-rtmp-whois-close]').addEventListener('click', closeDialog);
    if (dialog) dialog.addEventListener('click', function (event) { if (event.target === dialog) closeDialog(); });
    render();
}());
