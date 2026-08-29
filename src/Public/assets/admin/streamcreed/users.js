(function () {
	'use strict';

	var root = document.querySelector('[data-sc-registered-users]');
	if (!root) return;

	var search = root.querySelector('[data-sc-user-search]');
	var filter = root.querySelector('[data-sc-user-filter]');
	var entries = root.querySelector('[data-sc-user-entries]');
	var rows = root.querySelector('[data-sc-user-rows]');
	var previous = root.querySelector('[data-sc-user-previous]');
	var next = root.querySelector('[data-sc-user-next]');
	var range = root.querySelector('[data-sc-user-range]');
	var pageLabel = root.querySelector('[data-sc-user-page]');
	var endpoint = root.getAttribute('data-endpoint') || 'table';
	var page = 1;
	var total = 0;
	var requestSequence = 0;
	var searchTimer;

	function allowed(name) { return root.getAttribute('data-can-' + name) === '1'; }
	function node(tag, className, text) { var element = document.createElement(tag); if (className) element.className = className; if (typeof text !== 'undefined') element.textContent = text; return element; }
	function state(message, error) { rows.replaceChildren(); var row = node('tr'); var cell = node('td', 'sc-table-state' + (error ? ' is-error' : ''), message); cell.colSpan = 10; row.appendChild(cell); rows.appendChild(row); }
	function linkOrText(label, href, enabled, className) { var item = node(enabled ? 'a' : 'span', className || '', label); if (enabled) item.href = href; return item; }
	function countLink(count, pageName, permission, ownerId) { return linkOrText(Number(count || 0).toLocaleString(), pageName + '?owner=' + encodeURIComponent(ownerId), allowed(permission) && Number(count) > 0, 'sc-connection-link'); }
	function renderRow(item) {
		var row = node('tr');
		var identityCell = node('td'); var identity = node('div', 'sc-table-identity');
		identity.appendChild(linkOrText(item.username || '—', 'user?id=' + encodeURIComponent(item.id), allowed('edit'), ''));
		identity.appendChild(node('small', '', '#' + item.id + (item.ip ? ' · ' + item.ip : '')));
		identityCell.appendChild(identity); row.appendChild(identityCell);
		var ownerCell = node('td');
		ownerCell.appendChild(linkOrText(item.owner || 'System', 'user?id=' + encodeURIComponent(item.ownerId), allowed('edit') && item.ownerId > 0, 'sc-table-secondary'));
		row.appendChild(ownerCell);
		row.appendChild(node('td', 'sc-table-secondary', item.groupName || '—'));
		var statusCell = node('td'); statusCell.appendChild(node('span', 'sc-row-status is-' + (item.status === 'active' ? 'active' : 'disabled'), item.statusLabel || 'Disabled')); row.appendChild(statusCell);
		row.appendChild(node('td', item.isReseller ? '' : 'sc-table-muted', item.isReseller ? Number(item.credits || 0).toLocaleString() : '—'));
		var lines = node('td'); lines.appendChild(countLink(item.userLines, 'lines', 'lines', item.id)); row.appendChild(lines);
		var mags = node('td'); mags.appendChild(countLink(item.magLines, 'mags', 'mags', item.id)); row.appendChild(mags);
		var enigmas = node('td'); enigmas.appendChild(countLink(item.e2Lines, 'enigmas', 'enigmas', item.id)); row.appendChild(enigmas);
		row.appendChild(node('td', 'sc-table-secondary', item.lastLogin || 'Never'));
		var action = node('td', 'sc-table-actions'); if (allowed('edit')) action.appendChild(linkOrText('Edit', 'user?id=' + encodeURIComponent(item.id), true, 'sc-row-action')); row.appendChild(action);
		return row;
	}
	function render(data) {
		var items = Array.isArray(data.data) ? data.data : []; total = Number(data.recordsFiltered || 0); rows.replaceChildren();
		if (!items.length) state('No registered users match these filters.', false); else items.forEach(function (item) { rows.appendChild(renderRow(item)); });
		var size = Number(entries.value) || 25; var first = total ? ((page - 1) * size) + 1 : 0; var last = Math.min(page * size, total); var pages = Math.max(1, Math.ceil(total / size));
		range.textContent = 'Showing ' + first.toLocaleString() + '–' + last.toLocaleString() + ' of ' + total.toLocaleString(); pageLabel.textContent = 'Page ' + page.toLocaleString() + ' of ' + pages.toLocaleString(); previous.disabled = page <= 1; next.disabled = page >= pages;
	}
	function load() {
		var sequence = ++requestSequence; var size = Number(entries.value) || Number(root.getAttribute('data-default-entries')) || 25; var params = new URLSearchParams();
		params.set('draw', String(sequence)); params.set('id', 'reg_users'); params.set('view', 'streamcreed'); params.set('start', String((page - 1) * size)); params.set('length', String(size)); params.set('search[value]', search.value.trim()); params.set('filter', filter.value); params.set('order[0][column]', '0'); params.set('order[0][dir]', 'desc');
		state('Loading registered users…', false);
		fetch(endpoint + '?' + params.toString(), { credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
			.then(function (response) { if (!response.ok) throw new Error('Request failed'); return response.json(); })
			.then(function (data) { if (sequence === requestSequence) render(data); })
			.catch(function () { if (sequence === requestSequence) { state('Registered users could not be loaded. Refresh the page or use the legacy users screen.', true); range.textContent = 'Unable to load results'; previous.disabled = true; next.disabled = true; } });
	}
	search.addEventListener('input', function () { window.clearTimeout(searchTimer); searchTimer = window.setTimeout(function () { page = 1; load(); }, 350); });
	filter.addEventListener('change', function () { page = 1; load(); }); entries.addEventListener('change', function () { page = 1; load(); }); previous.addEventListener('click', function () { if (page > 1) { page -= 1; load(); } }); next.addEventListener('click', function () { if (page * Number(entries.value) < total) { page += 1; load(); } });
	load();
}());
