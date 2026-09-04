(function () {
	'use strict';

	var root = document.querySelector('[data-sc-movie-editor]');
	var form = root && root.querySelector('form');
	var lookup = root && root.querySelector('[data-sc-tmdb]');
	if (!form) return;

	var importSection = root.querySelector('[data-sc-movie-import]');
	if (importSection) {
		var importModes = importSection.querySelectorAll('[data-sc-import-mode]');
		var m3uFields = importSection.querySelectorAll('[data-sc-import-m3u]');
		var folderFields = importSection.querySelectorAll('[data-sc-import-folder]');
		function syncImportMode() {
			var folderMode = Array.prototype.some.call(importModes, function (input) { return input.checked && input.value === 'folder'; });
			m3uFields.forEach(function (field) { field.hidden = folderMode; });
			folderFields.forEach(function (field) { field.hidden = !folderMode; });
		}
		importModes.forEach(function (input) { input.addEventListener('change', syncImportMode); });
		syncImportMode();
	}

	if (!lookup) return;

	var query = lookup.querySelector('[data-sc-tmdb-query]');
	var language = lookup.getAttribute('data-sc-tmdb-language') || '';
	var results = lookup.querySelector('[data-sc-tmdb-results]');
	var timer = null;
	var request = 0;

	function input(name) {
		return form.elements[name];
	}

	function text(value) {
		return value === undefined || value === null ? '' : String(value);
	}

	function imageUrl(path, preset) {
		if (!path) return '';
		return /^https?:\/\//i.test(path) ? path : 'https://image.tmdb.org/t/p/' + preset + path;
	}

	function setValue(name, value) {
		var field = input(name);
		if (field) field.value = text(value);
	}

	function names(items, predicate, limit) {
		return (Array.isArray(items) ? items : []).filter(predicate || function () { return true; }).slice(0, limit || 5).map(function (item) {
			return text(item.name);
		}).filter(Boolean).join(', ');
	}

	function populate(data) {
		setValue('tmdb_id', data.id);
		setValue('stream_display_name', data.title || data.name);
		setValue('year', data.release_date ? text(data.release_date).slice(0, 4) : '');
		setValue('movie_image', imageUrl(data.poster_path, 'w600_and_h900_bestv2'));
		setValue('backdrop_path', imageUrl(data.backdrop_path, 'w1280'));
		setValue('release_date', data.release_date);
		setValue('episode_run_time', data.runtime);
		setValue('youtube_trailer', data.trailer);
		setValue('plot', data.overview);
		setValue('cast', names(data.credits && data.credits.cast, null, 5));
		setValue('director', names(data.credits && data.credits.crew, function (person) {
			return person.department === 'Directing' || person.known_for_department === 'Directing';
		}, 3));
		setValue('genre', names(data.genres, null, 3));
		setValue('country', data.production_countries && data.production_countries[0] ? data.production_countries[0].name : '');
		setValue('rating', data.vote_average);
		results.hidden = true;
		results.replaceChildren();
	}

	function selectMovie(id) {
		results.hidden = false;
		results.textContent = 'Loading TMDB metadata…';
		fetch('./api?action=tmdb&type=movie&id=' + encodeURIComponent(id) + '&language=' + encodeURIComponent(language), {
			credentials: 'same-origin',
			headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
		}).then(function (response) {
			if (!response.ok) throw new Error('Request failed');
			return response.json();
		}).then(function (payload) {
			if (payload.result !== true || !payload.data) throw new Error('No metadata');
			populate(payload.data);
		}).catch(function () {
			results.textContent = 'TMDB metadata could not be loaded.';
		});
	}

	function render(items) {
		results.replaceChildren();
		if (!items.length) {
			results.textContent = 'No TMDB matches found.';
			results.hidden = false;
			return;
		}
		items.forEach(function (item) {
			var button = document.createElement('button');
			button.type = 'button';
			button.className = 'sc-tmdb-result';
			var title = document.createElement('strong');
			title.textContent = text(item.title || item.name || 'Untitled movie');
			var detail = document.createElement('span');
			detail.textContent = item.release_date ? text(item.release_date).slice(0, 4) : 'No release date';
			button.append(title, detail);
			button.addEventListener('click', function () { selectMovie(item.id); });
			results.appendChild(button);
		});
		results.hidden = false;
	}

	function search() {
		var term = query.value.trim();
		if (!term) {
			results.hidden = true;
			results.replaceChildren();
			return;
		}
		var sequence = ++request;
		results.hidden = false;
		results.textContent = 'Searching TMDB…';
		fetch('./api?action=tmdb_search&type=movie&term=' + encodeURIComponent(term) + '&language=' + encodeURIComponent(language), {
			credentials: 'same-origin',
			headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
		}).then(function (response) {
			if (!response.ok) throw new Error('Request failed');
			return response.json();
		}).then(function (payload) {
			if (sequence !== request) return;
			render(payload.result === true && Array.isArray(payload.data) ? payload.data : []);
		}).catch(function () {
			if (sequence !== request) return;
			results.textContent = 'TMDB search could not be completed.';
		});
	}

	query.addEventListener('input', function () {
		clearTimeout(timer);
		timer = window.setTimeout(search, 280);
	});
}());
