(function () {
	'use strict';

	var root = document.querySelector('[data-sc-streams]');
	if (!root) return;

	var search = root.querySelector('[data-sc-stream-search]');
	var server = root.querySelector('[data-sc-stream-server]');
	var category = root.querySelector('[data-sc-stream-category]');
	var statusFilter = root.querySelector('[data-sc-stream-status]');
	var entries = root.querySelector('[data-sc-stream-entries]');
	var rows = root.querySelector('[data-sc-stream-rows]');
	var range = root.querySelector('[data-sc-stream-range]');
	var pageLabel = root.querySelector('[data-sc-stream-page]');
	var previous = root.querySelector('[data-sc-stream-previous]');
	var next = root.querySelector('[data-sc-stream-next]');
	var endpoint = root.getAttribute('data-endpoint') || 'table';

	var canEdit = root.getAttribute('data-can-edit') === '1';
	var canPlay = root.getAttribute('data-can-play') === '1';
	var canFingerprint = root.getAttribute('data-can-fingerprint') === '1';
	var canViewConnections = root.getAttribute('data-can-view-connections') === '1';

	var page = 1;
	var total = 0;
	var requestSequence = 0;
	var searchTimer;
	var actionBusy = false;

	var playerDialog = document.querySelector('[data-sc-player-dialog]');
	var playerTitle = playerDialog ? playerDialog.querySelector('[data-sc-player-title]') : null;
	var playerFrame = playerDialog ? playerDialog.querySelector('[data-sc-player-frame]') : null;
	var playerClose = playerDialog ? playerDialog.querySelector('[data-sc-player-close]') : null;

	var fingerprintDialog = document.querySelector('[data-sc-fingerprint-dialog]');
	var fingerprintItem = null;

	var actionMenu = element('div', 'sc-row-action-menu');
	var activeActionTrigger = null;
	actionMenu.hidden = true;
	document.body.appendChild(actionMenu);

	function element(tag, className, text) {
		var node = document.createElement(tag);
		if (className) node.className = className;
		if (typeof text !== 'undefined') node.textContent = text;
		return node;
	}

	function stripHtml(html) {
		if (!html) return '';
		if (typeof html !== 'string') return String(html);
		if (html.indexOf('<') === -1) return html.trim();
		var tmp = document.createElement('div');
		tmp.innerHTML = html;
		return (tmp.textContent || tmp.innerText || '').trim();
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

	var STATUS_LABELS = {
		'-1': 'No servers',
		'0': 'Stopped',
		'1': 'Online',
		'2': 'Starting',
		'3': 'Down',
		'4': 'On demand',
		'5': 'Direct source',
		'6': 'Creating…',
		'7': 'Direct stream'
	};

	function resolveStatusLabel(item) {
		var code = String(item.status);
		if (STATUS_LABELS[code]) {
			return STATUS_LABELS[code];
		}
		var raw = stripHtml(item.statusLabel);
		return raw || 'Unknown';
	}

	function statusClass(status) {
		var n = Number(status);
		if (n === 1) return 'is-active';
		if (n === 2) return 'is-warning';
		if (n === 3) return 'is-banned';
		if (n === 4 || n === 5 || n === 6 || n === 7) return 'is-neutral';
		return 'is-disabled';
	}

	function resolveUptime(item, statusLabel) {
		var uptime = stripHtml(item.uptime);
		if (!uptime || uptime === statusLabel || uptime.toUpperCase() === 'STOPPED' || Number(item.status) !== 1) {
			return '—';
		}
		return uptime;
	}

	function actionDetails(action, item) {
		var streamName = stripHtml(item.name) || ('Stream #' + item.id);
		if (action === 'delete') return { confirm: 'Are you sure you want to delete "' + streamName + '"?', success: 'Stream has been deleted.' };
		if (action === 'stop') return { confirm: 'Are you sure you want to stop "' + streamName + '"?', success: 'Stream has been stopped.' };
		if (action === 'start') return { confirm: null, success: 'Stream has been started.' };
		if (action === 'restart') return { confirm: null, success: 'Stream has been restarted.' };
		if (action === 'purge') return { confirm: 'Kill all active connections for "' + streamName + '"?', success: 'Active connections have been killed.' };
		return { confirm: null, success: 'Action completed successfully.' };
	}

	function runStreamAction(action, item) {
		var details = actionDetails(action, item);
		if (actionBusy || (details.confirm && !window.confirm(details.confirm))) return;
		actionBusy = true;

		var serverId = item.serverId !== undefined && item.serverId !== null ? item.serverId : -1;
		var url = './api?action=stream&sub=' + encodeURIComponent(action) +
			'&stream_id=' + encodeURIComponent(item.id) +
			'&server_id=' + encodeURIComponent(serverId);

		fetch(url, {
			credentials: 'same-origin',
			headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
		}).then(function (response) {
			if (!response.ok) throw new Error('Request failed');
			return response.json();
		}).then(function (data) {
			if (data.result !== true) throw new Error('Action failed');
			toast(details.success, false);
			load();
		}).catch(function () {
			toast('An error occurred while processing your request.', true);
		}).finally(function () {
			actionBusy = false;
		});
	}

	function actionControl(tag, label, icon, className) {
		var control = element(tag, 'sc-row-action sc-icon-action' + (className ? ' ' + className : ''));
		var glyph = element('i', icon);
		glyph.setAttribute('aria-hidden', 'true');
		control.appendChild(glyph);
		control.appendChild(element('span', 'sc-icon-action-label', label));
		control.title = label;
		control.setAttribute('aria-label', label);
		if (tag === 'button') control.type = 'button';
		return control;
	}

	function closeActionMenu() {
		actionMenu.hidden = true;
		actionMenu.replaceChildren();
		activeActionTrigger = null;
	}

	function menuAction(label, icon, callback, className) {
		var control = actionControl('button', label, icon, className);
		control.classList.add('sc-menu-action');
		control.addEventListener('click', function () {
			closeActionMenu();
			callback();
		});
		return control;
	}

	function openActionMenu(item, trigger) {
		closeActionMenu();
		activeActionTrigger = trigger;

		if (canPlay) {
			actionMenu.appendChild(menuAction('Play', 'fe-play', function () {
				openPlayer(item);
			}));
		}

		if (canEdit) {
			var statusNum = Number(item.status);
			if (statusNum === 1) {
				actionMenu.appendChild(menuAction('Stop', 'fe-pause-circle', function () {
					runStreamAction('stop', item);
				}));
			} else {
				actionMenu.appendChild(menuAction('Start', 'fe-play-circle', function () {
					runStreamAction('start', item);
				}));
			}

			actionMenu.appendChild(menuAction('Restart', 'fe-rotate-cw', function () {
				runStreamAction('restart', item);
			}));

			if (item.connections > 0) {
				actionMenu.appendChild(menuAction('Kill connections', 'fe-zap-off', function () {
					runStreamAction('purge', item);
				}, 'is-warning'));
			}

			if (canFingerprint && item.connections > 0) {
				actionMenu.appendChild(menuAction('Fingerprint', 'fe-crosshair', function () {
					openFingerprint(item);
				}));
			}

			actionMenu.appendChild(element('div', 'sc-action-menu-divider'));

			var editLink = actionControl('a', 'Edit', 'fe-edit-2');
			editLink.classList.add('sc-menu-action');
			editLink.href = 'stream?id=' + encodeURIComponent(item.id);
			actionMenu.appendChild(editLink);

			actionMenu.appendChild(menuAction('Delete', 'fe-trash-2', function () {
				runStreamAction('delete', item);
			}, 'is-danger'));
		}

		actionMenu.hidden = false;
		var rect = trigger.getBoundingClientRect();
		var menuRect = actionMenu.getBoundingClientRect();
		var top = rect.bottom + 6;
		if (top + menuRect.height > window.innerHeight - 8) {
			top = rect.top - menuRect.height - 6;
		}
		actionMenu.style.top = Math.max(8, top) + 'px';
		actionMenu.style.left = Math.max(8, Math.min(rect.right - menuRect.width, window.innerWidth - menuRect.width - 8)) + 'px';
	}

	function openPlayer(item) {
		if (!playerDialog || !playerFrame) return;
		var title = stripHtml(item.name) || ('Stream #' + item.id);
		if (playerTitle) playerTitle.textContent = title;
		playerFrame.src = './player?type=live&id=' + encodeURIComponent(item.id);
		playerDialog.showModal();
	}

	function closePlayer() {
		if (!playerDialog) return;
		if (playerFrame) playerFrame.src = '';
		playerDialog.close();
	}

	if (playerClose) {
		playerClose.addEventListener('click', closePlayer);
	}
	if (playerDialog) {
		playerDialog.addEventListener('cancel', function () {
			if (playerFrame) playerFrame.src = '';
		});
	}

	function openFingerprint(item) {
		if (!fingerprintDialog) return;
		fingerprintItem = item;
		fingerprintDialog.showModal();
	}

	function sendFingerprint() {
		if (!fingerprintDialog || !fingerprintItem) return;
		var type = fingerprintDialog.querySelector('[data-sc-fingerprint-type]').value;
		var messageInput = fingerprintDialog.querySelector('[data-sc-fingerprint-message]');
		var message = messageInput ? messageInput.value.trim() : '';

		if (type === '3' && !message) {
			toast('Enter a custom fingerprint message.', true);
			return;
		}

		var data = {
			id: fingerprintItem.id,
			stream: true,
			font_size: fingerprintDialog.querySelector('[data-sc-fingerprint-size]').value,
			font_color: fingerprintDialog.querySelector('[data-sc-fingerprint-color]').value,
			message: type === '3' ? message : '',
			type: type,
			xy_offset: fingerprintDialog.querySelector('[data-sc-fingerprint-x]').value + 'x' + fingerprintDialog.querySelector('[data-sc-fingerprint-y]').value
		};

		fetch('./api?action=fingerprint&data=' + encodeURIComponent(JSON.stringify(data)), {
			credentials: 'same-origin',
			headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
		}).then(function (response) {
			if (!response.ok) throw new Error('Request failed');
			return response.json();
		}).then(function (result) {
			if (result.result !== true) throw new Error('Fingerprint failed');
			fingerprintDialog.close();
			toast('Fingerprint signal has been sent to stream viewers.', false);
		}).catch(function () {
			toast('The fingerprint signal could not be sent.', true);
		});
	}

	if (fingerprintDialog) {
		var typeSelect = fingerprintDialog.querySelector('[data-sc-fingerprint-type]');
		var msgWrap = fingerprintDialog.querySelector('[data-sc-fingerprint-message-wrap]');
		if (typeSelect && msgWrap) {
			typeSelect.addEventListener('change', function () {
				msgWrap.hidden = typeSelect.value !== '3';
			});
		}
		var sendBtn = fingerprintDialog.querySelector('[data-sc-fingerprint-send]');
		if (sendBtn) {
			sendBtn.addEventListener('click', sendFingerprint);
		}
	}

	document.addEventListener('click', function (event) {
		if (!actionMenu.hidden && !actionMenu.contains(event.target)) {
			closeActionMenu();
		}
	});
	window.addEventListener('resize', closeActionMenu);
	window.addEventListener('scroll', closeActionMenu, true);

	function addStateRow(message, isError) {
		rows.replaceChildren();
		var tr = element('tr');
		var td = element('td', 'sc-table-state' + (isError ? ' is-error' : ''), message);
		td.colSpan = 7;
		tr.appendChild(td);
		rows.appendChild(tr);
	}

	function renderRow(item) {
		var tr = element('tr');

		// Stream identity column
		var td = element('td');
		var identity = element('div', 'sc-table-identity');
		var streamName = stripHtml(item.name) || 'Untitled stream';
		var title;
		if (canEdit) {
			title = element('a', '', streamName);
			title.href = 'stream?id=' + encodeURIComponent(item.id);
		} else {
			title = element('strong', '', streamName);
		}
		var meta = '#' + item.id;
		if (item.category) {
			meta += ' • ' + stripHtml(item.category);
		}
		identity.appendChild(title);
		identity.appendChild(element('small', '', meta));
		td.appendChild(identity);
		tr.appendChild(td);

		// Server column
		var serverName = stripHtml(item.server) || '—';
		var serverTd = element('td', 'sc-table-secondary', serverName);
		tr.appendChild(serverTd);

		// Status column
		var label = resolveStatusLabel(item);
		var statusTd = element('td');
		statusTd.appendChild(element('span', 'sc-row-status ' + statusClass(item.status), label));
		tr.appendChild(statusTd);

		// Connections column
		var connTd = element('td', 'sc-table-center');
		var connCount = Number(item.connections || 0);
		if (canViewConnections && connCount > 0) {
			var connLink = element('a', 'sc-connection-link', String(connCount));
			connLink.href = 'live_connections?stream_id=' + encodeURIComponent(item.id);
			connTd.appendChild(connLink);
		} else {
			connTd.appendChild(element('span', 'sc-connection-link', String(connCount)));
		}
		tr.appendChild(connTd);

		// Uptime column
		var uptimeTd = element('td', 'sc-table-secondary', resolveUptime(item, label));
		tr.appendChild(uptimeTd);

		// Bitrate column
		var bitrateText = item.bitrate ? item.bitrate + ' Kbps' : '—';
		var bitrateTd = element('td', 'sc-table-secondary', bitrateText);
		tr.appendChild(bitrateTd);

		// Actions column
		var actionTd = element('td', 'sc-table-actions');
		var trigger = element('button', 'sc-row-action sc-action-menu-trigger');
		trigger.type = 'button';
		trigger.title = 'Actions';
		trigger.setAttribute('aria-label', 'Actions for ' + streamName);
		trigger.appendChild(element('i', 'fe-more-vertical'));
		trigger.addEventListener('click', function (event) {
			event.stopPropagation();
			if (activeActionTrigger === trigger) {
				closeActionMenu();
			} else {
				openActionMenu(item, trigger);
			}
		});
		actionTd.appendChild(trigger);
		tr.appendChild(actionTd);

		return tr;
	}

	function load() {
		var sequence = ++requestSequence;
		var pageSize = Number(entries.value) || 25;
		var params = new URLSearchParams({
			draw: String(sequence),
			id: 'streams',
			view: 'streamcreed',
			start: String((page - 1) * pageSize),
			length: String(pageSize),
			'search[value]': search.value.trim(),
			server: server ? server.value : '',
			category: category ? category.value : '',
			filter: statusFilter ? statusFilter.value : '',
			'order[0][column]': '0',
			'order[0][dir]': 'desc'
		});

		addStateRow('Loading streams…', false);

		fetch(endpoint + '?' + params.toString(), {
			credentials: 'same-origin',
			headers: {
				'Accept': 'application/json',
				'X-Requested-With': 'XMLHttpRequest'
			}
		}).then(function (response) {
			if (!response.ok) throw new Error('Request failed');
			return response.json();
		}).then(function (data) {
			if (sequence !== requestSequence) return;
			var items = Array.isArray(data.data) ? data.data : [];
			total = Number(data.recordsFiltered || 0);
			rows.replaceChildren();

			if (!items.length) {
				addStateRow('No live streams match these filters.', false);
			} else {
				items.forEach(function (item) {
					rows.appendChild(renderRow(item));
				});
			}

			var first = total ? ((page - 1) * pageSize) + 1 : 0;
			var last = Math.min(page * pageSize, total);
			var pages = Math.max(1, Math.ceil(total / pageSize));

			range.textContent = 'Showing ' + first.toLocaleString() + '–' + last.toLocaleString() + ' of ' + total.toLocaleString();
			pageLabel.textContent = 'Page ' + page.toLocaleString() + ' of ' + pages.toLocaleString();
			previous.disabled = page <= 1;
			next.disabled = page >= pages;
		}).catch(function () {
			if (sequence !== requestSequence) return;
			total = 0;
			range.textContent = 'Unable to load streams';
			pageLabel.textContent = 'Page 1';
			previous.disabled = true;
			next.disabled = true;
			addStateRow('Live streams could not be loaded. Please refresh.', true);
		});
	}

	search.addEventListener('input', function () {
		clearTimeout(searchTimer);
		searchTimer = setTimeout(function () {
			page = 1;
			load();
		}, 300);
	});

	if (server) {
		server.addEventListener('change', function () {
			page = 1;
			load();
		});
	}

	if (category) {
		category.addEventListener('change', function () {
			page = 1;
			load();
		});
	}

	if (statusFilter) {
		statusFilter.addEventListener('change', function () {
			page = 1;
			load();
		});
	}

	entries.addEventListener('change', function () {
		page = 1;
		load();
	});

	previous.addEventListener('click', function () {
		if (page > 1) {
			page--;
			load();
		}
	});

	next.addEventListener('click', function () {
		var pageSize = Number(entries.value) || 25;
		if (page * pageSize < total) {
			page++;
			load();
		}
	});

	load();
})();
