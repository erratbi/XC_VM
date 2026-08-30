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
	var bulkBar = root.querySelector('[data-sc-user-bulk]');
	var selectedCount = root.querySelector('[data-sc-user-selected-count]');
	var selectAll = root.querySelector('[data-sc-user-select-all]');
	var endpoint = root.getAttribute('data-endpoint') || 'table';
	var page = 1;
	var total = 0;
	var requestSequence = 0;
	var searchTimer;
	var selected = new Set();
	var visibleIds = [];
	var busy = false;

	function allowed(name) { return root.getAttribute('data-can-' + name) === '1'; }
	function node(tag, className, text) { var element = document.createElement(tag); if (className) element.className = className; if (typeof text !== 'undefined') element.textContent = text; return element; }
	function state(message, error) { rows.replaceChildren(); var row = node('tr'); var cell = node('td', 'sc-table-state' + (error ? ' is-error' : ''), message); cell.colSpan = allowed('edit') ? 11 : 10; row.appendChild(cell); rows.appendChild(row); }
	function linkOrText(label, href, enabled, className) { var item = node(enabled ? 'a' : 'span', className || '', label); if (enabled) item.href = href; return item; }
	function countLink(count, pageName, permission, ownerId) { return linkOrText(Number(count || 0).toLocaleString(), pageName + '?owner=' + encodeURIComponent(ownerId), allowed(permission) && Number(count) > 0, 'sc-connection-link'); }
	function toast(message, error) { var item = node('div', 'sc-toast' + (error ? ' is-error' : ''), message); document.body.appendChild(item); requestAnimationFrame(function () { item.classList.add('is-visible'); }); window.setTimeout(function () { item.classList.remove('is-visible'); window.setTimeout(function () { item.remove(); }, 180); }, 2600); }
	function updateSelection() {
		if (!bulkBar) return;
		bulkBar.hidden = selected.size === 0;
		selectedCount.textContent = selected.size.toLocaleString();
		if (selectAll) {
			var selectedVisible = visibleIds.filter(function (id) { return selected.has(id); }).length;
			selectAll.checked = visibleIds.length > 0 && selectedVisible === visibleIds.length;
			selectAll.indeterminate = selectedVisible > 0 && selectedVisible < visibleIds.length;
		}
	}
	function confirmAction(action, count) { return action !== 'delete' || window.confirm(count === 1 ? 'Are you sure you want to delete this user?' : 'Are you sure you want to delete these users?'); }
	function runAction(action, ids, bulk) {
		if (busy || !ids.length || !confirmAction(action, ids.length)) return;
		busy = true;
		var url = !bulk
			? './api?action=reg_user&sub=' + encodeURIComponent(action) + '&user_id=' + encodeURIComponent(ids[0])
			: './api?action=multi&type=user&sub=' + encodeURIComponent(action) + '&ids=' + encodeURIComponent(JSON.stringify(ids));
		fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'application/json, text/javascript, */*; q=0.01', 'X-Requested-With': 'XMLHttpRequest' } })
			.then(function (response) { if (!response.ok) throw new Error('Request failed'); return response.json(); })
			.then(function (data) {
				if (data.result !== true) throw new Error('Action failed');
				var plural = ids.length > 1 ? 'Users have been ' : 'User has been ';
				toast(plural + (action === 'delete' ? 'deleted.' : action === 'enable' ? 'enabled.' : 'disabled.'), false);
				ids.forEach(function (id) { selected.delete(String(id)); }); updateSelection(); load();
			})
			.catch(function () { toast('An error occurred while processing your request.', true); })
			.finally(function () { busy = false; });
	}
	function renderRow(item) {
		var row = node('tr');
		if (allowed('edit')) { var selectCell = node('td', 'sc-select-column'); var checkbox = document.createElement('input'); checkbox.type = 'checkbox'; checkbox.checked = selected.has(String(item.id)); checkbox.setAttribute('aria-label', 'Select ' + (item.username || 'user')); checkbox.addEventListener('change', function () { if (checkbox.checked) selected.add(String(item.id)); else selected.delete(String(item.id)); updateSelection(); }); selectCell.appendChild(checkbox); row.appendChild(selectCell); }
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
		var action = node('td', 'sc-table-actions'); if (allowed('edit')) { action.appendChild(linkOrText('Edit', 'user?id=' + encodeURIComponent(item.id), true, 'sc-row-action')); var toggle = node('button', 'sc-row-action', item.status === 'active' ? 'Disable' : 'Enable'); toggle.type = 'button'; toggle.addEventListener('click', function () { runAction(item.status === 'active' ? 'disable' : 'enable', [String(item.id)]); }); action.appendChild(toggle); var remove = node('button', 'sc-row-action is-danger', 'Delete'); remove.type = 'button'; remove.addEventListener('click', function () { runAction('delete', [String(item.id)]); }); action.appendChild(remove); } row.appendChild(action);
		return row;
	}
	function render(data) {
		var items = Array.isArray(data.data) ? data.data : []; total = Number(data.recordsFiltered || 0); visibleIds = items.map(function (item) { return String(item.id); }); rows.replaceChildren();
		if (!items.length) state('No registered users match these filters.', false); else items.forEach(function (item) { rows.appendChild(renderRow(item)); });
		var size = Number(entries.value) || 25; var first = total ? ((page - 1) * size) + 1 : 0; var last = Math.min(page * size, total); var pages = Math.max(1, Math.ceil(total / size));
		range.textContent = 'Showing ' + first.toLocaleString() + '–' + last.toLocaleString() + ' of ' + total.toLocaleString(); pageLabel.textContent = 'Page ' + page.toLocaleString() + ' of ' + pages.toLocaleString(); previous.disabled = page <= 1; next.disabled = page >= pages; updateSelection();
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
	if (selectAll) selectAll.addEventListener('change', function () { visibleIds.forEach(function (id) { if (selectAll.checked) selected.add(id); else selected.delete(id); }); rows.querySelectorAll('.sc-select-column input').forEach(function (checkbox) { checkbox.checked = selectAll.checked; }); updateSelection(); });
	root.querySelectorAll('[data-sc-user-bulk-action]').forEach(function (button) { button.addEventListener('click', function () { runAction(button.getAttribute('data-sc-user-bulk-action'), Array.from(selected), true); }); });
	var clearSelection = root.querySelector('[data-sc-user-selection-clear]'); if (clearSelection) clearSelection.addEventListener('click', function () { selected.clear(); rows.querySelectorAll('.sc-select-column input').forEach(function (checkbox) { checkbox.checked = false; }); updateSelection(); });
	load();
}());
