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
		var wrapper = document.createElement('div'); wrapper.className = 'sc-owner-combobox'; var trigger = document.createElement('input'); trigger.type = 'text'; trigger.className = ownerInput.className; trigger.placeholder = 'Search for an owner…'; trigger.autocomplete = 'off'; var hidden = document.createElement('input'); hidden.type = 'hidden'; hidden.name = 'owner_id'; hidden.value = ownerInput.value || '0'; var menu = document.createElement('div'); menu.className = 'sc-owner-options'; menu.hidden = true; wrapper.append(trigger, hidden, menu); ownerInput.replaceWith(wrapper);
		var ownerTimer;
		function loadOwners() { var query = trigger.value.trim(); fetch('./api?search=' + encodeURIComponent(query) + '&action=reguserlist&page=1', { credentials: 'same-origin', headers: { Accept: 'application/json, text/javascript, */*; q=0.01', 'X-Requested-With': 'XMLHttpRequest' } }).then(function (response) { return response.json(); }).then(function (data) { menu.replaceChildren(); var clear = document.createElement('button'); clear.type = 'button'; clear.textContent = 'No owner'; clear.addEventListener('click', function () { hidden.value = '0'; trigger.value = ''; menu.hidden = true; }); menu.appendChild(clear); (data.items || []).forEach(function (item) { var option = document.createElement('button'); option.type = 'button'; option.textContent = item.text || item.username || item.id; option.dataset.id = item.id; option.addEventListener('click', function () { hidden.value = item.id; trigger.value = option.textContent; menu.hidden = true; }); menu.appendChild(option); }); menu.hidden = false; }).catch(function () {}); }
		trigger.addEventListener('focus', loadOwners); trigger.addEventListener('input', function () { clearTimeout(ownerTimer); ownerTimer = setTimeout(loadOwners, 250); }); document.addEventListener('click', function (event) { if (!wrapper.contains(event.target)) menu.hidden = true; });
	}

	// User saves use the same request contract as the legacy editor. Keep the form
	// markup usable without JavaScript, but intercept the normal navigation when
	// the StreamCreed bundle is available and send the browser-generated
	// multipart FormData to post.php?action=user instead.
	var userForm = document.querySelector('.sc-user-editor form');
	if (userForm) {
		var userFormError = userForm.querySelector('[data-sc-user-form-error]');
		function showUserFormError(message) { if (userFormError) { userFormError.textContent = message; userFormError.hidden = false; userFormError.scrollIntoView({ behavior: 'smooth', block: 'nearest' }); } else window.alert(message); }
		userForm.addEventListener('submit', function (event) {
			event.preventDefault();
			if (userFormError) userFormError.hidden = true;
			var submitButton = userForm.querySelector('button[type="submit"]');
			if (submitButton) submitButton.disabled = true;
			fetch(userForm.action, {
				method: 'POST',
				body: new FormData(userForm),
				credentials: 'same-origin',
				headers: {
					'Accept': 'application/json, text/javascript, */*; q=0.01',
					'X-Requested-With': 'XMLHttpRequest'
				}
			}).then(function (response) { return response.text(); }).then(function (text) {
				var response;
				try { response = JSON.parse(text); } catch (error) { response = null; }
				if (response && response.location) {
					window.location.href = response.location;
					return;
				}
				if (submitButton) submitButton.disabled = false;
				if (response && String(response.status) === userForm.getAttribute('data-status-invalid-input')) showUserFormError('Required entry fields have not been populated. Please check the form.');
				else if (response && String(response.status) === userForm.getAttribute('data-status-invalid-group')) showUserFormError('Please select a member group.');
				else if (response && String(response.status) === userForm.getAttribute('data-status-existing-username')) showUserFormError('The username you selected already exists. Please use another.');
				else showUserFormError(response && response.message ? response.message : 'An error occurred while processing your request.');
			}).catch(function () {
				if (submitButton) submitButton.disabled = false;
				showUserFormError('The user could not be saved. Please try again.');
			});
		});
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
	function closeDrill() { if (!sidebar) return; sidebar.classList.remove('is-drilling'); if (document.body.classList.contains('sc-page-inner')) document.body.classList.remove('sc-has-context-nav'); navPanels.forEach(function (panel) { panel.classList.remove('is-open'); panel.hidden = true; }); }
	document.querySelectorAll('[data-sc-nav-open]').forEach(function (button) { button.addEventListener('click', function () { var panel = document.querySelector('[data-sc-nav-panel="' + button.getAttribute('data-sc-nav-open') + '"]'); if (!panel || !sidebar) return; if (document.body.classList.contains('sc-page-inner') && window.matchMedia('(min-width: 921px)').matches) { var firstLink = panel.querySelector('a'); if (firstLink) window.location.href = firstLink.href; return; } navPanels.forEach(function (item) { item.classList.remove('is-open'); item.hidden = true; }); panel.hidden = false; sidebar.classList.add('is-drilling'); if (document.body.classList.contains('sc-page-inner')) document.body.classList.add('sc-has-context-nav'); requestAnimationFrame(function () { panel.classList.add('is-open'); }); }); });
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
