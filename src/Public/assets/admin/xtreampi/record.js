(function () {
    'use strict';
    var schedule = document.querySelector('[data-sc-record-schedule]');
    if (schedule) {
        var input = schedule.querySelector('[name="stream_id"]'), list = schedule.querySelector('datalist');
        function load(term) {
            fetch('api?action=streamlist&search=' + encodeURIComponent(term || ''), { credentials: 'same-origin', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }).then(function (r) { return r.json(); }).then(function (data) {
                list.replaceChildren();
                (Array.isArray(data.items) ? data.items : []).forEach(function (item) { var option = document.createElement('option'); option.value = item.id; option.label = item.text || item.name || item.id; list.appendChild(option); });
            }).catch(function () {});
        }
        input.addEventListener('focus', function () { load(input.value); });
        input.addEventListener('input', function () { load(input.value); });
        schedule.addEventListener('submit', function (event) { if (!input.value.trim() || Number(schedule.querySelector('[name="duration"]').value) <= 0) event.preventDefault(); });
    }
    var form = document.querySelector('[data-sc-record-form]');
    if (form) form.addEventListener('submit', function (event) {
        var title = form.querySelector('[name="title"]'), submit = event.submitter || form.querySelector('[type="submit"]');
        if (!title.value.trim()) { event.preventDefault(); title.focus(); return; }
        event.preventDefault();
        submit.disabled = true;
        fetch(form.action, { method: 'POST', body: new FormData(form), credentials: 'same-origin', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }).then(function (response) { return response.json(); }).then(function (data) {
            if (data.location) window.location.href = data.location;
            else { submit.disabled = false; window.alert('The recording could not be scheduled. Check the fields and try again.'); }
        }).catch(function () { submit.disabled = false; window.alert('The recording request failed. Please try again.'); });
    });
}());
