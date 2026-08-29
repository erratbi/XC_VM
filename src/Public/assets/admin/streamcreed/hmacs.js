(function () {
	'use strict';
	var root = document.querySelector('[data-sc-hmacs]'); if (!root) return;
	var search = root.querySelector('[data-sc-hmac-search]'), filter = root.querySelector('[data-sc-hmac-filter]'), rows = Array.prototype.slice.call(root.querySelectorAll('[data-sc-hmac-row]')), empty = root.querySelector('[data-sc-hmac-empty]');
	if (!search || !filter || !empty) return;
	function update() { var query = search.value.trim().toLowerCase(), status = filter.value, visible = 0; rows.forEach(function (row) { var matches = (!query || (row.getAttribute('data-search') || '').indexOf(query) !== -1) && (status === 'all' || row.getAttribute('data-status') === status); row.hidden = !matches; if (matches) visible++; }); empty.hidden = visible > 0; empty.querySelector('td').textContent = (query || status !== 'all') ? 'No HMAC keys match these filters.' : 'No HMAC keys have been created yet.'; }
	search.addEventListener('input', update); filter.addEventListener('change', update);
}());
