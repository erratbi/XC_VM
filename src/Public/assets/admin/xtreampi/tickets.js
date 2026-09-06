(function () {
	'use strict';
	var root = document.querySelector('[data-sc-tickets]'); if (!root) return;
	var search = root.querySelector('[data-sc-ticket-search]'), filter = root.querySelector('[data-sc-ticket-filter]'), rows = Array.prototype.slice.call(root.querySelectorAll('[data-sc-ticket-row]')), empty = root.querySelector('[data-sc-ticket-empty]');
	if (!search || !filter || !empty) return;
	function update() { var query = search.value.trim().toLowerCase(), status = filter.value, visible = 0; rows.forEach(function (row) { var matches = (!query || (row.getAttribute('data-search') || '').indexOf(query) !== -1) && (status === 'all' || row.getAttribute('data-status') === status); row.hidden = !matches; if (matches) visible++; }); empty.hidden = visible > 0; empty.querySelector('td').textContent = (query || status !== 'all') ? 'No tickets match these filters.' : 'No support tickets have been created yet.'; }
	search.addEventListener('input', update); filter.addEventListener('change', update);
}());
