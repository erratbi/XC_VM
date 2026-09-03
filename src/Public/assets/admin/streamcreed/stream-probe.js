(function () {
    'use strict';

    var cache = new Map();

    window.scProbeStream = function (settings) {
        var params = new URLSearchParams({
            action: 'probe_stream',
            map: '1',
            url: settings.url || '',
            user_agent: settings.userAgent || '',
            proxy: settings.proxy || '',
            cookies: settings.cookies || '',
            headers: settings.headers || '',
            server: settings.server || ''
        });
        var key = params.toString();
        if (!cache.has(key)) {
            cache.set(key, fetch('./api?' + params, {
                credentials: 'same-origin',
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function (response) {
                if (!response.ok) throw new Error('Probe request failed');
                return response.json();
            }).then(function (data) {
                if (!Array.isArray(data.streams) || !data.streams.length) throw new Error('No tracks returned');
                return data;
            }).catch(function (error) {
                cache.delete(key);
                throw error;
            }));
        }
        return cache.get(key);
    };
}());
