(function () {
	'use strict';
	var root = document.querySelector('[data-sc-packages]');
	if (!root) return;
	var search = root.querySelector('[data-sc-package-search]');
	var filter = root.querySelector('[data-sc-package-filter]');
	var rows = Array.prototype.slice.call(root.querySelectorAll('[data-sc-package-row]'));
	var empty = root.querySelector('[data-sc-package-empty]');
	if (!search || !filter || !empty) return;
	function update() {
		var query = search.value.trim().toLowerCase(); var type = filter.value; var visible = 0;
		rows.forEach(function (row) {
			var matchesSearch = !query || (row.getAttribute('data-search') || '').indexOf(query) !== -1;
			var matchesType = type === 'all' || row.getAttribute('data-type') === type || (type === 'official' && row.getAttribute('data-official') === '1');
			row.hidden = !(matchesSearch && matchesType); if (!row.hidden) visible += 1;
		});
		empty.hidden = visible > 0; empty.querySelector('td').textContent = (query || type !== 'all') ? 'No packages match these filters.' : 'No packages have been created yet.';
	}
	search.addEventListener('input', update); filter.addEventListener('change', update);
}());
