(function () {
    'use strict';

    var root = document.querySelector('[data-sc-episode-editor]');
    if (!root) return;

    var season = root.querySelector('#season_num');
    var episode = root.querySelector('#episode');
    var lookup = root.querySelector('[data-sc-episode-tmdb]');
    if (!season || !episode || !lookup) return;

    var results = lookup.querySelector('[data-sc-episode-tmdb-results]');
    var toggle = lookup.querySelector('[data-sc-episode-tmdb-toggle]');
    var count = lookup.querySelector('[data-sc-episode-tmdb-count]');
    var seriesId = root.dataset.scSeriesTmdbId || '';
    var seriesRuntime = root.dataset.scSeriesRuntime || '';
    var language = root.dataset.scTmdbLanguage || '';
    var request = 0;
    var matches = [];

    function set(name, value) {
        var field = root.querySelector('[name="' + name + '"]');
        if (field) field.value = value == null ? '' : value;
    }

    function rating(value) {
        var number = Number(String(value == null ? '' : value).replace(',', '.'));
        return Number.isFinite(number) ? (Math.round(number * 10) / 10).toFixed(1) : '';
    }

    function choose(item) {
        var format = 'S' + String(item.season_number || season.value).padStart(2, '0') + 'E' + String(item.episode_number || '').padStart(2, '0');
        var seriesName = root.querySelector('[readonly]');
        set('stream_display_name', (seriesName ? seriesName.value : 'Series') + ' - ' + format + ' - ' + (item.name || ''));
        set('episode', item.episode_number || '');
        set('tmdb_id', item.id || '');
        set('movie_image', item.still_path ? 'https://image.tmdb.org/t/p/w1280' + item.still_path : '');
        set('release_date', item.air_date || '');
        set('plot', item.overview || '');
        set('episode_run_time', item.runtime || seriesRuntime);
        set('rating', rating(item.vote_average));
        results.hidden = true;
    }

    function render(items) {
        matches = items;
        results.replaceChildren();
        if (!items.length) {
            toggle.hidden = true;
            results.hidden = true;
            return;
        }

        items.forEach(function (item) {
            var button = document.createElement('button');
            var title = document.createElement('strong');
            var detail = document.createElement('span');
            button.type = 'button';
            button.className = 'sc-tmdb-result';
            title.textContent = 'Episode ' + item.episode_number + ' — ' + (item.name || 'Untitled');
            detail.textContent = item.air_date || '';
            button.append(title, detail);
            button.addEventListener('click', function () { choose(item); });
            results.appendChild(button);
        });

        count.textContent = items.length + (items.length === 1 ? ' result' : ' results');
        toggle.hidden = false;
        results.hidden = true;
    }

    function clearMatches() {
        ++request;
        matches = [];
        toggle.hidden = true;
        results.hidden = true;
        results.replaceChildren();
    }

    function load() {
        var seasonNumber = Number(season.value);
        clearMatches();
        var sequence = request;
        if (!seriesId || !seasonNumber) return;

        fetch('./api?action=tmdb_search&type=episode&term=' + encodeURIComponent(seriesId) + '&season=' + encodeURIComponent(seasonNumber) + '&language=' + encodeURIComponent(language), {
            credentials: 'same-origin',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (response) {
            if (!response.ok) throw new Error();
            return response.json();
        }).then(function (payload) {
            if (sequence !== request) return;
            render(payload.result === true && payload.data && Array.isArray(payload.data.episodes) ? payload.data.episodes : []);
        }).catch(function () {
            if (sequence === request) {
                matches = [];
                toggle.hidden = true;
            }
        });
    }

    season.addEventListener('input', clearMatches);
    season.addEventListener('change', load);
    toggle.addEventListener('click', function () {
        if (matches.length) results.hidden = !results.hidden;
    });
    document.addEventListener('click', function (event) {
        if (!lookup.contains(event.target)) results.hidden = true;
    });
    if (season.value) load();
}());
