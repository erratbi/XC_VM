(function () {
    'use strict';
    var root = document.querySelector('[data-sc-stream-tools]');
    if (!root) return;
    function json(response) { return response.text().then(function (text) { try { return JSON.parse(text); } catch (e) { return {}; } }); }
    root.querySelectorAll('form').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            var submitter = event.submitter;
            if (submitter && submitter.name && !form.querySelector('[name="' + submitter.name + '"]:not([type="submit"])')) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = submitter.name;
                input.value = submitter.value;
                form.appendChild(input);
            }
            event.preventDefault();
            var button = submitter || form.querySelector('[type="submit"]');
            button.disabled = true;
            fetch(form.action, { method: 'POST', body: new FormData(form), credentials: 'same-origin', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }).then(json).then(function (data) {
                if (data.location) window.location.href = data.location;
                else { button.disabled = false; window.alert('The operation was rejected. Check the fields and try again.'); }
            }).catch(function () { button.disabled = false; window.alert('The operation failed. Please try again.'); });
        });
    });
    root.querySelector('[data-sc-tools-decrypt]').addEventListener('click', function () {
        var input = root.querySelector('[data-sc-tools-encrypted]'), output = root.querySelector('[data-sc-tools-decrypted]'), button = this;
        if (!input.value.trim()) { output.value = 'Enter encrypted text first.'; return; }
        button.disabled = true;
        fetch('api?action=decrypt_text&text=' + encodeURIComponent(input.value), { credentials: 'same-origin', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }).then(json).then(function (data) { output.value = Array.isArray(data.data) ? data.data.join('\n\n') : 'Text could not be decrypted.'; }).catch(function () { output.value = 'The decrypt request failed.'; }).finally(function () { button.disabled = false; });
    });
    var category = root.querySelector('[data-sc-tools-category]');
    fetch('api?action=epg_categories', { credentials: 'same-origin', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }).then(json).then(function (data) { if (data.status === 1 && Array.isArray(data.data)) data.data.forEach(function (item) { var option = document.createElement('option'); option.value = item.id; option.textContent = item.category_name || item.name || item.id; category.appendChild(option); }); }).catch(function () {});
    var threshold = root.querySelector('[data-sc-tools-threshold]');
    threshold.addEventListener('input', function () { root.querySelector('[data-sc-tools-threshold-value]').textContent = threshold.value; });
    root.querySelector('[data-sc-tools-assign]').addEventListener('click', function () {
        var button = this, result = root.querySelector('[data-sc-tools-result]'), lastId = 0, assigned = 0, skipped = 0, processed = 0, running = true;
        if (!window.confirm('Assign EPG matches now?')) return;
        button.disabled = true; result.textContent = 'Running…';
        function batch() {
            var query = 'api?action=epg_auto_assign&last_id=' + encodeURIComponent(lastId) + '&category_id=' + encodeURIComponent(category.value || '') + '&threshold=' + encodeURIComponent(threshold.value);
            fetch(query, { credentials: 'same-origin', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }).then(json).then(function (data) {
                if (data.status !== 1 || !data.data) throw new Error('rejected');
                assigned += Number(data.data.assigned || 0); skipped += Number(data.data.skipped || 0); processed += Number(data.data.batch_size || 0); lastId = Number(data.data.next_last_id || lastId);
                result.textContent = 'Processed ' + processed + ' · assigned ' + assigned + ' · skipped ' + skipped;
                if (data.data.has_more && running) batch(); else { running = false; button.disabled = false; }
            }).catch(function () { running = false; button.disabled = false; result.textContent = 'EPG assignment failed. Retry.'; });
        }
        batch();
    });
}());
