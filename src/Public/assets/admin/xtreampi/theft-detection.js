(function () {
    'use strict';

    var root = document.querySelector('[data-sc-theft-detection]');
    if (!root) return;

    var search = root.querySelector('[data-sc-theft-search]');
    var range = root.querySelector('[data-sc-theft-range]');
    var entries = root.querySelector('[data-sc-theft-entries]');
    var rows = Array.prototype.slice.call(root.querySelectorAll('[data-sc-theft-row]'));
    var empty = root.querySelector('[data-sc-theft-empty]');
    var summary = root.querySelector('[data-sc-theft-range-summary]');
    var pageLabel = root.querySelector('[data-sc-theft-page]');
    var previous = root.querySelector('[data-sc-theft-previous]');
    var next = root.querySelector('[data-sc-theft-next]');
    var page = 1;

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
        rows.forEach(function (row) { row.hidden = filtered.indexOf(row) < start || filtered.indexOf(row) >= end; });
        empty.hidden = filtered.length !== 0;
        summary.textContent = filtered.length ? 'Showing ' + (start + 1) + '–' + Math.min(end, filtered.length) + ' of ' + filtered.length + ' detected accounts' : 'No detected accounts';
        pageLabel.textContent = 'Page ' + page + ' of ' + pages;
        previous.disabled = page === 1;
        next.disabled = page === pages;
    }

    search.addEventListener('input', function () { page = 1; render(); });
    entries.addEventListener('change', function () { page = 1; render(); });
    previous.addEventListener('click', function () { if (page > 1) { page -= 1; render(); } });
    next.addEventListener('click', function () { page += 1; render(); });
    range.addEventListener('change', function () {
        var location = new URL(window.location.href);
        location.searchParams.set('range', range.value);
        window.location.assign(location.toString());
    });
    render();
}());
