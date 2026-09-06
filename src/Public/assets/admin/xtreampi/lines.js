(function () {
	'use strict';

	var root = document.querySelector('[data-sc-subscriptions]');
	if (!root) return;

	var search = root.querySelector('[data-sc-subscription-search]');
	var filter = root.querySelector('[data-sc-subscription-filter]');
	var ownerFilter = root.querySelector('[data-sc-owner-filter]');
	var ownerSearch = root.querySelector('[data-sc-owner-search]');
	var ownerId = root.querySelector('[data-sc-owner-id]');
	var ownerOptions = root.querySelector('[data-sc-owner-options]');
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
	var ownerTimer;
	var actionBusy = false;
	var playlistDialog = document.querySelector('[data-sc-playlist-dialog]');
	var notesDialog = document.querySelector('[data-sc-notes-dialog]');
	var whatsappDialog = document.querySelector('[data-sc-whatsapp-dialog]');
	var fingerprintDialog = document.querySelector('[data-sc-fingerprint-dialog]');
	var playlistItem = null;
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

	function toast(message, isError) {
		var item = element('div', 'sc-toast' + (isError ? ' is-error' : ''), message);
		document.body.appendChild(item);
		window.requestAnimationFrame(function () { item.classList.add('is-visible'); });
		window.setTimeout(function () {
			item.classList.remove('is-visible');
			window.setTimeout(function () { item.remove(); }, 180);
		}, 2600);
	}

	function actionDetails(action, item) {
		if (action === 'delete') return { confirm: 'Are you sure you want to delete this subscription?', success: 'Subscription has been deleted.' };
		if (action === 'kill') return { confirm: 'Kill all active connections for ' + (item.username || 'this subscription') + '?', success: 'Active connections have been killed.' };
		if (action === 'enable') return { success: 'Subscription has been enabled.' };
		if (action === 'disable') return { success: 'Subscription has been disabled.' };
		if (action === 'ban') return { success: 'Subscription has been banned.' };
		return { success: 'Subscription has been unbanned.' };
	}

	function runAction(action, item) {
		var details = actionDetails(action, item);
		if (actionBusy || (details.confirm && !window.confirm(details.confirm))) return;
		actionBusy = true;
		fetch('./api?action=line&sub=' + encodeURIComponent(action) + '&user_id=' + encodeURIComponent(item.id), {
			credentials: 'same-origin',
			headers: { 'Accept': 'application/json, text/javascript, */*; q=0.01', 'X-Requested-With': 'XMLHttpRequest' }
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

	function addStateRow(message, isError) {
		rows.replaceChildren();
		var row = element('tr');
		var cell = element('td', 'sc-table-state' + (isError ? ' is-error' : ''), message);
		cell.colSpan = 12;
		row.appendChild(cell);
		rows.appendChild(row);
	}

	function loadOwners() {
		fetch('./api?search=' + encodeURIComponent(ownerSearch.value.trim()) + '&action=reguserlist&page=1', {
			credentials: 'same-origin',
			headers: { 'Accept': 'application/json, text/javascript, */*; q=0.01', 'X-Requested-With': 'XMLHttpRequest' }
		}).then(function (response) {
			if (!response.ok) throw new Error('Request failed');
			return response.json();
		}).then(function (data) {
			ownerOptions.replaceChildren();
			(data.items || []).forEach(function (item) {
				var option = element('button', '', item.text || item.username || String(item.id));
				option.type = 'button';
				option.addEventListener('click', function () {
					ownerId.value = item.id;
					ownerSearch.value = option.textContent;
					ownerOptions.hidden = true;
					page = 1;
					load();
				});
				ownerOptions.appendChild(option);
			});
			ownerOptions.hidden = ownerOptions.childElementCount === 0;
		}).catch(function () { ownerOptions.hidden = true; });
	}

	function statusBadge(item) {
		var allowed = ['active', 'disabled', 'banned', 'expired'];
		var status = allowed.indexOf(item.status) === -1 ? 'disabled' : item.status;
		return element('span', 'sc-row-status is-' + status, item.statusLabel || status);
	}

	function stateIndicator(active, label) {
		var indicator = element('span', 'sc-state-indicator' + (active ? ' is-active' : ''), '');
		indicator.title = label + ': ' + (active ? 'Yes' : 'No');
		indicator.setAttribute('aria-label', indicator.title);
		return indicator;
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
		control.addEventListener('click', function () { closeActionMenu(); callback(); });
		return control;
	}

	function openActionMenu(item, trigger) {
		closeActionMenu();
		activeActionTrigger = trigger;
		if (canEdit) {
			var edit = actionControl('a', 'Edit', 'fe-edit-2');
			edit.classList.add('sc-menu-action');
			edit.href = 'line?id=' + encodeURIComponent(item.id);
			actionMenu.appendChild(edit);
		}
		actionMenu.appendChild(menuAction('Playlist', 'fe-download', function () { openPlaylist(item); }));
		if (item.adminNotes || item.resellerNotes) actionMenu.appendChild(menuAction('Notes', 'fe-file-text', function () { openNotes(item); }));
		actionMenu.appendChild(menuAction('WhatsApp', 'fe-message-circle', function () { openWhatsApp(item); }));
		if (root.getAttribute('data-can-fingerprint') === '1' && item.connections > 0) actionMenu.appendChild(menuAction('Fingerprint', 'fe-crosshair', function () { openFingerprint(item); }));
		if (canEdit) {
			actionMenu.appendChild(menuAction(item.enabled ? 'Disable' : 'Enable', item.enabled ? 'fe-pause-circle' : 'fe-play-circle', function () { runAction(item.enabled ? 'disable' : 'enable', item); }));
			actionMenu.appendChild(menuAction(item.adminEnabled ? 'Ban' : 'Unban', item.adminEnabled ? 'fe-slash' : 'fe-check-circle', function () { runAction(item.adminEnabled ? 'ban' : 'unban', item); }, item.adminEnabled ? 'is-warning' : ''));
			if (item.connections > 0) actionMenu.appendChild(menuAction('Kill', 'fe-zap-off', function () { runAction('kill', item); }, 'is-warning'));
			actionMenu.appendChild(element('div', 'sc-action-menu-divider'));
			actionMenu.appendChild(menuAction('Delete', 'fe-trash-2', function () { runAction('delete', item); }, 'is-danger'));
		}
		actionMenu.hidden = false;
		var rect = trigger.getBoundingClientRect();
		var menuRect = actionMenu.getBoundingClientRect();
		var top = rect.bottom + 6;
		if (top + menuRect.height > window.innerHeight - 8) top = rect.top - menuRect.height - 6;
		actionMenu.style.top = Math.max(8, top) + 'px';
		actionMenu.style.left = Math.max(8, Math.min(rect.right - menuRect.width, window.innerWidth - menuRect.width - 8)) + 'px';
	}

	function updatePlaylist() {
		if (!playlistDialog || !playlistItem) return;
		var format = playlistDialog.querySelector('[data-sc-playlist-format]');
		var option = format.options[format.selectedIndex];
		var output = playlistDialog.querySelector('[data-sc-playlist-url]');
		var open = playlistDialog.querySelector('[data-sc-playlist-open]');
		if (!format.value) { output.value = ''; open.disabled = true; return; }
		var url = playlistDialog.dataset.playlistBase + '/playlist/' + encodeURIComponent(playlistItem.username) + '/' + encodeURIComponent(playlistItem.password) + '/' + format.value;
		var keys = Array.from(playlistDialog.querySelectorAll('[data-sc-playlist-output]:checked')).map(function (field) { return field.value; });
		if (keys.length) url += (url.indexOf('?') === -1 ? '?' : '&') + 'key=' + encodeURIComponent(keys.join(','));
		var copyText = option.getAttribute('data-copy-text') || '';
		output.value = copyText ? copyText.replace('{DEVICE_LINK}', '"' + url + '"') : url;
		open.disabled = Boolean(copyText);
	}

	function openPlaylist(item) {
		playlistItem = item;
		playlistDialog.querySelector('[data-sc-playlist-format]').value = '';
		playlistDialog.querySelectorAll('[data-sc-playlist-output]').forEach(function (field) { field.checked = false; });
		updatePlaylist();
		playlistDialog.showModal();
	}

	function openNotes(item) {
		notesDialog.querySelector('[data-sc-admin-notes]').textContent = item.adminNotes || 'No admin notes.';
		notesDialog.querySelector('[data-sc-reseller-notes]').textContent = item.resellerNotes || 'No reseller notes.';
		notesDialog.showModal();
	}

	var whatsappMessages = {
		en: 'Hello Dear {USERNAME},\n\nYour IPTV subscription expires on {EXPDATE} and there are {DAYS} days remaining.\n\nWould you like to renew your IPTV subscription?\n\nBest regards',
		de: 'Hallo Lieber {USERNAME},\n\nIhr IPTV Abonnement endet am {EXPDATE} und es sind noch {DAYS} Tage übrig.\n\nMöchten Sie Ihr IPTV Abonnement verlängern?\n\nMit freundlichen Grüßen',
		tr: 'Merhaba Sayın {USERNAME},\n\nIPTV aboneliğiniz {EXPDATE} tarihinde sona eriyor ve {DAYS} gün kaldı.\n\nIPTV aboneliğinizi uzatmak ister misiniz?\n\nSaygılarımızla'
	};

	function updateWhatsApp() {
		var item = whatsappDialog._subscription;
		if (!item) return;
		var expiration = item.expiresAt ? new Date(item.expiresAt * 1000) : null;
		var days = expiration ? Math.max(0, Math.ceil((expiration - new Date()) / 86400000)) : 0;
		var message = whatsappMessages[whatsappDialog.querySelector('[data-sc-whatsapp-language]').value]
			.replace('{USERNAME}', item.username || '')
			.replace('{EXPDATE}', expiration ? expiration.toLocaleDateString() : 'Never')
			.replace('{DAYS}', String(days));
		whatsappDialog.querySelector('[data-sc-whatsapp-message]').value = message;
		whatsappDialog.querySelector('[data-sc-whatsapp-send]').href = 'https://wa.me/' + String(item.contact || '').replace(/[^0-9]/g, '') + '?text=' + encodeURIComponent(message);
	}

	function openWhatsApp(item) {
		if (!item.contact) { toast('This subscription has no WhatsApp number.', true); return; }
		whatsappDialog._subscription = item;
		updateWhatsApp();
		whatsappDialog.showModal();
	}

	function openFingerprint(item) {
		fingerprintItem = item;
		fingerprintDialog.querySelector('[data-sc-fingerprint-type]').value = '1';
		fingerprintDialog.querySelector('[data-sc-fingerprint-message-wrap]').hidden = true;
		fingerprintDialog.querySelector('[data-sc-fingerprint-message]').value = '';
		fingerprintDialog.showModal();
	}

	function sendFingerprint() {
		var type = fingerprintDialog.querySelector('[data-sc-fingerprint-type]').value;
		var message = fingerprintDialog.querySelector('[data-sc-fingerprint-message]').value.trim();
		var data = {
			id: fingerprintItem.id,
			user: true,
			font_size: fingerprintDialog.querySelector('[data-sc-fingerprint-size]').value,
			font_color: fingerprintDialog.querySelector('[data-sc-fingerprint-color]').value,
			message: type === '3' ? message : '',
			type: type,
			xy_offset: fingerprintDialog.querySelector('[data-sc-fingerprint-x]').value + 'x' + fingerprintDialog.querySelector('[data-sc-fingerprint-y]').value
		};
		if (type === '3' && !message) { toast('Enter a custom fingerprint message.', true); return; }
		fetch('./api?action=fingerprint&data=' + encodeURIComponent(JSON.stringify(data)), { credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
			.then(function (response) { if (!response.ok) throw new Error('Request failed'); return response.json(); })
			.then(function (result) { if (result.result !== true) throw new Error('Fingerprint failed'); fingerprintDialog.close(); toast('Fingerprint signal has been sent.', false); })
			.catch(function () { toast('The fingerprint signal could not be sent.', true); });
	}

	function renderRow(item) {
		var row = element('tr');
		var usernameCell = element('td');
		var identity = element('div', 'sc-table-identity sc-line-identity');
		var username = canEdit ? element('a', '', item.username || '—') : element('strong', '', item.username || '—');
		if (canEdit) username.href = 'line?id=' + encodeURIComponent(item.id);
		identity.appendChild(username);
		identity.appendChild(element('small', '', '#' + item.id));
		usernameCell.appendChild(identity);
		row.appendChild(usernameCell);
		row.appendChild(element('td', 'sc-table-password', item.password || '—'));

		row.appendChild(element('td', 'sc-table-secondary', item.owner || 'System'));
		var statusCell = element('td');
		statusCell.appendChild(statusBadge(item));
		row.appendChild(statusCell);
		var onlineCell = element('td', 'sc-table-center');
		onlineCell.appendChild(stateIndicator(Boolean(item.online), 'Online'));
		row.appendChild(onlineCell);
		var trialCell = element('td', 'sc-table-center');
		trialCell.appendChild(stateIndicator(Boolean(item.trial), 'Trial'));
		row.appendChild(trialCell);
		var restreamerCell = element('td', 'sc-table-center');
		restreamerCell.appendChild(stateIndicator(Boolean(item.restreamer), 'Restreamer'));
		row.appendChild(restreamerCell);

		var connectionCell = element('td', 'sc-table-center');
		var connectionText = String(item.connections || 0);
		if (canViewConnections && item.connections > 0) {
			var connectionLink = element('a', 'sc-connection-link', connectionText);
			connectionLink.href = 'live_connections?user_id=' + encodeURIComponent(item.id);
			connectionCell.appendChild(connectionLink);
		} else {
			connectionCell.appendChild(element('span', 'sc-connection-link', connectionText));
		}
		row.appendChild(connectionCell);

		var limitCell = element('td', 'sc-table-center');
		limitCell.appendChild(element('span', 'sc-connection-link', item.maxConnections === null ? '∞' : String(item.maxConnections)));
		row.appendChild(limitCell);
		row.appendChild(element('td', item.status === 'expired' ? 'sc-text-danger' : '', item.expiresAtLabel || 'Never'));

		var activityCell = element('td');
		var activity = element('div', 'sc-table-activity');
		activity.appendChild(element('span', '', item.lastActivityLabel || 'Never'));
		if (item.currentStream) activity.appendChild(element('small', '', item.currentStream));
		activityCell.appendChild(activity);
		row.appendChild(activityCell);

		var actionCell = element('td', 'sc-table-actions sc-line-actions');
		var actionTrigger = element('button', 'sc-row-action sc-action-menu-trigger');
		actionTrigger.type = 'button';
		actionTrigger.title = 'Actions';
		actionTrigger.setAttribute('aria-label', 'Actions for ' + (item.username || 'subscription'));
		actionTrigger.appendChild(element('i', 'fe-more-vertical'));
		actionTrigger.addEventListener('click', function (event) { event.stopPropagation(); if (activeActionTrigger === actionTrigger) closeActionMenu(); else openActionMenu(item, actionTrigger); });
		actionCell.appendChild(actionTrigger);
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
		params.set('view', 'xtreampi');
		params.set('start', String((page - 1) * pageSize));
		params.set('length', String(pageSize));
		params.set('search[value]', search.value.trim());
		params.set('filter', filter.value);
		params.set('reseller', ownerId.value);
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
	ownerSearch.addEventListener('focus', loadOwners);
	ownerSearch.addEventListener('input', function () {
		window.clearTimeout(ownerTimer);
		if (ownerId.value) ownerId.value = '';
		if (!ownerSearch.value.trim()) { ownerOptions.hidden = true; page = 1; load(); return; }
		ownerTimer = window.setTimeout(loadOwners, 250);
	});
	document.addEventListener('click', function (event) { if (!ownerFilter.contains(event.target)) ownerOptions.hidden = true; });
	document.addEventListener('click', function (event) { if (!actionMenu.contains(event.target)) closeActionMenu(); });
	window.addEventListener('resize', closeActionMenu);
	window.addEventListener('scroll', closeActionMenu, true);
	entries.addEventListener('change', function () { page = 1; load(); });
	previous.addEventListener('click', function () { if (page > 1) { page -= 1; load(); } });
	next.addEventListener('click', function () { if (page * Number(entries.value) < total) { page += 1; load(); } });
	if (playlistDialog) {
		playlistDialog.querySelector('[data-sc-playlist-format]').addEventListener('change', updatePlaylist);
		playlistDialog.querySelectorAll('[data-sc-playlist-output]').forEach(function (field) { field.addEventListener('change', updatePlaylist); });
		playlistDialog.querySelector('[data-sc-playlist-copy]').addEventListener('click', function () {
			var output = playlistDialog.querySelector('[data-sc-playlist-url]');
			if (!output.value) return;
			if (navigator.clipboard && window.isSecureContext) navigator.clipboard.writeText(output.value); else { output.select(); document.execCommand('copy'); }
			toast('Playlist value copied.', false);
		});
		playlistDialog.querySelector('[data-sc-playlist-open]').addEventListener('click', function () {
			var url = playlistDialog.querySelector('[data-sc-playlist-url]').value;
			if (url) window.open(url, '_blank', 'noopener');
		});
		playlistDialog.addEventListener('click', function (event) { if (event.target === playlistDialog) playlistDialog.close(); });
	}
	if (whatsappDialog) whatsappDialog.querySelector('[data-sc-whatsapp-language]').addEventListener('change', updateWhatsApp);
	if (fingerprintDialog) {
		fingerprintDialog.querySelector('[data-sc-fingerprint-type]').addEventListener('change', function (event) { fingerprintDialog.querySelector('[data-sc-fingerprint-message-wrap]').hidden = event.target.value !== '3'; });
		fingerprintDialog.querySelector('[data-sc-fingerprint-send]').addEventListener('click', sendFingerprint);
	}
	[notesDialog, whatsappDialog, fingerprintDialog].forEach(function (dialog) { if (dialog) dialog.addEventListener('click', function (event) { if (event.target === dialog) dialog.close(); }); });

	load();
}());
