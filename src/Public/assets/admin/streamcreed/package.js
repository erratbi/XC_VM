(function () {
    'use strict';
    var form = document.querySelector('.sc-package-form');
    if (!form) return;
    function selected(name) { return Array.from(form.querySelectorAll('[data-sc-selection="' + name + '"] input:checked')).map(function (input) { return Number(input.value); }); }
    form.querySelectorAll('[data-sc-toggle-selection]').forEach(function (button) { button.addEventListener('click', function () { var inputs = Array.from(form.querySelectorAll('[data-sc-selection="' + button.dataset.scToggleSelection + '"] input')); var check = inputs.some(function (input) { return !input.checked; }); inputs.forEach(function (input) { input.checked = check; }); }); });
    form.addEventListener('submit', function (event) {
        event.preventDefault();
        form.elements.groups_selected.value = JSON.stringify(selected('groups'));
        form.elements.bouquets_selected.value = JSON.stringify(selected('bouquets'));
        var error = form.querySelector('[data-sc-package-error]');
        var submit = form.querySelector('[type="submit"]');
        error.hidden = true; submit.disabled = true;
        fetch(form.action, { method: 'POST', body: new FormData(form), credentials: 'same-origin', headers: { Accept: 'application/json, text/javascript, */*; q=0.01', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (response) { return response.text(); })
            .then(function (text) { var result; try { result = JSON.parse(text); } catch (ignore) { result = null; } if (result && result.location) { window.location.href = result.location; return; } submit.disabled = false; error.textContent = result && String(result.status) === form.dataset.statusInvalidName ? 'This package name is already in use. Please use another.' : (result && result.message ? result.message : 'An error occurred while processing your request.'); error.hidden = false; error.scrollIntoView({ behavior: 'smooth', block: 'nearest' }); })
            .catch(function () { submit.disabled = false; error.textContent = 'The package could not be saved. Please try again.'; error.hidden = false; });
    });
}());
