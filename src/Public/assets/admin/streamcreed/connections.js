(function () {
	'use strict';

	var root = document.querySelector('[data-sc-connections]');
	if (!root) return;

	var search = root.querySelector('[data-sc-connection-search]');
	var server = root.querySelector('[data-sc-connection-server]');
	var entries = root.querySelector('[data-sc-connection-entries]');
	var rows = root.querySelector('[data-sc-connection-rows]');
	var previous = root.querySelector('[data-sc-connection-previous]');
	var next = root.querySelector('[data-sc-connection-next]');
	var range = root.querySelector('[data-sc-connection-range]');
	var pageLabel = root.querySelector('[data-sc-connection-page]');
	var endpoint = root.getAttribute('data-endpoint') || 'table';
	var page = 1;
	var total = 0;
	var requestSequence = 0;
	var searchTimer;
	var refreshTimer;

	function allowed(name) { return root.getAttribute('data-can-' + name) === '1'; }
	function node(tag, className, text) { var element = document.createElement(tag); if (className) element.className = className; if (typeof text !== 'undefined') element.textContent = text; return element; }
	function state(message, error) { rows.replaceChildren(); var row = node('tr'); var cell = node('td', 'sc-table-state' + (error ? ' is-error' : ''), message); cell.colSpan = 8; row.appendChild(cell); rows.appendChild(row); }
	function duration(startedAt) { var seconds = Math.max(0, Math.floor(Date.now() / 1000) - Number(startedAt || 0)); if (seconds >= 86400) return Math.floor(seconds / 86400) + 'd ' + Math.floor(seconds % 86400 / 3600) + 'h'; if (seconds >= 3600) return Math.floor(seconds / 3600) + 'h ' + Math.floor(seconds % 3600 / 60) + 'm'; return Math.floor(seconds / 60) + 'm ' + (seconds % 60) + 's'; }
	function subscriberLink(item) { var label = item.subscriber || 'Unknown'; var link; if (item.subscriberType === 'Line' && allowed('lines') && item.userId) { link = node('a', '', label); link.href = 'line?id=' + encodeURIComponent(item.userId); return link; } if (item.subscriberType === 'MAG' && allowed('mags') && item.magId) { link = node('a', '', label); link.href = 'mag?id=' + encodeURIComponent(item.magId); return link; } if (item.subscriberType === 'Enigma2' && allowed('enigmas') && item.enigmaId) { link = node('a', '', label); link.href = 'enigma?id=' + encodeURIComponent(item.enigmaId); return link; } if (item.subscriberType === 'HMAC' && allowed('hmacs') && item.hmacId) { link = node('a', '', label); link.href = 'hmac?id=' + encodeURIComponent(item.hmacId); return link; } return node('strong', '', label); }
	function renderRow(item) {
		var row = node('tr');
		var quality = Math.max(0, Math.min(100, Number(item.quality) || 0));
		row.appendChild(node('td', '', ''));
		var qualityCell = row.lastChild; qualityCell.appendChild(node('span', 'sc-quality is-' + (quality > 50 ? (quality > 80 ? 'low' : 'medium') : 'high'), quality + '%'));
		var userCell = node('td'); var user = node('div', 'sc-table-identity'); user.appendChild(subscriberLink(item)); user.appendChild(node('small', '', item.subscriberType + (item.restreamer ? ' · Restreamer' : ''))); userCell.appendChild(user); row.appendChild(userCell);
		var streamCell = node('td'); var stream = node('div', 'sc-table-activity'); stream.appendChild(node('strong', '', item.streamName || 'Unknown stream')); stream.appendChild(node('small', '', item.streamType === 2 ? 'Movie' : (item.streamType === 4 ? 'Radio' : (item.streamType === 5 ? 'Series' : 'Live')))); streamCell.appendChild(stream); row.appendChild(streamCell);
		var serverCell = node('td'); var serverInfo = node('div', 'sc-table-activity'); var serverName = allowed('servers') && item.serverId ? node('a', '', item.serverName || 'Unknown server') : node('strong', '', item.serverName || 'Unknown server'); if (allowed('servers') && item.serverId) serverName.href = 'server_view?id=' + encodeURIComponent(item.serverId); serverInfo.appendChild(serverName); if (item.proxyName) serverInfo.appendChild(node('small', '', 'via ' + item.proxyName)); serverCell.appendChild(serverInfo); row.appendChild(serverCell);
		row.appendChild(node('td', 'sc-table-secondary', item.player || 'Unknown'));
		var ipCell = node('td'); var ipInfo = node('div', 'sc-table-activity'); ipInfo.appendChild(node('strong', '', (item.country ? item.country + ' · ' : '') + (item.ip || 'Unknown IP'))); if (item.isp) ipInfo.appendChild(node('small', '', item.isp)); ipCell.appendChild(ipInfo); row.appendChild(ipCell);
		row.appendChild(node('td', 'sc-connection-duration', duration(item.startedAt)));
		row.appendChild(node('td', 'sc-output-tag', item.output || '—'));
		return row;
	}
	function render(data) {
		var items = Array.isArray(data.data) ? data.data : []; total = Number(data.recordsFiltered || 0); rows.replaceChildren();
		if (!items.length) state('No active connections match these filters.', false); else items.forEach(function (item) { rows.appendChild(renderRow(item)); });
		var size = Number(entries.value) || 25; var first = total ? ((page - 1) * size) + 1 : 0; var last = Math.min(page * size, total); var pages = Math.max(1, Math.ceil(total / size));
		range.textContent = 'Showing ' + first.toLocaleString() + '–' + last.toLocaleString() + ' of ' + total.toLocaleString(); pageLabel.textContent = 'Page ' + page.toLocaleString() + ' of ' + pages.toLocaleString(); previous.disabled = page <= 1; next.disabled = page >= pages;
	}
	function scheduleRefresh() { window.clearTimeout(refreshTimer); refreshTimer = window.setTimeout(function () { if (!document.hidden) load(); else scheduleRefresh(); }, 5000); }
	function load() {
		var sequence = ++requestSequence; var size = Number(entries.value) || Number(root.getAttribute('data-default-entries')) || 25; var params = new URLSearchParams();
		params.set('draw', String(sequence)); params.set('id', 'live_connections'); params.set('view', 'streamcreed'); params.set('start', String((page - 1) * size)); params.set('length', String(size)); params.set('search[value]', search ? search.value.trim() : ''); params.set('server_id', server.value); params.set('order[0][column]', '8'); params.set('order[0][dir]', 'desc');
		fetch(endpoint + '?' + params.toString(), { credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
			.then(function (response) { if (!response.ok) throw new Error('Request failed'); return response.json(); })
			.then(function (data) { if (sequence === requestSequence) render(data); })
			.catch(function () { if (sequence === requestSequence) { state('Live connections could not be loaded. Refresh the page or use the legacy connection tools.', true); range.textContent = 'Unable to load results'; previous.disabled = true; next.disabled = true; } })
			.finally(scheduleRefresh);
	}
	if (search) search.addEventListener('input', function () { window.clearTimeout(searchTimer); searchTimer = window.setTimeout(function () { page = 1; load(); }, 350); });
	server.addEventListener('change', function () { page = 1; load(); }); entries.addEventListener('change', function () { page = 1; load(); }); previous.addEventListener('click', function () { if (page > 1) { page -= 1; load(); } }); next.addEventListener('click', function () { if (page * Number(entries.value) < total) { page += 1; load(); } }); window.addEventListener('pagehide', function () { window.clearTimeout(refreshTimer); }); load();
}());
