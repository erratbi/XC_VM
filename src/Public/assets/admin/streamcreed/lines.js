(function () {
	'use strict';

	var root = document.querySelector('[data-sc-subscriptions]');
	if (!root) return;

	var search = root.querySelector('[data-sc-subscription-search]');
	var filter = root.querySelector('[data-sc-subscription-filter]');
	var entries = root.querySelector('[data-sc-subscription-entries]');
	var rows = root.querySelector('[data-sc-subscription-rows]');
	var previous = root.querySelector('[data-sc-subscription-previous]');
	var next = root.querySelector('[data-sc-subscription-next]');
	var range = root.querySelector('[data-sc-subscription-range]');
	var pageLabel = root.querySelector('[data-sc-subscription-page]');
	var canEdit = root.getAttribute('data-can-edit') === '1';
	var canViewConnections = root.getAttribute('data-can-view-connections') === '1';
	var endpoint = root.getAttribute('data-endpoint') || 'table';
	var page = 1;
	var total = 0;
	var requestSequence = 0;
	var searchTimer;

	function element(tag, className, text) {
		var node = document.createElement(tag);
		if (className) node.className = className;
		if (typeof text !== 'undefined') node.textContent = text;
		return node;
	}

	function addStateRow(message, isError) {
		rows.replaceChildren();
		var row = element('tr');
		var cell = element('td', 'sc-table-state' + (isError ? ' is-error' : ''), message);
		cell.colSpan = 8;
		row.appendChild(cell);
		rows.appendChild(row);
	}

	function statusBadge(item) {
		var allowed = ['active', 'disabled', 'banned', 'expired'];
		var status = allowed.indexOf(item.status) === -1 ? 'disabled' : item.status;
		return element('span', 'sc-row-status is-' + status, item.statusLabel || status);
	}

	function typeTags(item) {
		var wrapper = element('div', 'sc-tag-list');
		if (item.trial) wrapper.appendChild(element('span', 'sc-tag is-trial', 'Trial'));
		if (item.restreamer) wrapper.appendChild(element('span', 'sc-tag', 'Restreamer'));
		if (!item.trial && !item.restreamer) wrapper.appendChild(element('span', 'sc-table-muted', 'Standard'));
		return wrapper;
	}

	function renderRow(item) {
		var row = element('tr');
		var identityCell = element('td');
		var identity = element('div', 'sc-table-identity');
		var username = canEdit ? element('a', '', item.username || '—') : element('strong', '', item.username || '—');
		if (canEdit) username.href = 'line?id=' + encodeURIComponent(item.id);
		identity.appendChild(username);
		identity.appendChild(element('small', '', '#' + item.id + (item.online ? ' · Online' : '')));
		identityCell.appendChild(identity);
		row.appendChild(identityCell);

		row.appendChild(element('td', 'sc-table-secondary', item.owner || 'System'));
		var statusCell = element('td');
		statusCell.appendChild(statusBadge(item));
		row.appendChild(statusCell);

		var connectionCell = element('td');
		var connectionText = String(item.connections || 0) + ' / ' + (item.maxConnections === null ? '∞' : item.maxConnections);
		if (canViewConnections && item.connections > 0) {
			var connectionLink = element('a', 'sc-connection-link', connectionText);
			connectionLink.href = 'live_connections?user_id=' + encodeURIComponent(item.id);
			connectionCell.appendChild(connectionLink);
		} else {
			connectionCell.appendChild(element('span', 'sc-connection-link', connectionText));
		}
		row.appendChild(connectionCell);

		var typeCell = element('td');
		typeCell.appendChild(typeTags(item));
		row.appendChild(typeCell);
		row.appendChild(element('td', item.status === 'expired' ? 'sc-text-danger' : '', item.expiresAtLabel || 'Never'));

		var activityCell = element('td');
		var activity = element('div', 'sc-table-activity');
		activity.appendChild(element('span', '', item.lastActivityLabel || 'Never'));
		if (item.currentStream) activity.appendChild(element('small', '', item.currentStream));
		activityCell.appendChild(activity);
		row.appendChild(activityCell);

		var actionCell = element('td', 'sc-table-actions');
		if (canEdit) {
			var edit = element('a', 'sc-row-action', 'Edit');
			edit.href = 'line?id=' + encodeURIComponent(item.id);
			actionCell.appendChild(edit);
		}
		row.appendChild(actionCell);
		return row;
	}

	function render(data) {
		var items = Array.isArray(data.data) ? data.data : [];
		total = Number(data.recordsFiltered || 0);
		rows.replaceChildren();
		if (!items.length) {
			addStateRow('No subscriptions match these filters.', false);
		} else {
			items.forEach(function (item) { rows.appendChild(renderRow(item)); });
		}

		var pageSize = Number(entries.value) || 25;
		var first = total ? ((page - 1) * pageSize) + 1 : 0;
		var last = Math.min(page * pageSize, total);
		var pages = Math.max(1, Math.ceil(total / pageSize));
		range.textContent = 'Showing ' + first.toLocaleString() + '–' + last.toLocaleString() + ' of ' + total.toLocaleString();
		pageLabel.textContent = 'Page ' + page.toLocaleString() + ' of ' + pages.toLocaleString();
		previous.disabled = page <= 1;
		next.disabled = page >= pages;
	}

	function load() {
		var sequence = ++requestSequence;
		var pageSize = Number(entries.value) || Number(root.getAttribute('data-default-entries')) || 25;
		var params = new URLSearchParams();
		params.set('draw', String(sequence));
		params.set('id', 'lines');
		params.set('view', 'streamcreed');
		params.set('start', String((page - 1) * pageSize));
		params.set('length', String(pageSize));
		params.set('search[value]', search.value.trim());
		params.set('filter', filter.value);
		params.set('order[0][column]', '0');
		params.set('order[0][dir]', 'desc');
		addStateRow('Loading subscriptions…', false);

		fetch(endpoint + '?' + params.toString(), {
			credentials: 'same-origin',
			headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
		}).then(function (response) {
			if (!response.ok) throw new Error('Request failed with status ' + response.status);
			return response.json();
		}).then(function (data) {
			if (sequence !== requestSequence) return;
			render(data);
		}).catch(function () {
			if (sequence !== requestSequence) return;
			addStateRow('Subscriptions could not be loaded. Refresh the page or use the legacy subscriptions screen.', true);
			range.textContent = 'Unable to load results';
			previous.disabled = true;
			next.disabled = true;
		});
	}

	search.addEventListener('input', function () {
		window.clearTimeout(searchTimer);
		searchTimer = window.setTimeout(function () { page = 1; load(); }, 350);
	});
	filter.addEventListener('change', function () { page = 1; load(); });
	entries.addEventListener('change', function () { page = 1; load(); });
	previous.addEventListener('click', function () { if (page > 1) { page -= 1; load(); } });
	next.addEventListener('click', function () { if (page * Number(entries.value) < total) { page += 1; load(); } });

	load();
}());
