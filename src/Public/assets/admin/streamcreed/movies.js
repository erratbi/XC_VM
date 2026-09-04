(function () {
	'use strict';

	var root = document.querySelector('[data-sc-movies]');
	if (!root) return;

	var endpoint = root.getAttribute('data-endpoint') || 'table';
	var search = root.querySelector('[data-sc-movie-search]');
	var server = root.querySelector('[data-sc-movie-server]');
	var category = root.querySelector('[data-sc-movie-category]');
	var status = root.querySelector('[data-sc-movie-status]');
	var video = root.querySelector('[data-sc-movie-video]');
	var audio = root.querySelector('[data-sc-movie-audio]');
	var resolution = root.querySelector('[data-sc-movie-resolution]');
	var entries = root.querySelector('[data-sc-movie-entries]');
	var rows = root.querySelector('[data-sc-movie-rows]');
	var range = root.querySelector('[data-sc-movie-range]');
	var pageLabel = root.querySelector('[data-sc-movie-page]');
	var previous = root.querySelector('[data-sc-movie-previous]');
	var next = root.querySelector('[data-sc-movie-next]');
	var canEdit = root.getAttribute('data-can-edit') === '1';
	var canPlay = root.getAttribute('data-can-play') === '1';
	var canViewConnections = root.getAttribute('data-can-view-connections') === '1';
	var showImages = root.getAttribute('data-show-images') === '1';
	var page = 1;
	var total = 0;
	var requestSequence = 0;
	var searchTimer = null;
	var actionBusy = false;

	var playerDialog = document.querySelector('[data-sc-movie-player-dialog]');
	var playerScaler = playerDialog ? playerDialog.querySelector('[data-sc-movie-player-scaler]') : null;
	var playerFrame = playerDialog ? playerDialog.querySelector('[data-sc-movie-player-frame]') : null;
	var playerClose = playerDialog ? playerDialog.querySelector('[data-sc-movie-player-close]') : null;
	var actionMenu = element('div', 'sc-row-action-menu');
	var activeTrigger = null;
	actionMenu.hidden = true;
	document.body.appendChild(actionMenu);

	function element(tag, className, text) {
		var node = document.createElement(tag);
		if (className) node.className = className;
		if (typeof text !== 'undefined') node.textContent = text;
		return node;
	}

	function clean(value) {
		return String(value === undefined || value === null ? '' : value).replace(/<[^>]*>/g, '').trim();
	}

	function toast(message, isError) {
		var item = element('div', 'sc-toast' + (isError ? ' is-error' : ''), message);
		document.body.appendChild(item);
		window.requestAnimationFrame(function () { item.classList.add('is-visible'); });
		window.setTimeout(function () {
			item.classList.remove('is-visible');
			window.setTimeout(function () { item.remove(); }, 180);
		}, 2600);
	}

	function statusClass(value) {
		var code = Number(value);
		if (code === 1) return 'is-active';
		if (code === 2) return 'is-warning';
		if (code === 3 || code === 4) return 'is-banned';
		if (code === 5) return 'is-neutral';
		return 'is-disabled';
	}

	function statusLabel(item) {
		var labels = { '-1': 'No server', '0': 'Ready', '1': 'Encoded', '2': 'Encoding', '3': 'Direct', '4': 'Down', '5': 'Direct proxy' };
		return labels[String(item.status)] || clean(item.statusLabel) || 'Unknown';
	}

	function actionControl(tag, label, icon, extraClass) {
		var control = element(tag, 'sc-row-action' + (extraClass ? ' ' + extraClass : ''));
		if (tag === 'button') control.type = 'button';
		control.title = label;
		control.setAttribute('aria-label', label);
		var glyph = element('i', icon);
		glyph.setAttribute('aria-hidden', 'true');
		control.appendChild(glyph);
		return control;
	}

	function closeActionMenu() {
		actionMenu.hidden = true;
		actionMenu.replaceChildren();
		activeTrigger = null;
	}

	function menuAction(label, icon, callback, className) {
		var button = actionControl('button', label, icon, 'sc-menu-action' + (className ? ' ' + className : ''));
		button.appendChild(element('span', '', label));
		button.addEventListener('click', function () {
			closeActionMenu();
			callback();
		});
		return button;
	}

	function openPlayer(item) {
		if (!playerDialog || !playerFrame) return;
		playerFrame.src = './player?type=movie&id=' + encodeURIComponent(item.id) + '&container=' + encodeURIComponent(item.target_container || '');
		if (playerScaler) playerScaler.classList.add('is-loading');
		playerDialog.showModal();
	}

	function runAction(action, item) {
		if (actionBusy) return;
		var name = clean(item.name) || ('Movie #' + item.id);
		if (action === 'delete' && !window.confirm('Are you sure you want to delete "' + name + '"?')) return;
		if (action === 'stop' && !window.confirm('Stop encoding "' + name + '"?')) return;

		actionBusy = true;
		fetch('./api?action=movie&sub=' + encodeURIComponent(action) + '&stream_id=' + encodeURIComponent(item.id) + '&server_id=' + encodeURIComponent(item.serverId), {
			credentials: 'same-origin',
			headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
		}).then(function (response) {
			if (!response.ok) throw new Error('Request failed');
			return response.json();
		}).then(function (data) {
			if (data.result !== true) throw new Error('Action failed');
			var messages = { start: 'Movie encoding has been started.', stop: 'Movie encoding has been stopped.', delete: 'Movie has been deleted.' };
			toast(messages[action] || 'Action completed.', false);
			load();
		}).catch(function () {
			toast('The movie action could not be completed.', true);
		}).finally(function () {
			actionBusy = false;
		});
	}

	function populateActionMenu(item) {
		actionMenu.replaceChildren();
		if (canPlay && item.can_play) {
			actionMenu.appendChild(menuAction('Play', 'fe-play', function () { openPlayer(item); }));
		}
		if (!canEdit) return;
		if (item.encode_action === 'stop') {
			actionMenu.appendChild(menuAction('Stop encoding', 'fe-stop-circle', function () { runAction('stop', item); }, 'is-warning'));
		} else if (item.encode_action === 'start') {
			var label = Number(item.status) === 1 ? 'Encode' : 'Start encoding';
			actionMenu.appendChild(menuAction(label, Number(item.status) === 1 ? 'fe-refresh-cw' : 'fe-play-circle', function () { runAction('start', item); }));
		}
		actionMenu.appendChild(element('div', 'sc-action-menu-divider'));
		var editLink = actionControl('a', 'Edit', 'fe-edit-2', 'sc-menu-action');
		editLink.href = 'movie?id=' + encodeURIComponent(item.id);
		editLink.appendChild(element('span', '', 'Edit'));
		actionMenu.appendChild(editLink);
		actionMenu.appendChild(menuAction('Delete', 'fe-trash-2', function () { runAction('delete', item); }, 'is-danger'));
	}

	function openActionMenu(item, trigger) {
		if (activeTrigger === trigger) {
			closeActionMenu();
			return;
		}
		closeActionMenu();
		populateActionMenu(item);
		if (!actionMenu.children.length) return;
		activeTrigger = trigger;
		actionMenu.hidden = false;
		var rect = trigger.getBoundingClientRect();
		var menuRect = actionMenu.getBoundingClientRect();
		var top = rect.bottom + 6;
		if (top + menuRect.height > window.innerHeight - 8) top = rect.top - menuRect.height - 6;
		actionMenu.style.top = Math.max(8, top) + 'px';
		actionMenu.style.left = Math.max(8, Math.min(rect.right - menuRect.width, window.innerWidth - menuRect.width - 8)) + 'px';
	}

	function buildMediaInfo(item) {
		var info = element('div', 'sc-stream-specs');
		var primary = [];
		if (item.width && item.height) primary.push(item.width + '×' + item.height);
		if (item.bitrate) primary.push(Number(item.bitrate).toLocaleString() + ' Kbps');
		if (item.duration) primary.push(clean(item.duration));
		if (primary.length) info.appendChild(element('div', 'sc-specs-primary', primary.join(' • ')));
		var secondary = [];
		if (item.video_codec && item.video_codec !== 'N/A') secondary.push(item.video_codec);
		if (item.audio_codec && item.audio_codec !== 'N/A') secondary.push(item.audio_codec);
		if (secondary.length) {
			var badges = element('div', 'sc-specs-secondary');
			secondary.forEach(function (codec) { badges.appendChild(element('span', 'sc-spec-badge', codec)); });
			info.appendChild(badges);
		}
		return info.children.length ? info : element('span', 'sc-spec-empty', 'No media data');
	}

	function renderRow(item) {
		var tr = element('tr');
		var movieTd = element('td', 'sc-col-movie');
		var movieCell = element('div', 'sc-stream-cell');
		var thumb = element('div', 'sc-movie-thumb');
		if (showImages && clean(item.image)) {
			var img = element('img');
			img.src = 'resize?maxh=72&maxw=48&url=' + encodeURIComponent(clean(item.image));
			img.alt = '';
			img.loading = 'lazy';
			thumb.appendChild(img);
		} else {
			thumb.appendChild(element('i', 'fe-film'));
		}
		movieCell.appendChild(thumb);
		var identity = element('div', 'sc-table-identity');
		var name = clean(item.name) || 'Untitled movie';
		var title = canEdit ? element('a', '', name) : element('strong', '', name);
		if (canEdit) title.href = 'movie?id=' + encodeURIComponent(item.id);
		identity.appendChild(title);
		var detail = '#' + (item.display_id || item.id);
		if (item.year) detail += ' • ' + clean(item.year);
		if (item.rating !== null && item.rating !== undefined) detail += ' • ★ ' + Number(item.rating).toFixed(1);
		if (item.category) detail += ' • ' + clean(item.category);
		identity.appendChild(element('small', '', detail));
		movieCell.appendChild(identity);
		movieTd.appendChild(movieCell);
		tr.appendChild(movieTd);

		tr.appendChild(element('td', 'sc-col-server sc-table-secondary', clean(item.server) || 'No server selected'));
		var stateTd = element('td', 'sc-col-status');
		stateTd.appendChild(element('span', 'sc-row-status ' + statusClass(item.status), statusLabel(item)));
		tr.appendChild(stateTd);
		var connections = Number(item.connections || 0);
		var connectionTd = element('td', 'sc-col-conn sc-table-center');
		var connectionNode = element(canViewConnections && connections > 0 ? 'a' : 'span', 'sc-connection-link', String(connections));
		if (connectionNode.tagName === 'A') connectionNode.href = 'live_connections?stream_id=' + encodeURIComponent(item.id);
		connectionTd.appendChild(connectionNode);
		tr.appendChild(connectionTd);
		var infoTd = element('td', 'sc-col-info');
		infoTd.appendChild(buildMediaInfo(item));
		tr.appendChild(infoTd);

		var actionsTd = element('td', 'sc-col-actions sc-table-actions');
		if (canPlay && item.can_play) {
			var play = actionControl('button', 'Play ' + name, 'fe-play', 'sc-row-play');
			play.addEventListener('click', function () { openPlayer(item); });
			actionsTd.appendChild(play);
		}
		if (canEdit || (canPlay && item.can_play)) {
			var menu = actionControl('button', 'Actions for ' + name, 'fe-more-vertical', 'sc-action-menu-trigger');
			menu.addEventListener('click', function (event) {
				event.stopPropagation();
				openActionMenu(item, menu);
			});
			actionsTd.appendChild(menu);
		}
		tr.appendChild(actionsTd);
		return tr;
	}

	function addStateRow(message, isError) {
		rows.replaceChildren();
		var tr = element('tr');
		var td = element('td', 'sc-table-state' + (isError ? ' is-error' : ''), message);
		td.colSpan = 6;
		tr.appendChild(td);
		rows.appendChild(tr);
	}

	function load() {
		var sequence = ++requestSequence;
		var pageSize = Number(entries.value) || 25;
		var params = new URLSearchParams({
			draw: String(sequence), id: 'movies', view: 'streamcreed', start: String((page - 1) * pageSize), length: String(pageSize),
			'search[value]': search.value.trim(), server: server.value, category: category.value, filter: status.value,
			video: video.value, audio: audio.value, resolution: resolution.value,
			'order[0][column]': '0', 'order[0][dir]': 'desc'
		});
		addStateRow('Loading movies…', false);
		fetch(endpoint + '?' + params.toString(), { credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
			.then(function (response) { if (!response.ok) throw new Error('Request failed'); return response.json(); })
			.then(function (data) {
				if (sequence !== requestSequence) return;
				var movies = Array.isArray(data.data) ? data.data : [];
				total = Number(data.recordsFiltered || 0);
				rows.replaceChildren();
				if (movies.length) movies.forEach(function (item) { rows.appendChild(renderRow(item)); });
				else addStateRow('No movies match these filters.', false);
				var pages = Math.max(1, Math.ceil(total / pageSize));
				var first = total ? ((page - 1) * pageSize) + 1 : 0;
				var last = Math.min(page * pageSize, total);
				range.textContent = 'Showing ' + first.toLocaleString() + '–' + last.toLocaleString() + ' of ' + total.toLocaleString();
				pageLabel.textContent = 'Page ' + page.toLocaleString() + ' of ' + pages.toLocaleString();
				previous.disabled = page <= 1;
				next.disabled = page >= pages;
			}).catch(function () {
				if (sequence !== requestSequence) return;
				range.textContent = 'Unable to load movies';
				pageLabel.textContent = 'Page 1';
				previous.disabled = true;
				next.disabled = true;
				addStateRow('Movies could not be loaded. Please refresh.', true);
			});
	}

	search.addEventListener('input', function () {
		clearTimeout(searchTimer);
		searchTimer = window.setTimeout(function () { page = 1; load(); }, 250);
	});
	[server, category, status, video, audio, resolution, entries].forEach(function (control) {
		control.addEventListener('change', function () { page = 1; load(); });
	});
	previous.addEventListener('click', function () { if (page > 1) { page -= 1; load(); } });
	next.addEventListener('click', function () {
		var pageSize = Number(entries.value) || 25;
		if (page * pageSize < total) { page += 1; load(); }
	});
	document.addEventListener('click', function (event) { if (!actionMenu.hidden && !actionMenu.contains(event.target)) closeActionMenu(); });
	window.addEventListener('resize', closeActionMenu);
	window.addEventListener('scroll', closeActionMenu, true);
	if (playerClose && playerDialog) playerClose.addEventListener('click', function () { playerDialog.close(); });
	if (playerDialog) {
		playerDialog.addEventListener('close', function () { if (playerFrame) playerFrame.src = 'about:blank'; if (playerScaler) playerScaler.classList.remove('is-loading'); });
		playerDialog.addEventListener('click', function (event) { if (event.target === playerDialog) playerDialog.close(); });
	}
	load();
}());
