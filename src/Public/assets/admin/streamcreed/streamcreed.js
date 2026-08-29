(function () {
	'use strict';
	var passwordInput = document.querySelector('.sc-user-editor input[name="password"]');
	if (passwordInput) {
		var meter = document.createElement('div'); meter.className = 'sc-password-meter'; meter.innerHTML = '<span></span><strong></strong>'; passwordInput.parentNode.appendChild(meter);
		var meterBar = meter.querySelector('span'), meterLabel = meter.querySelector('strong');
		passwordInput.addEventListener('input', function () { var value = passwordInput.value, score = (value.length >= 10 ? 1 : 0) + (/[A-Z]/.test(value) ? 1 : 0) + (/[0-9]/.test(value) ? 1 : 0) + (/[^A-Za-z0-9]/.test(value) ? 1 : 0), labels = ['Too weak', 'Weak', 'Medium', 'Strong', 'Very strong']; meter.hidden = !value; meterBar.style.width = Math.max(8, score * 25) + '%'; meterLabel.textContent = value ? labels[score] : ''; meter.dataset.score = score; }); passwordInput.dispatchEvent(new Event('input'));
	}
	var ownerInput = document.querySelector('.sc-user-editor input[name="owner_id"]');
	if (ownerInput) {
		var ownerSelect = document.createElement('select'); ownerSelect.name = 'owner_id'; ownerSelect.className = ownerInput.className; ownerSelect.innerHTML = '<option value="0">No owner</option>'; ownerSelect.value = ownerInput.value || '0'; ownerInput.replaceWith(ownerSelect);
		var ownerTimer;
		function loadOwners() { var query = ownerSelect.dataset.query || ''; fetch('./api?action=reguserlist&search=' + encodeURIComponent(query), { credentials: 'same-origin', headers: { Accept: 'application/json' } }).then(function (response) { return response.json(); }).then(function (data) { var current = ownerSelect.value; ownerSelect.innerHTML = '<option value="0">No owner</option>'; (data.items || []).forEach(function (item) { var option = document.createElement('option'); option.value = item.id; option.textContent = item.text || item.username || item.id; ownerSelect.appendChild(option); }); ownerSelect.value = current; }).catch(function () {}); }
		ownerSelect.addEventListener('focus', loadOwners); ownerSelect.addEventListener('change', function () { ownerSelect.dataset.query = ownerSelect.options[ownerSelect.selectedIndex].textContent; clearTimeout(ownerTimer); ownerTimer = setTimeout(loadOwners, 250); });
	}

	var sidebar = document.getElementById('streamcreed-sidebar');
	var sidebarToggle = document.querySelector('[data-sc-sidebar-toggle]');
	var sidebarClose = document.querySelector('[data-sc-sidebar-close]');

	function setSidebar(open) {
		document.body.classList.toggle('sc-sidebar-open', open);
		if (sidebarToggle) {
			sidebarToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
		}
	}

	if (sidebar && sidebarToggle) {
		sidebarToggle.addEventListener('click', function () {
			setSidebar(!document.body.classList.contains('sc-sidebar-open'));
		});
	}
	if (sidebarClose) {
		sidebarClose.addEventListener('click', function () { setSidebar(false); });
	}

	var navPanels = document.querySelectorAll('[data-sc-nav-panel]');
	function closeDrill() { if (!sidebar) return; sidebar.classList.remove('is-drilling'); navPanels.forEach(function (panel) { panel.classList.remove('is-open'); panel.hidden = true; }); }
	document.querySelectorAll('[data-sc-nav-open]').forEach(function (button) { button.addEventListener('click', function () { var panel = document.querySelector('[data-sc-nav-panel="' + button.getAttribute('data-sc-nav-open') + '"]'); if (!panel || !sidebar) return; navPanels.forEach(function (item) { item.classList.remove('is-open'); item.hidden = true; }); panel.hidden = false; sidebar.classList.add('is-drilling'); requestAnimationFrame(function () { panel.classList.add('is-open'); }); }); });
	document.querySelectorAll('[data-sc-nav-close]').forEach(function (button) { button.addEventListener('click', closeDrill); });

	var dashboard = document.querySelector('[data-sc-dashboard]');
	if (!dashboard) {
		return;
	}

	var serverFilter = dashboard.querySelector('[data-sc-server-filter]');
	if (serverFilter) {
		serverFilter.addEventListener('change', function () {
			var target = './dashboard';
			if (serverFilter.value) {
				target += '?server_id=' + encodeURIComponent(serverFilter.value);
			}
			window.location.href = target;
		});
	}

	var liveState = dashboard.querySelector('[data-sc-live-state]');
	var selectedServer = dashboard.getAttribute('data-selected-server');
	var numberFormatter = new Intl.NumberFormat();
	var stopped = false;

	function number(value) {
		return numberFormatter.format(Number(value) || 0);
	}

	function mbps(bytes) {
		return number(Math.floor((Number(bytes) || 0) / 125000));
	}

	function percent(value) {
		return Math.max(0, Math.min(100, Number(value) || 0));
	}

	function setText(selector, value, root) {
		var element = (root || dashboard).querySelector(selector);
		if (element) {
			element.textContent = value;
		}
	}

	function updateServer(server) {
		var serverID = String(parseInt(server.server_id || selectedServer || 0, 10) || '');
		var card = dashboard.querySelector('[data-sc-server="' + serverID + '"]');
		if (!card) {
			return;
		}

		card.classList.remove('is-offline');
		setText('[data-sc-server-stat="connections"]', number(server.open_connections), card);
		setText('[data-sc-server-stat="uptime"]', server.uptime || '--', card);
		setText('[data-sc-server-stat="requests"]', number(server.requests_per_second), card);
		setText('[data-sc-server-stat="streams"]', number(server.total_running_streams), card);
		setText('[data-sc-server-stat="upload"]', mbps(server.bytes_sent), card);
		setText('[data-sc-server-stat="download"]', mbps(server.bytes_received), card);

		['mem', 'cpu', 'fs', 'io'].forEach(function (key) {
			var value = percent(server[key]);
			setText('[data-sc-server-value="' + key + '"]', number(value) + '%', card);
			var bar = card.querySelector('[data-sc-server-bar="' + key + '"]');
			if (bar) {
				bar.style.width = value + '%';
				bar.classList.toggle('is-warning', value > 50 && value <= 75);
				bar.classList.toggle('is-danger', value > 75);
			}
		});
	}

	function updateDashboard(data) {
		var servers = Array.isArray(data.servers) ? data.servers : [];
		var requests = selectedServer
			? Number(data.requests_per_second) || 0
			: servers.reduce(function (total, server) { return total + (Number(server.requests_per_second) || 0); }, 0);

		setText('[data-sc-stat="online-users"]', number(data.online_users));
		setText('[data-sc-stat="open-connections"]', number(data.open_connections));
		setText('[data-sc-stat="output"]', mbps(data.bytes_sent));
		setText('[data-sc-stat="input"]', mbps(data.bytes_received));
		setText('[data-sc-stat="online-streams"]', number(data.total_running_streams));
		setText('[data-sc-stat="offline-streams"]', number(data.offline_streams));
		setText('[data-sc-stat="active-requests"]', number(requests));

		if (selectedServer) {
			data.server_id = selectedServer;
			updateServer(data);
		} else {
			servers.forEach(updateServer);
		}

		if (liveState) {
			liveState.classList.remove('has-error');
			liveState.innerHTML = '<i></i> Live';
		}
	}

	function refreshStats() {
		if (stopped) {
			return;
		}
		if (document.hidden) {
			window.setTimeout(refreshStats, 2000);
			return;
		}

		fetch(dashboard.getAttribute('data-stats-url'), {
			credentials: 'same-origin',
			headers: {
				'Accept': 'application/json',
				'X-Requested-With': 'XMLHttpRequest'
			}
		})
			.then(function (response) {
				if (!response.ok) throw new Error('Dashboard request failed');
				return response.json();
			})
			.then(function (data) {
				if (data.result === false) throw new Error('Dashboard access denied');
				updateDashboard(data);
			})
			.catch(function () {
				if (liveState) {
					liveState.classList.add('has-error');
					liveState.innerHTML = '<i></i> Data unavailable';
				}
			})
			.finally(function () {
				window.setTimeout(refreshStats, 2000);
			});
	}

	function pingSession() {
		if (stopped) {
			return;
		}
		fetch('./session', {
			credentials: 'same-origin',
			headers: { 'Accept': 'application/json' }
		})
			.then(function (response) { return response.json(); })
			.then(function (data) {
				if (!data.result) {
					window.location.href = './login?referrer=' + encodeURIComponent(window.location.href.split('/').pop());
				}
			})
			.catch(function () {})
			.finally(function () {
				window.setTimeout(pingSession, 30000);
			});
	}

	window.addEventListener('pagehide', function () { stopped = true; });
	refreshStats();
	window.setTimeout(pingSession, 30000);
}());
