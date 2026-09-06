(function () {
    'use strict';
    var root = document.querySelector('[data-sc-profile-editor]');
    if (!root) return;
    var form = root.querySelector('form'), key = form.elements.api_key;
    var generate = root.querySelector('[data-sc-api-generate]'), clear = root.querySelector('[data-sc-api-clear]');
    if (generate && key) generate.addEventListener('click', function () {
        var alphabet = 'ABCDEF0123456789', value = '';
        for (var index = 0; index < 32; index += 1) value += alphabet.charAt(Math.floor(Math.random() * alphabet.length));
        key.value = value;
    });
    if (clear && key) clear.addEventListener('click', function () { key.value = ''; });
    form.addEventListener('submit', function (event) {
        event.preventDefault();
        var error = root.querySelector('[data-sc-profile-error]'), submit = form.querySelector('[type="submit"]');
        error.hidden = true;
        submit.disabled = true;
        fetch(form.action, { method: 'POST', body: new FormData(form), credentials: 'same-origin', headers: { 'Accept': 'application/json, text/javascript, */*; q=0.01', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (response) { return response.text(); })
            .then(function (text) {
                var result;
                try { result = JSON.parse(text); } catch (ignore) { result = null; }
                if (result && result.location) { window.location.href = result.location; return; }
                submit.disabled = false;
                error.textContent = 'The profile could not be saved. Please check your details and try again.';
                error.hidden = false;
            }).catch(function () {
                submit.disabled = false;
                error.textContent = 'The profile could not be saved. Please try again.';
                error.hidden = false;
            });
    });
}());
