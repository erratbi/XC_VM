(function () {
    'use strict';
    var root = document.querySelector('[data-sc-serie-editor]');
    var form = root && root.querySelector('form');
    if (!form) return;
    function error(message) { var node = form.querySelector('[data-sc-serie-error]'); node.textContent = message || ''; node.hidden = !message; }

    root.querySelectorAll('[data-sc-serie-picker]').forEach(function (picker) {
        var items = [], search = picker.querySelector('[data-serie-search]'), options = picker.querySelector('[data-serie-options]'), values = picker.querySelector('[data-serie-values]'), empty = picker.querySelector('[data-serie-empty]'), create = form.elements[picker.dataset.createField];
        try { items = JSON.parse(picker.querySelector('[data-serie-items]').textContent); } catch (ignore) {}
        function update() { if (create) create.value = JSON.stringify(Array.from(values.querySelectorAll('[data-new="1"] input')).map(function (input) { return input.value; })); empty.hidden = values.children.length > 0; }
        function selected(id) { return Array.from(values.querySelectorAll('input')).some(function (input) { return String(input.value).toLowerCase() === String(id).toLowerCase(); }); }
        function add(entry) { if (selected(entry.id)) return; var token = document.createElement('span'), input = document.createElement('input'), remove = document.createElement('button'); token.append(document.createTextNode(entry.text)); if (entry.isNew) { token.dataset.new = '1'; var badge = document.createElement('small'); badge.textContent = 'New'; token.appendChild(badge); } input.type = 'hidden'; input.name = picker.dataset.name; input.value = entry.id; remove.type = 'button'; remove.textContent = '×'; remove.onclick = function () { token.remove(); update(); render(); }; token.append(input, remove); values.appendChild(token); search.value = ''; options.hidden = true; update(); }
        function render() { var raw = search.value.trim(), term = raw.toLowerCase(); options.replaceChildren(); items.filter(function (entry) { return !selected(entry.id) && (!term || entry.text.toLowerCase().indexOf(term) !== -1); }).slice(0, 50).forEach(function (entry) { var button = document.createElement('button'); button.type = 'button'; button.textContent = entry.text; button.onclick = function () { add(entry); }; options.appendChild(button); }); if (raw && !items.some(function (entry) { return entry.text.toLowerCase() === term; }) && !selected(raw)) { var createButton = document.createElement('button'); createButton.type = 'button'; createButton.className = 'is-create-option'; createButton.textContent = 'Create ' + picker.dataset.createLabel.toLowerCase() + ' “' + raw + '”'; createButton.onclick = function () { add({ id: raw, text: raw, isNew: true }); }; options.appendChild(createButton); } options.hidden = !options.children.length; }
        values.querySelectorAll('button').forEach(function (button) { button.onclick = function () { button.parentNode.remove(); update(); render(); }; });
        search.onfocus = render; search.oninput = render; search.onkeydown = function (event) { if (event.key === 'Enter' && options.firstElementChild) { event.preventDefault(); options.firstElementChild.click(); } }; document.addEventListener('click', function (event) { if (!picker.contains(event.target)) options.hidden = true; }); update();
    });

    var lookup = form.querySelector('[data-sc-serie-tmdb]');
    var query = form.elements.title;
    if (lookup && query) {
        var results = lookup.querySelector('[data-sc-serie-results]');
        var language = lookup.dataset.scTmdbLanguage || '';
        var timer;
        var request = 0;
        var matches = [];
        var oldInput = lookup.querySelector('[data-sc-serie-query]');
        var heading = lookup.querySelector('.sc-section-heading');
        var titleLabel = query.closest('label');
        if (oldInput) oldInput.closest('label').remove();
        if (heading) heading.remove();
        lookup.classList.remove('sc-tmdb-lookup', 'sc-form-span');
        lookup.classList.add('sc-tmdb-autocomplete');
        titleLabel.parentNode.insertBefore(lookup, titleLabel);
        lookup.appendChild(titleLabel);
        var inputWrap = document.createElement('span');
        inputWrap.className = 'sc-tmdb-input-wrap';
        titleLabel.insertBefore(inputWrap, query);
        inputWrap.appendChild(query);
        var toggle = document.createElement('button');
        var count = document.createElement('span');
        toggle.type = 'button';
        toggle.className = 'sc-tmdb-result-count';
        toggle.hidden = true;
        toggle.append(count, Object.assign(document.createElement('i'), { className: 'fe-chevron-down', ariaHidden: 'true' }));
        inputWrap.appendChild(toggle);
        query.autocomplete = 'off';

        function url(action, extra) { return './api?' + new URLSearchParams(Object.assign({ action: action, type: 'series', language: language }, extra)); }
        function set(name, value) { var input = form.elements[name]; if (input) input.value = value == null ? '' : value; }
        function choose(entry) {
            results.hidden = false;
            results.textContent = 'Loading TMDB metadata…';
            fetch(url('tmdb', { id: entry.id })).then(function (response) { if (!response.ok) throw new Error(); return response.json(); }).then(function (payload) {
                if (payload.result !== true) throw new Error();
                var data = payload.data || {}, credits = data.credits || {};
                var cast = (credits.cast || []).slice(0, 5).map(function (person) { return person.name; }).join(', ');
                var directors = (credits.crew || []).filter(function (person) { return person.department === 'Directing' || person.known_for_department === 'Directing'; }).slice(0, 3).map(function (person) { return person.name; }).join(', ');
                set('title', data.name || data.title); set('year', (data.first_air_date || '').slice(0, 4));
                set('cover', data.poster_path ? 'https://image.tmdb.org/t/p/w600_and_h900_bestv2' + data.poster_path : '');
                set('backdrop_path', data.backdrop_path ? 'https://image.tmdb.org/t/p/w1280' + data.backdrop_path : '');
                set('plot', data.overview); set('cast', cast); set('director', directors);
                set('genre', (data.genres || []).slice(0, 3).map(function (genre) { return genre.name; }).join(', '));
                set('release_date', data.first_air_date || '');
                set('episode_run_time', Array.isArray(data.episode_run_time) ? (data.episode_run_time[0] || '') : data.episode_run_time);
                set('youtube_trailer', data.trailer || ''); set('rating', data.vote_average || ''); set('tmdb_id', data.id || entry.id);
                results.hidden = true;
            }).catch(function () { results.textContent = 'TMDB metadata could not be loaded.'; });
        }
        function show(items) {
            matches = items;
            results.replaceChildren();
            if (!items.length) { toggle.hidden = true; results.hidden = true; return; }
            items.forEach(function (entry) {
                var button = document.createElement('button'), title = document.createElement('strong'), detail = document.createElement('span');
                button.type = 'button'; button.className = 'sc-tmdb-result'; title.textContent = entry.name || entry.title || 'Untitled series';
                detail.textContent = entry.first_air_date || entry.release_date || ''; button.append(title, detail);
                button.onclick = function () { choose(entry); }; results.appendChild(button);
            });
            count.textContent = items.length + (items.length === 1 ? ' result' : ' results');
            toggle.hidden = false; results.hidden = true;
        }
        query.oninput = function () {
            var sequence = ++request;
            clearTimeout(timer); toggle.hidden = true; results.hidden = true;
            timer = setTimeout(function () {
                var term = query.value.trim();
                if (!term) { matches = []; return; }
                fetch(url('tmdb_search', { term: term })).then(function (response) { if (!response.ok) throw new Error(); return response.json(); }).then(function (payload) {
                    if (sequence !== request) return;
                    show(payload.result === true && Array.isArray(payload.data) ? payload.data : []);
                }).catch(function () { if (sequence === request) { matches = []; toggle.hidden = true; } });
            }, 250);
        };
        toggle.addEventListener('click', function () { if (matches.length) results.hidden = !results.hidden; });
        document.addEventListener('click', function (event) { if (!lookup.contains(event.target)) results.hidden = true; });
    }

    form.onsubmit = function (event) { event.preventDefault(); if (!form.elements.title.value.trim()) { error('Enter a series name.'); return; } var button = form.querySelector('[type=submit]'); button.disabled = true; error(''); fetch(form.action, { method: 'POST', body: new FormData(form), credentials: 'same-origin', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }).then(function (response) { return response.json(); }).then(function (data) { if (data && data.location) location.href = data.location; else throw new Error(); }).catch(function () { button.disabled = false; error('The series could not be saved. Please try again.'); }); };
}());
