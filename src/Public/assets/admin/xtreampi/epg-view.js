(function () {
    'use strict';

    var root = document.querySelector('[data-sc-epg-view]');
    if (!root) return;
    var rowContainer = root.querySelector('[data-sc-epg-rows]');
    var windowLabel = root.querySelector('[data-sc-epg-window]');
    var ids = (root.getAttribute('data-stream-ids') || '').split(',').map(function (value) { return value.trim(); }).filter(function (value) { return /^\d+$/.test(value); });
    var category = root.getAttribute('data-category') || '';
    var timezone = 'Europe/London';
    try { timezone = Intl.DateTimeFormat().resolvedOptions().timeZone || timezone; } catch (error) { /* browser without Intl */ }
    var windowStart = new Date();
    windowStart.setMinutes(0, 0, 0);
    var requestToken = 0;
    var dialog = root.nextElementSibling && root.nextElementSibling.matches('[data-sc-epg-dialog]') ? root.nextElementSibling : document.querySelector('[data-sc-epg-dialog]');

    function addTextCell(row, value) {
        var cell = document.createElement('td');
        cell.textContent = value == null || value === '' ? '—' : String(value);
        row.appendChild(cell);
        return cell;
    }

    function formatApiDate(date) {
        var pad = function (value) { return String(value).padStart(2, '0'); };
        return date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate()) + ' ' + pad(date.getHours()) + ':' + pad(date.getMinutes()) + ':' + pad(date.getSeconds());
    }

    function formatWindow(date) {
        return date.toLocaleString([], { dateStyle: 'medium', timeStyle: 'short' }) + ' – ' + new Date(date.getTime() + 3 * 3600000).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) + ' (' + timezone + ')';
    }

    function showState(message) {
        rowContainer.replaceChildren();
        var row = document.createElement('tr');
        var cell = document.createElement('td');
        cell.className = 'sc-table-state';
        cell.colSpan = 3;
        cell.textContent = message;
        row.appendChild(cell);
        rowContainer.appendChild(row);
    }

    function showProgramme(channelId, programmeId) {
        if (!dialog || !programmeId) return;
        var title = dialog.querySelector('[data-sc-epg-title]');
        var date = dialog.querySelector('[data-sc-epg-date]');
        var description = dialog.querySelector('[data-sc-epg-description]');
        var record = dialog.querySelector('[data-sc-epg-record]');
        title.textContent = 'Loading programme…';
        date.textContent = '';
        description.textContent = '';
        record.hidden = true;
        if (typeof dialog.showModal === 'function' && !dialog.open) dialog.showModal();
        fetch('api?action=get_programme&id=' + encodeURIComponent(programmeId) + '&stream_id=' + encodeURIComponent(channelId) + '&timezone=' + encodeURIComponent(timezone), { credentials: 'same-origin', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }).then(function (response) {
            if (!response.ok) throw new Error('HTTP ' + response.status);
            return response.json();
        }).then(function (payload) {
            if (!payload || payload.result !== true || !payload.data) throw new Error('Programme unavailable');
            title.textContent = payload.data.title || 'Programme';
            date.textContent = payload.data.date || '';
            description.textContent = payload.data.description || 'No description available.';
            if (payload.available === true) {
                record.href = 'record?id=' + encodeURIComponent(channelId) + '&programme=' + encodeURIComponent(programmeId);
                record.hidden = false;
            }
        }).catch(function () {
            title.textContent = 'Programme unavailable';
            description.textContent = 'The programme details could not be loaded.';
        });
    }

    function renderChannels(channels) {
        rowContainer.replaceChildren();
        if (!channels.length) {
            showState('No programmes are available for this window.');
            return;
        }
        channels.forEach(function (channel) {
            var row = document.createElement('tr');
            var channelCell = document.createElement('td');
            var link = document.createElement('a');
            link.href = 'stream_view?id=' + encodeURIComponent(channel.Id);
            link.textContent = channel.DisplayName || ('Channel ' + channel.Id);
            channelCell.appendChild(link);
            row.appendChild(channelCell);
            addTextCell(row, channel.CategoryName);
            var scheduleCell = document.createElement('td');
            var listings = Array.isArray(channel.TvListings) ? channel.TvListings : [];
            if (!listings.length) {
                scheduleCell.textContent = 'No programme information.';
            } else {
                listings.forEach(function (listing) {
                    var programme = document.createElement('button');
                    programme.type = 'button';
                    programme.className = 'sc-row-action sc-epg-programme';
                    programme.textContent = (listing.Title || 'No programme information') + (listing.StartTime ? ' · ' + listing.StartTime + (listing.EndTime ? '–' + listing.EndTime : '') : '');
                    if (listing.ListingId) {
                        programme.addEventListener('click', function () { showProgramme(channel.Id, listing.ListingId); });
                    } else {
                        programme.disabled = true;
                    }
                    scheduleCell.appendChild(programme);
                });
            }
            row.appendChild(scheduleCell);
            rowContainer.appendChild(row);
        });
    }

    function load() {
        if (!ids.length) return;
        var token = ++requestToken;
        if (windowLabel) windowLabel.textContent = 'Loading schedule…';
        showState('Loading schedule…');
        var params = new URLSearchParams({ action: 'get_epg', startdate: formatApiDate(windowStart), hours: '3', channels: ids.join(','), timezone: timezone });
        if (category) params.set('category', category);
        fetch('api?' + params.toString(), { credentials: 'same-origin', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }).then(function (response) {
            if (!response.ok) throw new Error('HTTP ' + response.status);
            return response.json();
        }).then(function (payload) {
            if (token !== requestToken) return;
            if (!payload || !Array.isArray(payload.Channels)) throw new Error('Invalid EPG response');
            renderChannels(payload.Channels);
            if (windowLabel) windowLabel.textContent = formatWindow(windowStart);
        }).catch(function () {
            if (token !== requestToken) return;
            showState('The guide could not be loaded.');
            if (windowLabel) windowLabel.textContent = 'Guide unavailable';
        });
    }

    root.querySelectorAll('[data-sc-epg-shift]').forEach(function (button) {
        button.addEventListener('click', function () {
            windowStart = new Date(windowStart.getTime() + parseInt(button.getAttribute('data-sc-epg-shift'), 10) * 3600000);
            load();
        });
    });
    var nowButton = root.querySelector('[data-sc-epg-now]');
    if (nowButton) nowButton.addEventListener('click', function () { windowStart = new Date(); windowStart.setMinutes(0, 0, 0); load(); });
    if (dialog) {
        var close = dialog.querySelector('[data-sc-epg-close]');
        if (close) close.addEventListener('click', function () { if (typeof dialog.close === 'function') dialog.close(); });
    }
    load();
}());
