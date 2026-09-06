(function () {
    'use strict';

    var root = document.querySelector('[data-sc-mysql-syslog]');
    if (!root) return;

    var search = root.querySelector('[data-sc-mysql-search]');
    var entries = root.querySelector('[data-sc-mysql-entries]');
    var orderColumn = root.querySelector('[data-sc-mysql-order-column]');
    var orderDirection = root.querySelector('[data-sc-mysql-order-dir]');
    var body = root.querySelector('[data-sc-mysql-body]');
    var range = root.querySelector('[data-sc-mysql-range]');
    var pageLabel = root.querySelector('[data-sc-mysql-page]');
    var previous = root.querySelector('[data-sc-mysql-previous]');
    var next = root.querySelector('[data-sc-mysql-next]');
    var exportButton = root.querySelector('[data-sc-mysql-export]');
    var error = root.querySelector('[data-sc-mysql-error]');
    var canBlock = root.dataset.canBlock === '1';
    var page = 1;
    var total = 0;
    var requestToken = 0;
    var searchTimer;
    var whoisDialog;
    var whoisBody;
    var whoisTitle;

    function pageSize() {
        return parseInt(entries && entries.value, 10) || 25;
    }

    function setError(message) {
        if (!error) return;
        error.textContent = message;
        error.hidden = false;
    }

    function clearError() {
        if (error) error.hidden = true;
    }

    function showState(message, isError) {
        if (!body) return;
        body.replaceChildren();
        var row = document.createElement('tr');
        var cell = document.createElement('td');
        cell.className = 'sc-table-state' + (isError ? ' is-error' : '');
        cell.colSpan = 6;
        cell.textContent = message;
        if (isError) {
            var retry = document.createElement('button');
            retry.className = 'sc-row-action';
            retry.type = 'button';
            retry.setAttribute('data-sc-mysql-retry', '');
            retry.textContent = 'Retry';
            cell.appendChild(document.createTextNode(' '));
            cell.appendChild(retry);
        }
        row.appendChild(cell);
        body.appendChild(row);
    }

    function buildQuery() {
        var size = pageSize();
        return new URLSearchParams({
            draw: String(requestToken),
            id: 'mysql_syslog',
            view: 'xtreampi',
            start: String((page - 1) * size),
            length: String(size),
            'search[value]': search ? search.value.trim() : '',
            'order[0][column]': orderColumn ? orderColumn.value : '0',
            'order[0][dir]': orderDirection ? orderDirection.value : 'desc'
        });
    }

    function buildExportPayload() {
        return {
            draw: 1,
            id: 'mysql_syslog',
            view: 'xtreampi',
            start: (page - 1) * pageSize(),
            length: pageSize(),
            search: { value: search ? search.value.trim() : '', regex: false },
            order: [{ column: orderColumn ? orderColumn.value : '0', dir: orderDirection ? orderDirection.value : 'desc' }]
        };
    }

    function parsedMarkup(value) {
        var parser = new DOMParser();
        return parser.parseFromString(value == null ? '' : String(value), 'text/html').body;
    }

    function textFromNode(node) {
        var clone = node.cloneNode(true);
        Array.prototype.forEach.call(clone.querySelectorAll('br'), function (breakNode) {
            breakNode.parentNode.replaceChild(document.createTextNode('\n'), breakNode);
        });
        return clone.textContent || '';
    }

    function textFromMarkup(value) {
        return textFromNode(parsedMarkup(value));
    }

    function addTextCell(row, value, preserveBreaks) {
        var cell = document.createElement('td');
        cell.textContent = preserveBreaks ? textFromMarkup(value) : textFromMarkup(value).replace(/\s+/g, ' ').trim();
        if (preserveBreaks) cell.style.whiteSpace = 'pre-wrap';
        row.appendChild(cell);
        return cell;
    }

    function addServerCell(row, value) {
        var cell = document.createElement('td');
        var source = parsedMarkup(value);
        var link = source.querySelector('a[href]');
        var href = link && link.getAttribute('href');
        if (link && href && /^(?:\.\/)?server_view\?id=\d+$/.test(href)) {
            var anchor = document.createElement('a');
            anchor.href = href;
            anchor.textContent = textFromNode(link).trim();
            cell.appendChild(anchor);
        } else {
            cell.textContent = textFromNode(source).replace(/\s+/g, ' ').trim();
        }
        row.appendChild(cell);
    }

    function addIpCell(row, value) {
        var cell = document.createElement('td');
        var source = parsedMarkup(value);
        var link = source.querySelector('a');
        var onclick = link && (link.getAttribute('onclick') || link.getAttribute('onClick'));
        var match = onclick && onclick.match(/whois\s*\(\s*['"]([^'"]+)['"]\s*\)/i);
        if (link && match) {
            var button = document.createElement('button');
            button.className = 'sc-row-action';
            button.type = 'button';
            button.setAttribute('data-sc-mysql-whois', match[1]);
            button.textContent = textFromNode(link).trim() || match[1];
            cell.appendChild(button);
        } else {
            cell.textContent = textFromNode(source).replace(/\s+/g, ' ').trim() || '—';
        }
        row.appendChild(cell);
    }

    function addActionCell(row, value) {
        var cell = document.createElement('td');
        var source = parsedMarkup(value);
        var sourceButton = source.querySelector('button');
        var onclick = sourceButton && (sourceButton.getAttribute('onclick') || sourceButton.getAttribute('onClick'));
        var match = onclick && onclick.match(/api\s*\(\s*['"]([^'"]+)['"]\s*,\s*['"]block['"]\s*\)/i);
        var button = document.createElement('button');
        button.className = 'sc-row-action' + (match ? '' : ' is-danger');
        button.type = 'button';
        if (match && canBlock) {
            button.setAttribute('data-sc-mysql-block', match[1]);
            button.textContent = 'Block';
        } else {
            button.disabled = true;
            button.textContent = match && !canBlock ? 'Restricted' : 'Blocked';
        }
        cell.appendChild(button);
        row.appendChild(cell);
    }

    function renderRows(items) {
        if (!body) return;
        body.replaceChildren();
        if (!items.length) {
            showState(search && search.value.trim() ? 'No system logs match this search.' : 'No system logs recorded yet.');
            return;
        }
        items.forEach(function (item) {
            var row = document.createElement('tr');
            var values = Array.isArray(item) ? item : [];
            addTextCell(row, values[0], false);
            addServerCell(row, values[1]);
            addTextCell(row, values[2], false);
            addTextCell(row, values[3], true);
            addIpCell(row, values[4]);
            addActionCell(row, values[5]);
            body.appendChild(row);
        });
    }

    function updatePager() {
        var size = pageSize();
        var pages = Math.max(1, Math.ceil(total / size));
        if (range) {
            range.textContent = total ? 'Showing ' + ((page - 1) * size + 1) + '–' + Math.min(page * size, total) + ' of ' + total : 'Showing 0 of 0';
        }
        if (pageLabel) pageLabel.textContent = 'Page ' + page + ' of ' + pages;
        if (previous) previous.disabled = page <= 1;
        if (next) next.disabled = page >= pages;
    }

    function load() {
        var token = ++requestToken;
        clearError();
        showState('Loading system logs…');
        fetch('table?' + buildQuery().toString(), {
            credentials: 'same-origin',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (response) {
            if (!response.ok) throw new Error('HTTP ' + response.status);
            return response.json();
        }).then(function (data) {
            if (token !== requestToken) return;
            if (!data || !Array.isArray(data.data)) throw new Error('Invalid system log response.');
            total = parseInt(data.recordsFiltered != null ? data.recordsFiltered : data.recordsTotal, 10) || 0;
            var pages = Math.max(1, Math.ceil(total / pageSize()));
            if (page > pages) {
                page = pages;
                load();
                return;
            }
            renderRows(data.data);
            updatePager();
        }).catch(function () {
            if (token !== requestToken) return;
            total = 0;
            updatePager();
            showState('System logs could not be loaded.', true);
            setError('The system log service did not return a valid response.');
        });
    }

    function dialogValue(value) {
        return value === null || value === undefined || value === '' ? '—' : String(value);
    }

    function lookup(source, path) {
        return path.split('.').reduce(function (value, key) {
            return value && typeof value === 'object' ? value[key] : undefined;
        }, source);
    }

    function ensureWhoisDialog() {
        if (whoisDialog) return;
        whoisDialog = document.createElement('dialog');
        whoisDialog.className = 'sc-dialog sc-whois-dialog';
        var heading = document.createElement('div');
        heading.className = 'sc-dialog-heading';
        whoisTitle = document.createElement('h2');
        heading.appendChild(whoisTitle);
        var close = document.createElement('button');
        close.className = 'sc-dialog-close';
        close.type = 'button';
        close.setAttribute('aria-label', 'Close');
        close.textContent = '×';
        close.addEventListener('click', function () {
            if (typeof whoisDialog.close === 'function') whoisDialog.close();
        });
        heading.appendChild(close);
        whoisBody = document.createElement('div');
        whoisBody.className = 'sc-dialog-body';
        whoisDialog.appendChild(heading);
        whoisDialog.appendChild(whoisBody);
        document.body.appendChild(whoisDialog);
    }

    function showWhoisMessage(message) {
        ensureWhoisDialog();
        whoisBody.replaceChildren();
        var paragraph = document.createElement('p');
        paragraph.textContent = message;
        whoisBody.appendChild(paragraph);
        if (typeof whoisDialog.showModal === 'function') {
            if (!whoisDialog.open) whoisDialog.showModal();
        } else {
            window.alert(message);
        }
    }

    function showWhois(ip, button) {
        if (button) button.disabled = true;
        fetch('api?action=ip_whois&isp=1&ip=' + encodeURIComponent(ip), {
            credentials: 'same-origin',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (response) {
            if (!response.ok) throw new Error('HTTP ' + response.status);
            return response.json();
        }).then(function (payload) {
            var data = payload && payload.data && typeof payload.data === 'object' ? payload.data : payload;
            if (!data || typeof data !== 'object') throw new Error('Invalid WHOIS response.');
            ensureWhoisDialog();
            whoisTitle.textContent = 'IP details: ' + ip;
            whoisBody.replaceChildren();
            var rows = [
                ['Continent', lookup(data, 'continent.names.en')],
                ['Country', lookup(data, 'country.names.en')],
                ['City', lookup(data, 'city.names.en')],
                ['Postal code', lookup(data, 'postal.code')],
                ['Coordinates', lookup(data, 'location.latitude') !== undefined && lookup(data, 'location.longitude') !== undefined ? lookup(data, 'location.latitude') + ', ' + lookup(data, 'location.longitude') : undefined],
                ['ISP', lookup(data, 'isp.isp')],
                ['Organization', lookup(data, 'isp.organization')],
                ['ASN', lookup(data, 'isp.autonomous_system_number') ? 'AS' + lookup(data, 'isp.autonomous_system_number') + (lookup(data, 'isp.autonomous_system_organization') ? ' — ' + lookup(data, 'isp.autonomous_system_organization') : '') : undefined],
                ['Classification', lookup(data, 'type')],
                ['Time zone', lookup(data, 'location.time_zone')],
                ['Local time', lookup(data, 'location.time')]
            ];
            rows.forEach(function (entry) {
                var line = document.createElement('div');
                var label = document.createElement('strong');
                label.textContent = entry[0];
                var value = document.createElement('span');
                value.textContent = dialogValue(entry[1]);
                line.appendChild(label);
                line.appendChild(value);
                whoisBody.appendChild(line);
            });
            if (typeof whoisDialog.showModal === 'function') {
                if (!whoisDialog.open) whoisDialog.showModal();
            } else {
                window.alert('WHOIS details loaded for ' + ip + '.');
            }
        }).catch(function () {
            showWhoisMessage('WHOIS information could not be loaded.');
        }).then(function () {
            if (button) button.disabled = false;
        });
    }

    if (search) search.addEventListener('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () { page = 1; load(); }, 300);
    });
    if (entries) entries.addEventListener('change', function () { page = 1; load(); });
    if (orderColumn) orderColumn.addEventListener('change', function () { page = 1; load(); });
    if (orderDirection) orderDirection.addEventListener('change', function () { page = 1; load(); });
    if (previous) previous.addEventListener('click', function () { if (page > 1) { page -= 1; load(); } });
    if (next) next.addEventListener('click', function () { if (page < Math.ceil(total / pageSize())) { page += 1; load(); } });
    if (exportButton) exportButton.addEventListener('click', function () {
        window.location.href = 'api?action=report&params=' + encodeURIComponent(JSON.stringify(buildExportPayload()));
    });
    if (body) body.addEventListener('click', function (event) {
        var retry = event.target.closest('[data-sc-mysql-retry]');
        if (retry) {
            load();
            return;
        }
        var block = event.target.closest('[data-sc-mysql-block]');
        if (block) {
            var ip = block.getAttribute('data-sc-mysql-block');
            if (!ip || !window.confirm('Block this IP address?')) return;
            block.disabled = true;
            clearError();
            fetch('api?action=mysql_syslog&sub=block&ip=' + encodeURIComponent(ip), {
                credentials: 'same-origin',
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function (response) {
                if (!response.ok) throw new Error('HTTP ' + response.status);
                return response.json();
            }).then(function (data) {
                if (!data || data.result !== true) throw new Error('The blocklist service rejected the request.');
                load();
            }).catch(function () {
                block.disabled = false;
                setError('The IP address could not be blocked.');
            });
            return;
        }
        var whois = event.target.closest('[data-sc-mysql-whois]');
        if (whois) showWhois(whois.getAttribute('data-sc-mysql-whois'), whois);
    });

    load();
}());
