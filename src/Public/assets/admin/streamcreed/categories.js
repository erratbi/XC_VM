(function () {
	'use strict';
	var root = document.querySelector('[data-sc-categories]');
	if (!root) return;
	var search = root.querySelector('[data-sc-category-search]');
	var filter = root.querySelector('[data-sc-category-filter]');
	var rows = Array.prototype.slice.call(root.querySelectorAll('[data-sc-category-row]'));
	var empty = root.querySelector('[data-sc-category-empty]');
	if (!search || !filter || !empty) return;
	function update() {
		var query = search.value.trim().toLowerCase(); var type = filter.value; var visible = 0;
		rows.forEach(function (row) { var matches = (!query || (row.getAttribute('data-search') || '').indexOf(query) !== -1) && (type === 'all' || row.getAttribute('data-type') === type); row.hidden = !matches; if (matches) visible += 1; });
		empty.hidden = visible > 0; empty.querySelector('td').textContent = (query || type !== 'all') ? 'No categories match these filters.' : 'No categories have been created yet.';
	}
	search.addEventListener('input', update); filter.addEventListener('change', update);
}());
