(function () {
    'use strict';
    var root = document.querySelector('[data-sc-profile-editor]');
    if (!root) return;
    var form = root.querySelector('form'), key = form.elements.api_key;
    var adminUi = form.elements.admin_ui;
    var legacyAppearance = root.querySelector('[data-sc-legacy-appearance]');
    var legacyControls = root.querySelectorAll('[data-sc-legacy-appearance-control]');
    var legacyValues = root.querySelectorAll('[data-sc-legacy-value]');
    var legacyNote = root.querySelector('[data-sc-legacy-appearance-note]');
    var generate = root.querySelector('[data-sc-api-generate]'), clear = root.querySelector('[data-sc-api-clear]');
    if (generate && key) generate.addEventListener('click', function () {
        var alphabet = 'ABCDEF0123456789', value = '';
        for (var index = 0; index < 32; index += 1) value += alphabet.charAt(Math.floor(Math.random() * alphabet.length));
        key.value = value;
    });
    if (clear && key) clear.addEventListener('click', function () { key.value = ''; });
    function updateLegacyAppearance() {
        var isXtreamPi = adminUi && adminUi.value === 'xtreampi';
        legacyControls.forEach(function (control) { control.disabled = isXtreamPi; });
        legacyValues.forEach(function (value) { value.disabled = !isXtreamPi; });
        if (legacyAppearance) {
            legacyAppearance.classList.toggle('is-disabled', isXtreamPi);
            legacyAppearance.setAttribute('aria-disabled', isXtreamPi ? 'true' : 'false');
        }
        if (legacyNote) legacyNote.hidden = !isXtreamPi;
    }
    legacyControls.forEach(function (control) {
        control.addEventListener('change', function () {
            var value = root.querySelector('[data-sc-legacy-value="' + control.name + '"]');
            if (value) value.value = control.value;
        });
    });
    if (adminUi) {
        adminUi.addEventListener('change', updateLegacyAppearance);
        updateLegacyAppearance();
    }
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
                if (result && result.location) {
                    var destination = new URL(result.location, window.location.href);
                    if (adminUi) destination.searchParams.set('admin_ui', adminUi.value);
                    window.location.href = destination.toString();
                    return;
                }
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
