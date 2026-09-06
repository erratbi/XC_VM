(function () {
    'use strict';

    var root = document.querySelector('[data-sc-blocked-ips]');
    if (!root) return;

    var body = root.querySelector('[data-sc-blocked-ip-rows]');
    var search = root.querySelector('[data-sc-ip-search]');
    var status = root.querySelector('[data-sc-status]');
    var error = root.querySelector('[data-sc-error]');
    var flush = root.querySelector('[data-sc-flush]');

    function showError(message) {
        if (!error) return;
        error.textContent = message;
        error.hidden = false;
    }

    function showStatus(message) {
        if (!status) return;
        status.textContent = message;
        status.hidden = false;
    }

    function updateEmptyState() {
        if (!body) return;
        var rows = body.querySelectorAll('[data-sc-blocked-ip-row]');
        var empty = body.querySelector('[data-sc-blocked-ip-empty]');
        if (!rows.length && !empty) {
            empty = document.createElement('tr');
            empty.setAttribute('data-sc-blocked-ip-empty', '');
            var cell = document.createElement('td');
            cell.className = 'sc-table-state';
            cell.colSpan = 5;
            cell.textContent = 'No blocked IP addresses.';
            empty.appendChild(cell);
            body.appendChild(empty);
        } else if (rows.length && empty) {
            empty.remove();
        }
        updateFilterState();
    }

    function updateFilterState() {
        if (!body) return;
        var query = search ? search.value.trim().toLowerCase() : '';
        var rows = body.querySelectorAll('[data-sc-blocked-ip-row]');
        var visible = 0;
        Array.prototype.forEach.call(rows, function (row) {
            var searchable = Array.prototype.map.call(row.querySelectorAll('td'), function (cell) { return cell.textContent; }).slice(0, 4).join(' ');
            var matches = !query || searchable.toLowerCase().indexOf(query) !== -1;
            row.hidden = !matches;
            if (matches) visible += 1;
        });
        var filteredEmpty = body.querySelector('[data-sc-blocked-ip-filter-empty]');
        if (query && rows.length && !visible && !filteredEmpty) {
            filteredEmpty = document.createElement('tr');
            filteredEmpty.setAttribute('data-sc-blocked-ip-filter-empty', '');
            var cell = document.createElement('td');
            cell.className = 'sc-table-state';
            cell.colSpan = 5;
            cell.textContent = 'No blocked IP addresses match this search.';
            filteredEmpty.appendChild(cell);
            body.appendChild(filteredEmpty);
        } else if ((!query || visible) && filteredEmpty) {
            filteredEmpty.remove();
        }
    }

    if (flush) {
        flush.addEventListener('click', function (event) {
            if (!window.confirm('Flush every blocked IP address?')) event.preventDefault();
        });
    }

    if (search) search.addEventListener('input', updateFilterState);

    if (!body) return;
    body.addEventListener('click', function (event) {
        var button = event.target.closest('[data-sc-ip-delete]');
        if (!button) return;

        var rowId = button.getAttribute('data-ip-id');
        if (!rowId || !window.confirm('Remove this blocked IP address?')) return;

        button.disabled = true;
        if (error) error.hidden = true;
        fetch('api?action=ip&sub=delete&ip=' + encodeURIComponent(rowId), {
            credentials: 'same-origin',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (response) {
            if (!response.ok) throw new Error('HTTP ' + response.status);
            return response.json();
        }).then(function (data) {
            if (!data || data.result !== true) throw new Error('The blocklist service rejected the request.');
            var row = button.closest('[data-sc-blocked-ip-row]');
            if (row) row.remove();
            updateEmptyState();
            showStatus('The blocked IP address was removed.');
        }).catch(function () {
            button.disabled = false;
            showError('The blocked IP address could not be removed.');
        });
    });
}());
