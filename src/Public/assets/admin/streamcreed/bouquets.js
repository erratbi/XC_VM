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
	root.addEventListener('click', function (event) {
		var button = event.target.closest('[data-sc-bouquet-delete]');
		if (!button || !root.contains(button)) return;
		var id = button.getAttribute('data-sc-bouquet-delete');
		var row = button.closest('[data-sc-bouquet-row]');
		var name = row ? row.querySelector('.sc-table-identity').textContent.trim().split('#')[0].trim() : 'this bouquet';
		if (!window.confirm('Delete "' + name + '"?')) return;
		button.disabled = true;
		fetch('./api?action=bouquet&sub=delete&bouquet_id=' + encodeURIComponent(id), { credentials: 'same-origin', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
			.then(function (response) { if (!response.ok) throw new Error(); return response.json(); })
			.then(function (data) {
				if (data.result !== true) throw new Error();
				if (row) row.remove();
				rows = Array.prototype.slice.call(root.querySelectorAll('[data-sc-bouquet-row]'));
				search.dispatchEvent(new Event('input'));
			})
			.catch(function () { button.disabled = false; window.alert('The bouquet could not be deleted.'); });
	});
}());
