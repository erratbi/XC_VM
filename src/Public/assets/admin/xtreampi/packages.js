(function () {
	'use strict';
	var root = document.querySelector('[data-sc-packages]');
	if (!root) return;
	var search = root.querySelector('[data-sc-package-search]');
	var filter = root.querySelector('[data-sc-package-filter]');
	var rows = Array.prototype.slice.call(root.querySelectorAll('[data-sc-package-row]'));
	var empty = root.querySelector('[data-sc-package-empty]');
	if (!search || !filter || !empty) return;
	function toast(message, error) { var item = document.createElement('div'); item.className = 'sc-toast' + (error ? ' is-error' : ''); item.textContent = message; document.body.appendChild(item); requestAnimationFrame(function () { item.classList.add('is-visible'); }); window.setTimeout(function () { item.classList.remove('is-visible'); window.setTimeout(function () { item.remove(); }, 180); }, 2600); }
	function removePackage(row) {
		if (!window.confirm('Are you sure you want to delete this package?')) return;
		var id = row.getAttribute('data-package-id');
		fetch('./api?action=package&sub=delete&package_id=' + encodeURIComponent(id), { credentials: 'same-origin', headers: { 'Accept': 'application/json, text/javascript, */*; q=0.01', 'X-Requested-With': 'XMLHttpRequest' } })
			.then(function (response) { if (!response.ok) throw new Error('Request failed'); return response.json(); })
			.then(function (data) { if (data.result !== true) throw new Error('Delete failed'); row.remove(); rows = rows.filter(function (item) { return item !== row; }); update(); toast('Package has been deleted.', false); })
			.catch(function () { toast('An error occurred while processing your request.', true); });
	}
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
	root.querySelectorAll('[data-sc-package-delete]').forEach(function (button) { button.addEventListener('click', function () { removePackage(button.closest('[data-sc-package-row]')); }); });
}());
