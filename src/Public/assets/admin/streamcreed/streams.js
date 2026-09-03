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

	// Player dialog (Legacy UI Magnific-Popup Style)
	var playerDialog = document.querySelector('[data-sc-player-dialog]');
	var playerScaler = playerDialog ? playerDialog.querySelector('[data-sc-player-scaler]') : null;
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

	// EPG schedule dialog
	var epgDialog = document.querySelector('[data-sc-epg-dialog]');
	var epgTitle = epgDialog ? epgDialog.querySelector('[data-sc-epg-title]') : null;
	var epgRows = epgDialog ? epgDialog.querySelector('[data-sc-epg-rows]') : null;
	var epgClose = epgDialog ? epgDialog.querySelector('[data-sc-epg-close]') : null;

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

			if (item.has_epg) {
				actionMenu.appendChild(menuAction('View EPG', 'fe-calendar', function () {
					openEPG(item);
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

		// 1. Status badge & restarts pill
		var statusTd = tr.querySelector('.sc-col-status');
		if (statusTd) {
			var statusCell = statusTd.querySelector('.sc-status-cell');
			if (statusCell) {
				var statusBadge = statusCell.querySelector('[data-sc-status]');
				if (statusBadge) {
					statusBadge.className = 'sc-row-status ' + statusClass(current.status);
					statusBadge.textContent = label;
				}
				var rBtn = statusCell.querySelector('.sc-restarts-pill');
				var restarts = Number(current.restarts || 0);
				if (restarts > 0 && Number(current.status) === 1) {
					var level = current.failures_level || (restarts <= 2 ? 'success' : (restarts <= 4 ? 'info' : (restarts <= 144 ? 'warning' : 'danger')));
					if (!rBtn) {
						rBtn = element('button', 'sc-restarts-pill is-' + level);
						rBtn.type = 'button';
						rBtn.title = restarts + ' restart' + (restarts === 1 ? '' : 's') + ' (Click to view logs)';
						rBtn.appendChild(element('i', restarts > 2 ? 'fe-alert-triangle' : 'fe-check'));
						rBtn.appendChild(document.createTextNode(' ' + restarts));
						rBtn.addEventListener('click', function (e) {
							e.stopPropagation();
							openFailures(current);
						});
						statusCell.appendChild(rBtn);
					} else {
						rBtn.className = 'sc-restarts-pill is-' + level;
						rBtn.title = restarts + ' restart' + (restarts === 1 ? '' : 's') + ' (Click to view logs)';
						rBtn.replaceChildren(element('i', restarts > 2 ? 'fe-alert-triangle' : 'fe-check'), document.createTextNode(' ' + restarts));
					}
				} else if (rBtn) {
					rBtn.remove();
				}
			}
		}

		// 2. Server name & IP
		var serverTd = tr.querySelector('[data-sc-server]');
		if (serverTd && current.server) {
			var sCell = serverTd.querySelector('.sc-server-cell');
			if (sCell) {
				var sName = sCell.querySelector('.sc-server-name');
				if (sName) sName.textContent = stripHtml(current.server) || '—';
				var sIp = sCell.querySelector('.sc-server-ip');
				if (current.server_ip) {
					if (sIp) {
						sIp.textContent = current.server_ip;
					} else {
						sCell.appendChild(element('span', 'sc-server-ip', current.server_ip));
					}
				} else if (sIp) {
					sIp.remove();
				}
			}
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
			var upSpan = uptimeTd.querySelector('.sc-uptime-text');
			if (upSpan) {
				upSpan.textContent = upText;
			} else {
				uptimeTd.textContent = upText;
			}
		}

		// 5. Stream Info
		var infoTd = tr.querySelector('[data-sc-streaminfo]');
		if (infoTd) {
			infoTd.replaceChildren(buildStreamInfo(current));
		}

		// 6. If action menu is open for this row, refresh its buttons
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
		playerFrame.src = './player?type=live&id=' + encodeURIComponent(item.id);
		if (typeof playerDialog.showModal === 'function') {
			playerDialog.showModal();
		} else {
			playerDialog.setAttribute('open', '');
		}
	}

	function closePlayer() {
		if (!playerDialog) return;
		if (playerFrame) playerFrame.src = '';
		if (typeof playerDialog.close === 'function' && playerDialog.open) {
			playerDialog.close();
		} else {
			playerDialog.removeAttribute('open');
		}
	}

	if (playerClose) {
		playerClose.addEventListener('click', function (event) {
			event.stopPropagation();
			closePlayer();
		});
	}

	if (playerDialog) {
		playerDialog.addEventListener('cancel', function () {
			if (playerFrame) playerFrame.src = '';
		});

		// Close when clicking anywhere on the dark backdrop outside the video frame
		playerDialog.addEventListener('click', function (event) {
			if (playerScaler) {
				var rect = playerScaler.getBoundingClientRect();
				var isInside = (
					rect.top <= event.clientY &&
					event.clientY <= rect.bottom &&
					rect.left <= event.clientX &&
					event.clientX <= rect.right
				);
				if (!isInside) {
					closePlayer();
				}
			} else if (event.target === playerDialog) {
				closePlayer();
			}
		});
	}

	// Expose legacy player() globally for any legacy scripts or shortcuts
	window.player = function (id) {
		var item = currentStreams[String(id)] || { id: id };
		openPlayer(item);
	};

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

	function openEPG(item) {
		if (!epgDialog) return;
		var title = item.stream_display_name || stripHtml(item.name) || ('Stream #' + item.id);
		if (epgTitle) epgTitle.textContent = title + ' — EPG Schedule';
		if (epgRows) {
			epgRows.replaceChildren();
			var tr = element('tr');
			var td = element('td', 'sc-table-state', 'Loading EPG schedule…');
			td.colSpan = 3;
			tr.appendChild(td);
			epgRows.appendChild(tr);
		}
		epgDialog.showModal();

		fetch(endpoint + '?id=epg_modal&stream_id=' + encodeURIComponent(item.id), {
			credentials: 'same-origin',
			headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
		}).then(function (res) {
			if (!res.ok) throw new Error('Failed to load EPG');
			return res.json();
		}).then(function (data) {
			if (!epgRows) return;
			epgRows.replaceChildren();
			var items = Array.isArray(data.data) ? data.data : [];
			if (!items.length) {
				var emptyTr = element('tr');
				var emptyTd = element('td', 'sc-table-state', 'No EPG schedule data available for this stream.');
				emptyTd.colSpan = 3;
				emptyTr.appendChild(emptyTd);
				epgRows.appendChild(emptyTr);
				return;
			}
			items.forEach(function (row) {
				var rtr = element('tr');
				rtr.appendChild(element('td', 'sc-table-secondary', row[0] || '—'));
				rtr.appendChild(element('td', '', stripHtml(row[1] || '—')));
				rtr.appendChild(element('td', 'sc-table-secondary', stripHtml(row[2] || '—')));
				epgRows.appendChild(rtr);
			});
		}).catch(function () {
			if (!epgRows) return;
			epgRows.replaceChildren();
			var errTr = element('tr');
			var errTd = element('td', 'sc-table-state is-error', 'Failed to load EPG schedule.');
			errTd.colSpan = 3;
			errTr.appendChild(errTd);
			epgRows.appendChild(errTr);
		});
	}

	if (epgClose && epgDialog) {
		epgClose.addEventListener('click', function () { epgDialog.close(); });
	}
	if (epgDialog) {
		epgDialog.addEventListener('click', function (e) {
			if (e.target === epgDialog) epgDialog.close();
		});
	}

	function buildStreamInfo(item) {
		var statusNum = Number(item.status);
		var hasTechInfo = (item.bitrate > 0 || (item.width && item.width !== '?') || (item.video_codec && item.video_codec !== 'N/A'));
		if ((statusNum === 1 || statusNum === 4 || hasTechInfo) && (item.bitrate || item.width || item.video_codec)) {
			var specs = element('div', 'sc-stream-specs');

			// Line 1: Primary specs (Resolution • Bitrate • FPS)
			var pLine = element('div', 'sc-specs-primary');
			var res = (item.width && item.height && item.width !== '?') ? (item.width + '×' + item.height) : (item.width || '');
			if (res) {
				pLine.appendChild(element('span', 'sc-spec-res', res));
			}
			if (item.bitrate) {
				if (res) pLine.appendChild(element('span', 'sc-spec-sep', '•'));
				pLine.appendChild(element('span', 'sc-spec-bitrate', Number(item.bitrate).toLocaleString() + ' Kbps'));
			}
			if (item.fps && item.fps !== '--') {
				if (res || item.bitrate) pLine.appendChild(element('span', 'sc-spec-sep', '•'));
				pLine.appendChild(element('span', 'sc-spec-fps', item.fps));
			}
			specs.appendChild(pLine);

			// Line 2: Secondary badges (Codecs & Speed)
			var sLine = element('div', 'sc-specs-secondary');
			if (item.video_codec && item.video_codec !== 'N/A') {
				sLine.appendChild(element('span', 'sc-spec-badge', item.video_codec));
			}
			if (item.audio_codec && item.audio_codec !== 'N/A') {
				sLine.appendChild(element('span', 'sc-spec-badge', item.audio_codec));
			}
			if (item.speed && item.speed !== '1x') {
				sLine.appendChild(element('span', 'sc-spec-badge', item.speed));
			}
			if (sLine.children.length > 0) {
				specs.appendChild(sLine);
			}
			return specs;
		}

		return element('span', 'sc-spec-empty', 'No stream data');
	}

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

		// 1. Stream column (Logo + Name + #ID • Category)
		var streamTd = element('td', 'sc-col-stream');
		var streamCell = element('div', 'sc-stream-cell');

		var thumbWrap = element(item.icon && item.icon.trim() ? 'a' : 'div', 'sc-stream-thumb-wrap');
		if (item.icon && item.icon.trim()) {
			thumbWrap.href = 'javascript:void(0);';
			thumbWrap.title = 'Logo preview';
			var img = element('img', 'sc-stream-thumb');
			img.src = 'resize?maxw=96&maxh=32&url=' + encodeURIComponent(item.icon.trim());
			img.alt = '';
			img.loading = 'lazy';
			thumbWrap.appendChild(img);
			thumbWrap.addEventListener('click', function (e) {
				e.stopPropagation();
				if (playerDialog && playerFrame) {
					playerFrame.src = 'resize?maxw=512&maxh=512&url=' + encodeURIComponent(item.icon.trim());
					playerDialog.showModal();
				}
			});
		} else {
			thumbWrap.appendChild(element('i', 'fe-tv sc-stream-thumb-placeholder'));
		}
		streamCell.appendChild(thumbWrap);

		var metaDiv = element('div', 'sc-stream-meta');
		var streamName = stripHtml(item.stream_display_name || item.name) || 'Untitled stream';
		var title;
		if (canEdit) {
			title = element('a', 'sc-stream-name', streamName);
			title.href = 'stream?id=' + encodeURIComponent(item.id);
		} else {
			title = element('span', 'sc-stream-name', streamName);
		}
		metaDiv.appendChild(title);

		var subText = '#' + (item.display_id || item.id);
		if (item.category) {
			subText += ' • ' + stripHtml(item.category);
		}
		metaDiv.appendChild(element('span', 'sc-stream-sub', subText));
		streamCell.appendChild(metaDiv);

		streamTd.appendChild(streamCell);
		tr.appendChild(streamTd);

		// 2. Server column (Server Name + IP/Host)
		var serverTd = element('td', 'sc-col-server sc-table-secondary');
		serverTd.setAttribute('data-sc-server', '');
		var sCell = element('div', 'sc-server-cell');
		sCell.appendChild(element('span', 'sc-server-name', stripHtml(item.server) || '—'));
		if (item.server_ip) {
			sCell.appendChild(element('span', 'sc-server-ip', item.server_ip));
		}
		serverTd.appendChild(sCell);
		tr.appendChild(serverTd);

		// 3. Status column (Status Badge + Restarts Warning)
		var label = resolveStatusLabel(item);
		var statusTd = element('td', 'sc-col-status');
		var statusCell = element('div', 'sc-status-cell');
		var statusBadge = element('span', 'sc-row-status ' + statusClass(item.status), label);
		statusBadge.setAttribute('data-sc-status', '');
		statusCell.appendChild(statusBadge);

		var restarts = Number(item.restarts || 0);
		if (restarts > 0 && Number(item.status) === 1) {
			var level = item.failures_level || (restarts <= 2 ? 'success' : (restarts <= 4 ? 'info' : (restarts <= 144 ? 'warning' : 'danger')));
			var rBtn = element('button', 'sc-restarts-pill is-' + level);
			rBtn.type = 'button';
			rBtn.title = restarts + ' restart' + (restarts === 1 ? '' : 's') + ' (Click to view logs)';
			rBtn.appendChild(element('i', restarts > 2 ? 'fe-alert-triangle' : 'fe-check'));
			rBtn.appendChild(document.createTextNode(' ' + restarts));
			rBtn.addEventListener('click', function (e) {
				e.stopPropagation();
				openFailures(item);
			});
			statusCell.appendChild(rBtn);
		}
		statusTd.appendChild(statusCell);
		tr.appendChild(statusTd);

		// 4. Connections column
		var connTd = element('td', 'sc-col-conn sc-table-center');
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
		var uptimeTd = element('td', 'sc-col-uptime sc-table-secondary');
		uptimeTd.setAttribute('data-sc-uptime', '');
		var upText = resolveUptime(item, label);
		uptimeTd.appendChild(element('span', 'sc-uptime-text', upText));
		tr.appendChild(uptimeTd);

		// 6. Stream Info column
		var infoTd = element('td', 'sc-col-info');
		infoTd.setAttribute('data-sc-streaminfo', '');
		infoTd.appendChild(buildStreamInfo(item));
		tr.appendChild(infoTd);

		// 7. Actions column (Play, EPG, 3-dots)
		var actionTd = element('td', 'sc-col-actions sc-table-actions');


		if (item.has_epg || item.epg_status === 'has_data' || item.epg_status === 'assigned') {
			var epgBtn = element('button', 'sc-row-action sc-row-epg' + (item.epg_status === 'assigned' ? ' is-assigned' : ''));
			epgBtn.type = 'button';
			epgBtn.title = item.epg_status === 'assigned' ? 'EPG Assigned (Waiting for schedule data)' : 'View EPG schedule';
			epgBtn.setAttribute('aria-label', 'EPG for ' + streamName);
			epgBtn.appendChild(element('i', 'fe-calendar'));
			epgBtn.addEventListener('click', function (event) {
				event.stopPropagation();
				openEPG(item);
			});
			actionTd.appendChild(epgBtn);
		}

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
