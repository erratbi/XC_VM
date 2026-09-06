(function () {
	'use strict';
	var root = document.querySelector('[data-sc-groups]');
	if (!root) return;
	var search = root.querySelector('[data-sc-group-search]');
	var filter = root.querySelector('[data-sc-group-filter]');
	var rows = Array.prototype.slice.call(root.querySelectorAll('[data-sc-group-row]'));
	var empty = root.querySelector('[data-sc-group-empty]');
	if (!search || !filter || !empty) return;
	function toast(message, error) { var item = document.createElement('div'); item.className = 'sc-toast' + (error ? ' is-error' : ''); item.textContent = message; document.body.appendChild(item); requestAnimationFrame(function () { item.classList.add('is-visible'); }); window.setTimeout(function () { item.classList.remove('is-visible'); window.setTimeout(function () { item.remove(); }, 180); }, 2600); }
	function removeGroup(row) {
		if (!window.confirm('Are you sure you want to delete this group?')) return;
		var id = row.getAttribute('data-group-id');
		fetch('./api?action=group&sub=delete&group_id=' + encodeURIComponent(id), { credentials: 'same-origin', headers: { 'Accept': 'application/json, text/javascript, */*; q=0.01', 'X-Requested-With': 'XMLHttpRequest' } })
			.then(function (response) { if (!response.ok) throw new Error('Request failed'); return response.json(); })
			.then(function (data) { if (data.result !== true) throw new Error('Delete failed'); row.remove(); rows = rows.filter(function (item) { return item !== row; }); update(); toast('Group has been deleted.', false); })
			.catch(function () { toast('An error occurred while processing your request.', true); });
	}
	function update() {
		var query = search.value.trim().toLowerCase(); var role = filter.value; var visible = 0;
		rows.forEach(function (row) { var matches = (!query || (row.getAttribute('data-search') || '').indexOf(query) !== -1) && (role === 'all' || row.getAttribute('data-role') === role); row.hidden = !matches; if (matches) visible += 1; });
		empty.hidden = visible > 0; empty.querySelector('td').textContent = (query || role !== 'all') ? 'No groups match these filters.' : 'No groups have been created yet.';
	}
	search.addEventListener('input', update); filter.addEventListener('change', update);
	root.querySelectorAll('[data-sc-group-delete]').forEach(function (button) { button.addEventListener('click', function () { removeGroup(button.closest('[data-sc-group-row]')); }); });
}());
