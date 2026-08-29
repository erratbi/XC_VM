(function () {
	'use strict';
	var root = document.querySelector('[data-sc-bouquets]');
	if (!root) return;
	var search = root.querySelector('[data-sc-bouquet-search]');
	var rows = Array.prototype.slice.call(root.querySelectorAll('[data-sc-bouquet-row]'));
	var empty = root.querySelector('[data-sc-bouquet-empty]');
	if (!search || !empty) return;
	search.addEventListener('input', function () {
		var query = search.value.trim().toLowerCase();
		var visible = 0;
		rows.forEach(function (row) {
			var matches = !query || (row.getAttribute('data-search') || '').indexOf(query) !== -1;
			row.hidden = !matches;
			if (matches) visible += 1;
		});
		empty.hidden = visible > 0;
		empty.querySelector('td').textContent = query ? 'No bouquets match this search.' : 'No bouquets have been created yet.';
	});
}());
