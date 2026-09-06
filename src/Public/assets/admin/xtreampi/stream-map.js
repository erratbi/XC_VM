(function () {
    'use strict';

    var root = document.querySelector('[data-sc-stream-editor]');
    var form = root && root.querySelector('form');
    var input = form && form.elements.custom_map;
    if (!form || !input) return;

    var label = input.closest('label');
    var inputRow = document.createElement('div');
    var button = document.createElement('button');
    var panel = document.createElement('div');
    inputRow.className = 'sc-input-action';
    button.className = 'sc-button sc-button-secondary';
    button.type = 'button';
    button.innerHTML = '<i class="fe-search"></i> Choose tracks';
    panel.className = 'sc-map-picker sc-form-span';
    panel.hidden = true;
    input.parentNode.insertBefore(inputRow, input);
    inputRow.append(input, button);
    label.insertAdjacentElement('afterend', panel);

    function selectedIndexes() {
        var selected = new Set();
        var match;
        var pattern = /-map\s+0:(\d+)/g;
        while ((match = pattern.exec(input.value))) selected.add(Number(match[1]));
        return selected;
    }

    function frameRate(stream) {
        var parts = String(stream.avg_frame_rate || stream.r_frame_rate || '').split('/');
        var numerator = Number(parts[0] || 0);
        var denominator = Number(parts[1] || 1);
        return denominator && numerator ? Math.round((numerator / denominator) * 100) / 100 : 0;
    }

    function streamInformation(stream) {
        var parts = [String(stream.codec_name || stream.codec_long_name || 'Unknown').toUpperCase()];
        if (stream.codec_type === 'video') {
            if (stream.profile) parts.push(stream.profile);
            if (stream.pix_fmt) parts.push(stream.pix_fmt);
            if (stream.width && stream.height) parts.push(stream.width + ' × ' + stream.height);
            var fps = frameRate(stream);
            if (fps) parts.push(fps + ' FPS');
        } else if (stream.codec_type === 'audio') {
            if (stream.tags && stream.tags.language) parts.push(stream.tags.language.toUpperCase());
            if (stream.sample_rate) parts.push(stream.sample_rate + ' Hz');
            if (stream.channel_layout) parts.push(stream.channel_layout);
            var bitrate = Number(stream.bit_rate || (stream.tags && stream.tags.variant_bitrate) || 0);
            if (bitrate) parts.push(Math.ceil(bitrate / 1000) + ' kb/s');
        } else if (stream.tags && stream.tags.language) {
            parts.push(stream.tags.language.toUpperCase());
        }
        return parts.join(' · ');
    }

    function render(streams) {
        var selected = selectedIndexes();
        panel.replaceChildren();
        var heading = document.createElement('div');
        var title = document.createElement('div');
        var clear = document.createElement('button');
        var list = document.createElement('div');
        heading.className = 'sc-map-heading';
        title.innerHTML = '<strong>Available tracks</strong><small>Select the tracks FFmpeg should use.</small>';
        clear.className = 'sc-button sc-button-secondary';
        clear.type = 'button';
        clear.textContent = 'Clear selection';
        list.className = 'sc-map-tracks';
        heading.append(title, clear);
        panel.append(heading, list);

        function sync() {
            input.value = Array.from(selected).sort(function (a, b) { return a - b; }).map(function (index) {
                return '-map 0:' + index;
            }).join(' ');
            list.querySelectorAll('[data-map-index]').forEach(function (row) {
                var active = selected.has(Number(row.dataset.mapIndex));
                row.classList.toggle('is-selected', active);
                row.querySelector('input').checked = active;
            });
        }

        streams.forEach(function (stream) {
            var index = Number(stream.index);
            var row = document.createElement('button');
            var check = document.createElement('input');
            var number = document.createElement('span');
            var type = document.createElement('strong');
            var information = document.createElement('span');
            row.type = 'button';
            row.dataset.mapIndex = index;
            check.type = 'checkbox';
            check.tabIndex = -1;
            number.textContent = '#' + index;
            type.textContent = (stream.codec_type || 'data').replace(/^./, function (letter) { return letter.toUpperCase(); });
            information.textContent = streamInformation(stream);
            row.append(check, number, type, information);
            row.onclick = function () {
                if (selected.has(index)) selected.delete(index); else selected.add(index);
                sync();
            };
            list.append(row);
        });
        clear.onclick = function () { selected.clear(); sync(); };
        sync();
    }

    button.onclick = function () {
        var sources = Array.from(form.querySelectorAll('input[name="stream_source[]"]')).map(function (source) {
            return source.value.trim();
        }).filter(Boolean);
        panel.hidden = false;
        if (!sources.length) {
            panel.innerHTML = '<p class="sc-map-message is-error">Add a source URL before scanning tracks.</p>';
            return;
        }
        panel.innerHTML = '<p class="sc-map-message">Scanning tracks…</p>';
        var sourceServer = Array.from(form.querySelectorAll('[data-server-id]')).find(function (row) {
            return row.querySelector('[data-server-parent]').value === 'source';
        });
        window.scProbeStream({
            url: sources[0],
            userAgent: form.elements.user_agent ? form.elements.user_agent.value : '',
            proxy: form.elements.http_proxy ? form.elements.http_proxy.value : '',
            cookies: form.elements.cookie ? form.elements.cookie.value : '',
            headers: form.elements.headers ? form.elements.headers.value : '',
            server: sourceServer ? sourceServer.dataset.serverId : ''
        }).then(function (data) {
            render(data.streams);
            if (sources.length > 1) {
                var warning = document.createElement('p');
                warning.className = 'sc-map-warning';
                warning.textContent = 'Tracks were read from the first source. A custom map may not match your backup sources.';
                panel.prepend(warning);
            }
        }).catch(function () {
            panel.innerHTML = '<p class="sc-map-message is-error">Tracks could not be read. Try the proxy or provider connection settings under Processing.</p>';
        });
    };
}());
