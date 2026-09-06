(function () {
	'use strict';

	var root = document.querySelector('[data-sc-movie-editor]');
	var dialog = document.querySelector('[data-sc-file-browser-dialog]');
	if (!root || !dialog) return;

	var title = dialog.querySelector('[data-sc-file-browser-title]');
	var close = dialog.querySelector('[data-sc-file-browser-close]');
	var server = dialog.querySelector('[data-sc-file-browser-server]');
	var path = dialog.querySelector('[data-sc-file-browser-path]');
	var load = dialog.querySelector('[data-sc-file-browser-load]');
	var dirs = dialog.querySelector('[data-sc-file-browser-dirs]');
	var files = dialog.querySelector('[data-sc-file-browser-files]');
	var filesHeading = dialog.querySelector('[data-sc-file-browser-files-heading]');
	var target = null;
	var filter = 'video';
	var mode = 'file';
	var error = root.querySelector('[data-stream-error]');

	function processingInput(id) {
		return root.querySelector('#' + id);
	}

	function setEnabled(id, enabled) {
		var input = processingInput(id);
		if (!input) return;
		if (!enabled) input.checked = false;
		input.disabled = !enabled;
	}

	function showError(message) {
		if (!error) return;
		error.textContent = message || '';
		error.hidden = !message;
	}

	function sourceIsLocal() {
		var source = processingInput('stream_source');
		var value = source ? source.value.trim() : '';
		return !value || value.indexOf('s:') === 0 || value.indexOf('/') === 0;
	}

	function syncProcessingRules() {
		var directSource = processingInput('direct_source');
		var directProxy = processingInput('direct_proxy');
		var symlink = processingInput('movie_symlink');
		if (!directSource || !directProxy || !symlink) return;

		if (directSource.checked) {
			setEnabled('movie_symlink', false);
			setEnabled('read_native', false);
			setEnabled('remove_subtitles', false);
			setEnabled('transcode_profile_id', false);
			setEnabled('movie_subtitles', false);
			setEnabled('direct_proxy', true);
			showError('');
			return;
		}

		setEnabled('direct_proxy', false);
		if (symlink.checked && !sourceIsLocal()) {
			symlink.checked = false;
			showError('Create symlink requires a local movie source. Choose a server file or enter an absolute path.');
		}

		if (symlink.checked) {
			setEnabled('direct_source', false);
			setEnabled('direct_proxy', false);
			setEnabled('read_native', false);
			setEnabled('remove_subtitles', false);
			setEnabled('target_container', false);
			setEnabled('transcode_profile_id', false);
			setEnabled('movie_subtitles', false);
			return;
		}

		['direct_source', 'read_native', 'remove_subtitles', 'target_container', 'transcode_profile_id', 'movie_subtitles'].forEach(function (id) { setEnabled(id, true); });
		setEnabled('direct_proxy', false);
		showError('');
	}

	function row(label, icon, callback) {
		var button = document.createElement('button');
		button.type = 'button';
		button.className = 'sc-file-browser-item';
		var glyph = document.createElement('i');
		glyph.className = icon;
		glyph.setAttribute('aria-hidden', 'true');
		button.append(glyph, document.createTextNode(label));
		button.addEventListener('click', callback);
		return button;
	}

	function normalPath(value) {
		var result = String(value || '/').trim();
		if (!result.startsWith('/')) result = '/' + result;
		return result.endsWith('/') ? result : result + '/';
	}

	function parentPath(value) {
		var parts = normalPath(value).split('/').filter(Boolean);
		parts.pop();
		return '/' + (parts.length ? parts.join('/') + '/' : '');
	}

	function selectFile(name) {
		if (!target) return;
		target.value = 's:' + server.value + ':' + normalPath(path.value) + name;
		if (filter === 'video') {
			var extension = name.split('.').pop().toLowerCase();
			var container = root.querySelector('[name="target_container"]');
			if (container && Array.prototype.some.call(container.options, function (option) { return option.value === extension; })) container.value = extension;
		}
		syncProcessingRules();
		dialog.close();
	}

	function selectDirectory() {
		if (!target) return;
		target.value = 's:' + server.value + ':' + normalPath(path.value);
		dialog.close();
	}

	function showState(container, message) {
		container.replaceChildren();
		var state = document.createElement('p');
		state.className = 'sc-file-browser-state';
		state.textContent = message;
		container.appendChild(state);
	}

	function browse() {
		path.value = normalPath(path.value);
		showState(dirs, 'Loading folders…');
		showState(files, 'Loading files…');
		var params = new URLSearchParams({ action: 'listdir', server: server.value, dir: path.value, filter: filter });
		fetch('./api?' + params.toString(), { credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
			.then(function (response) { if (!response.ok) throw new Error('Request failed'); return response.json(); })
			.then(function (payload) {
				if (payload.result !== true) throw new Error('Listing failed');
				var data = payload.data || {};
				dirs.replaceChildren();
				files.replaceChildren();
				if (path.value !== '/') dirs.appendChild(row('Parent directory', 'fe-corner-up-left', function () { path.value = parentPath(path.value); browse(); }));
				(data.dirs || []).forEach(function (name) { dirs.appendChild(row(name, 'fe-folder', function () { path.value = normalPath(path.value) + name + '/'; browse(); })); });
				if (mode === 'directory') {
					files.appendChild(row('Use this folder', 'fe-check-circle', selectDirectory));
				} else {
					(data.files || []).forEach(function (name) { files.appendChild(row(name, filter === 'subs' ? 'fe-file-text' : 'fe-film', function () { selectFile(name); })); });
				}
				if (!dirs.children.length) showState(dirs, 'No folders found.');
				if (!files.children.length) showState(files, 'No compatible files found.');
			}).catch(function () {
				showState(dirs, 'Folders could not be loaded.');
				showState(files, 'Files could not be loaded.');
			});
	}

	root.querySelectorAll('[data-sc-file-browser-open]').forEach(function (button) {
		button.addEventListener('click', function () {
			target = root.querySelector('#' + button.dataset.scFileTarget);
			filter = button.dataset.scFileFilter || 'video';
			mode = button.dataset.scFileMode || 'file';
			title.textContent = mode === 'directory' ? 'Choose a movie folder' : (filter === 'subs' ? 'Browse subtitle files' : 'Browse movie files');
			filesHeading.textContent = mode === 'directory' ? 'Selection' : 'Compatible files';
			path.value = '/';
			dialog.showModal();
			browse();
		});
	});
	load.addEventListener('click', browse);
	server.addEventListener('change', function () { path.value = '/'; browse(); });
	path.addEventListener('keydown', function (event) { if (event.key === 'Enter') { event.preventDefault(); browse(); } });
	close.addEventListener('click', function () { dialog.close(); });
	dialog.addEventListener('click', function (event) { if (event.target === dialog) dialog.close(); });
	['direct_source', 'direct_proxy', 'movie_symlink'].forEach(function (id) {
		var input = processingInput(id);
		if (input) input.addEventListener('change', syncProcessingRules);
	});
	var source = processingInput('stream_source');
	if (source) source.addEventListener('change', syncProcessingRules);
	syncProcessingRules();
}());
