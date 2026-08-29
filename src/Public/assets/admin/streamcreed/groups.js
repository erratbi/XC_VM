(function () {
	'use strict';
	var root = document.querySelector('[data-sc-groups]');
	if (!root) return;
	var search = root.querySelector('[data-sc-group-search]');
	var filter = root.querySelector('[data-sc-group-filter]');
	var rows = Array.prototype.slice.call(root.querySelectorAll('[data-sc-group-row]'));
	var empty = root.querySelector('[data-sc-group-empty]');
	if (!search || !filter || !empty) return;
	function update() {
		var query = search.value.trim().toLowerCase(); var role = filter.value; var visible = 0;
		rows.forEach(function (row) { var matches = (!query || (row.getAttribute('data-search') || '').indexOf(query) !== -1) && (role === 'all' || row.getAttribute('data-role') === role); row.hidden = !matches; if (matches) visible += 1; });
		empty.hidden = visible > 0; empty.querySelector('td').textContent = (query || role !== 'all') ? 'No groups match these filters.' : 'No groups have been created yet.';
	}
	search.addEventListener('input', update); filter.addEventListener('change', update);
}());
