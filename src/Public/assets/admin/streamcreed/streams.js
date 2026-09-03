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
	var searchTimer = null;
	var actionBusy = false;
	var isPolling = false;
	var pollTimer = null;
	var currentStreams = {};

	// Player dialog
	var playerDialog = document.querySelector('[data-sc-player-dialog]');
	var playerTitle = playerDialog ? playerDialog.querySelector('[data-sc-player-title]') : null;
	var playerFrame = playerDialog ? playerDialog.querySelector('[data-sc-player-frame]') : null;
	var playerClose = playerDialog ? playerDialog.querySelector('[data-sc-player-close]') : null;

	// Fingerprint dialog
	var fingerprintDialog = document.querySelector('[data-sc-fingerprint-dialog]');
	var fingerprintItem = null;

	// Failures / Restarts dialog
	var failuresDialog = document.querySelector('[data-sc-failures-dialog]');
	var failuresTitle = failuresDialog ? failuresDialog.querySelector('[data-sc-failures-title]') : null;
	var failuresRows = failuresDialog ? failuresDialog.querySelector('[data-sc-failures-rows]') : null;
	var failuresClearBtn = failuresDialog ? failuresDialog.querySelector('[data-sc-failures-clear]') : null;
	var failuresCloseBtn = failuresDialog ? failuresDialog.querySelector('[data-sc-failures-close-btn]') : null;
	var failuresCloseX = failuresDialog ? failuresDialog.querySelector('[data-sc-failures-close]') : null;
	var failuresItem = null;

	// Action menu dropdown
	var actionMenu = element('div', 'sc-row-action-menu');
	var activeActionTrigger = null;
	var activeMenuItem = null;
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
		activeMenuItem = null;
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

	function populateActionMenu(item) {
		actionMenu.replaceChildren();

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

			if (Number(item.connections || 0) > 0) {
				actionMenu.appendChild(menuAction('Kill connections', 'fe-zap-off', function () {
					runStreamAction('purge', item);
				}, 'is-warning'));
			}

			if (canFingerprint && Number(item.connections || 0) > 0) {
				actionMenu.appendChild(menuAction('Fingerprint', 'fe-crosshair', function () {
					openFingerprint(item);
				}));
			}

			actionMenu.appendChild(menuAction('Restarts & logs', 'fe-activity', function () {
				openFailures(item);
			}));

			actionMenu.appendChild(element('div', 'sc-action-menu-divider'));

			var editLink = actionControl('a', 'Edit', 'fe-edit-2');
			editLink.classList.add('sc-menu-action');
			editLink.href = 'stream?id=' + encodeURIComponent(item.id);
			actionMenu.appendChild(editLink);

			actionMenu.appendChild(menuAction('Delete', 'fe-trash-2', function () {
				runStreamAction('delete', item);
			}, 'is-danger'));
		}
	}

	function openActionMenu(item, trigger) {
		closeActionMenu();
		activeActionTrigger = trigger;
		activeMenuItem = item;
		populateActionMenu(item);

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

	// Update existing DOM row in-place without rebuilding or flashing
	function updateRowInPlace(item) {
		if (!item || !item.id) return;
		var streamId = String(item.id);
		currentStreams[streamId] = Object.assign(currentStreams[streamId] || {}, item);
		var current = currentStreams[streamId];

		var tr = rows.querySelector('tr[data-stream-id="' + streamId + '"]');
		if (!tr) return;

		var label = resolveStatusLabel(current);

		// 1. Status badge
		var statusBadge = tr.querySelector('[data-sc-status]');
		if (statusBadge) {
			statusBadge.className = 'sc-row-status ' + statusClass(current.status);
			statusBadge.textContent = label;
		}

		// 2. Server name
		var serverTd = tr.querySelector('[data-sc-server]');
		if (serverTd && current.server) {
			var sName = stripHtml(current.server) || '—';
			if (serverTd.textContent !== sName) serverTd.textContent = sName;
		}

		// 3. Connections
		var connWrap = tr.querySelector('[data-sc-connections]');
		if (connWrap) {
			var count = Number(current.connections || 0);
			connWrap.replaceChildren();
			if (canViewConnections && count > 0) {
				var connLink = element('a', 'sc-connection-link', String(count));
				connLink.href = 'live_connections?stream_id=' + encodeURIComponent(current.id);
				connWrap.appendChild(connLink);
			} else {
				connWrap.appendChild(element('span', 'sc-connection-link', String(count)));
			}
		}

		// 4. Uptime
		var uptimeTd = tr.querySelector('[data-sc-uptime]');
		if (uptimeTd) {
			var upText = resolveUptime(current, label);
			if (uptimeTd.textContent !== upText) uptimeTd.textContent = upText;
		}

		// 5. Bitrate
		var bitrateTd = tr.querySelector('[data-sc-bitrate]');
		if (bitrateTd) {
			var bText = current.bitrate ? current.bitrate + ' Kbps' : '—';
			if (bitrateTd.textContent !== bText) bitrateTd.textContent = bText;
		}

		// 6. If action menu is open for this row, refresh its buttons (e.g. Stop vs Start)
		if (!actionMenu.hidden && activeMenuItem && String(activeMenuItem.id) === streamId) {
			populateActionMenu(current);
		}
	}

	function runStreamAction(action, item) {
		var details = actionDetails(action, item);
		if (actionBusy || (details.confirm && !window.confirm(details.confirm))) return;
		actionBusy = true;

		var streamId = String(item.id);

		// Optimistic UI updates — update row immediately without wiping table
		if (action === 'start' || action === 'restart') {
			item.status = 2; // 2 = Starting
			item.uptime = '—';
			updateRowInPlace(item);
		} else if (action === 'stop') {
			item.status = 0; // 0 = Stopped
			item.uptime = '—';
			item.bitrate = 0;
			item.connections = 0;
			updateRowInPlace(item);
		} else if (action === 'purge') {
			item.connections = 0;
			updateRowInPlace(item);
		}

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

			if (action === 'delete') {
				// Remove the row smoothly from DOM
				var tr = rows.querySelector('tr[data-stream-id="' + streamId + '"]');
				if (tr) tr.remove();
				delete currentStreams[streamId];
				if (!rows.querySelector('tr[data-stream-id]')) {
					load();
				}
			} else {
				// Schedule immediate poll in 1.5s to catch status transition
				schedulePoll(1500);
			}
		}).catch(function () {
			toast('An error occurred while processing your request.', true);
			// Trigger poll to restore accurate database state
			schedulePoll(2000);
		}).finally(function () {
			actionBusy = false;
		});
	}

	// Periodic lightweight background polling of visible streams
	function schedulePoll(delayMs) {
		clearTimeout(pollTimer);
		pollTimer = setTimeout(pollStreams, delayMs !== undefined ? delayMs : 5000);
	}

	function pollStreams() {
		if (document.hidden || isPolling || actionBusy) {
			schedulePoll(5000);
			return;
		}

		var trList = rows.querySelectorAll('tr[data-stream-id]');
		if (!trList.length) {
			schedulePoll(5000);
			return;
		}

		var visibleIds = [];
		trList.forEach(function (tr) {
			visibleIds.push(tr.dataset.streamId);
		});

		isPolling = true;
		var url = endpoint + '?id=streams&view=streamcreed&refresh=' + encodeURIComponent(visibleIds.join(','));

		fetch(url, {
			credentials: 'same-origin',
			headers: {
				'Accept': 'application/json',
				'X-Requested-With': 'XMLHttpRequest'
			}
		}).then(function (response) {
			if (!response.ok) throw new Error('Refresh request failed');
			return response.json();
		}).then(function (data) {
			var hasStarting = false;
			if (Array.isArray(data.data)) {
				data.data.forEach(function (updated) {
					updateRowInPlace(updated);
					if (Number(updated.status) === 2) {
						hasStarting = true;
					}
				});
			}
			// If any stream is starting, poll more frequently (2s) so it flips to Online promptly
			schedulePoll(hasStarting ? 2000 : 5000);
		}).catch(function () {
			schedulePoll(8000);
		}).finally(function () {
			isPolling = false;
		});
	}

	// Wake up polling immediately when tab becomes visible again
	document.addEventListener('visibilitychange', function () {
		if (!document.hidden) {
			schedulePoll(300);
		}
	});

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

		var formBody = [];
		for (var key in data) {
			formBody.push(encodeURIComponent(key) + '=' + encodeURIComponent(data[key]));
		}

		fetch('./api?action=fingerprint', {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
				'X-Requested-With': 'XMLHttpRequest'
			},
			body: formBody.join('&')
		}).then(function (response) {
			if (!response.ok) throw new Error('Failed');
			return response.json();
		}).then(function (res) {
			if (res.result === true) {
				toast('Fingerprint sent successfully.', false);
				fingerprintDialog.close();
			} else {
				toast('Could not send fingerprint.', true);
			}
		}).catch(function () {
			toast('Error sending fingerprint.', true);
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

	// Failures / Restarts dialog
	function openFailures(item) {
		if (!failuresDialog) return;
		failuresItem = item;
		if (failuresTitle) {
			failuresTitle.textContent = (stripHtml(item.name) || ('Stream #' + item.id)) + ' — Restarts & Logs';
		}
		if (failuresRows) {
			failuresRows.replaceChildren();
			var tr = element('tr');
			var td = element('td', 'sc-table-state', 'Loading log entries…');
			td.colSpan = 4;
			tr.appendChild(td);
			failuresRows.appendChild(tr);
		}
		failuresDialog.showModal();

		var serverId = item.serverId !== undefined && item.serverId !== null ? item.serverId : -1;
		fetch(endpoint + '?id=failures_modal&stream_id=' + encodeURIComponent(item.id) + '&server_id=' + encodeURIComponent(serverId), {
			credentials: 'same-origin',
			headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
		}).then(function (res) {
			if (!res.ok) throw new Error('Failed to load logs');
			return res.json();
		}).then(function (data) {
			if (!failuresRows) return;
			failuresRows.replaceChildren();
			var items = Array.isArray(data.data) ? data.data : [];
			if (!items.length) {
				var emptyTr = element('tr');
				var emptyTd = element('td', 'sc-table-state', 'No restart or failure logs found for this stream.');
				emptyTd.colSpan = 4;
				emptyTr.appendChild(emptyTd);
				failuresRows.appendChild(emptyTr);
				return;
			}
			items.forEach(function (log) {
				var ltr = element('tr');
				// Column 0: Server
				var sTd = element('td', '', stripHtml(log[0]) || '—');
				ltr.appendChild(sTd);
				// Column 1: Source IP
				var ipTd = element('td', 'sc-table-secondary', log[1] || '—');
				ltr.appendChild(ipTd);
				// Column 2: Status
				var stTd = element('td');
				var rawSt = stripHtml(log[2]) || 'Unknown';
				var badgeClass = 'sc-row-status ';
				if (rawSt.indexOf('START') !== -1) badgeClass += 'is-active';
				else if (rawSt.indexOf('RESTART') !== -1) badgeClass += 'is-warning';
				else if (rawSt.indexOf('FAIL') !== -1) badgeClass += 'is-banned';
				else badgeClass += 'is-disabled';
				stTd.appendChild(element('span', badgeClass, rawSt));
				ltr.appendChild(stTd);
				// Column 3: Date & Time
				var dtTd = element('td', 'sc-table-secondary', log[3] || '—');
				ltr.appendChild(dtTd);

				failuresRows.appendChild(ltr);
			});
		}).catch(function () {
			if (!failuresRows) return;
			failuresRows.replaceChildren();
			var errTr = element('tr');
			var errTd = element('td', 'sc-table-state is-error', 'Failed to load restart logs.');
			errTd.colSpan = 4;
			errTr.appendChild(errTd);
			failuresRows.appendChild(errTr);
		});
	}

	function clearFailures() {
		if (!failuresItem) return;
		fetch('./api?action=clear_failures&id=' + encodeURIComponent(failuresItem.id), {
			credentials: 'same-origin',
			headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
		}).then(function (res) {
			if (!res.ok) throw new Error('Failed');
			return res.json();
		}).then(function (data) {
			if (data.result === true) {
				toast('Stream monitor logs have been cleared.', false);
				if (failuresRows) {
					failuresRows.replaceChildren();
					var tr = element('tr');
					var td = element('td', 'sc-table-state', 'No restart or failure logs found for this stream.');
					td.colSpan = 4;
					tr.appendChild(td);
					failuresRows.appendChild(tr);
				}
			} else {
				toast('Could not clear logs.', true);
			}
		}).catch(function () {
			toast('Error clearing logs.', true);
		});
	}

	if (failuresClearBtn) {
		failuresClearBtn.addEventListener('click', clearFailures);
	}
	if (failuresCloseBtn && failuresDialog) {
		failuresCloseBtn.addEventListener('click', function () { failuresDialog.close(); });
	}
	if (failuresCloseX && failuresDialog) {
		failuresCloseX.addEventListener('click', function () { failuresDialog.close(); });
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
		tr.dataset.streamId = String(item.id);
		currentStreams[String(item.id)] = item;

		// 1. Stream identity column
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

		// 2. Server column
		var serverName = stripHtml(item.server) || '—';
		var serverTd = element('td', 'sc-table-secondary', serverName);
		serverTd.setAttribute('data-sc-server', '');
		tr.appendChild(serverTd);

		// 3. Status column
		var label = resolveStatusLabel(item);
		var statusTd = element('td');
		var statusBadge = element('span', 'sc-row-status ' + statusClass(item.status), label);
		statusBadge.setAttribute('data-sc-status', '');
		statusTd.appendChild(statusBadge);
		tr.appendChild(statusTd);

		// 4. Connections column
		var connTd = element('td', 'sc-table-center');
		connTd.setAttribute('data-sc-connections', '');
		var connCount = Number(item.connections || 0);
		if (canViewConnections && connCount > 0) {
			var connLink = element('a', 'sc-connection-link', String(connCount));
			connLink.href = 'live_connections?stream_id=' + encodeURIComponent(item.id);
			connTd.appendChild(connLink);
		} else {
			connTd.appendChild(element('span', 'sc-connection-link', String(connCount)));
		}
		tr.appendChild(connTd);

		// 5. Uptime column
		var uptimeTd = element('td', 'sc-table-secondary', resolveUptime(item, label));
		uptimeTd.setAttribute('data-sc-uptime', '');
		tr.appendChild(uptimeTd);

		// 6. Bitrate column
		var bitrateText = item.bitrate ? item.bitrate + ' Kbps' : '—';
		var bitrateTd = element('td', 'sc-table-secondary', bitrateText);
		bitrateTd.setAttribute('data-sc-bitrate', '');
		tr.appendChild(bitrateTd);

		// 7. Actions column
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
		clearTimeout(pollTimer);
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
			currentStreams = {};

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

			// Start periodic background polling for visible streams
			schedulePoll(5000);
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
