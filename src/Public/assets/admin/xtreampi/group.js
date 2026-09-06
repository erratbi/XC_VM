(function () {
    'use strict';
    var form = document.querySelector('.sc-group-form'); if (!form) return;
    function inputs(name) { return Array.from(form.querySelectorAll('[data-sc-selection="' + name + '"] input')); }
    function selected(name) { return inputs(name).filter(function (input) { return input.checked; }).map(function (input) { return name === 'permissions' ? input.value : Number(input.value); }); }
    function setVisibility() { var admin = form.elements.is_admin && form.elements.is_admin.checked; var reseller = form.elements.is_reseller && form.elements.is_reseller.checked; form.querySelectorAll('[data-sc-admin-section]').forEach(function (section) { section.hidden = !admin; }); form.querySelectorAll('[data-sc-reseller-section]').forEach(function (section) { section.hidden = !reseller; }); }
    form.querySelectorAll('[data-sc-group-role]').forEach(function (input) { input.addEventListener('change', setVisibility); }); setVisibility();
    form.querySelectorAll('[data-sc-toggle-selection]').forEach(function (button) { button.addEventListener('click', function () { var fields = inputs(button.dataset.scToggleSelection), checked = fields.some(function (field) { return !field.checked; }); fields.forEach(function (field) { field.checked = checked; }); }); });
    form.querySelectorAll('[data-sc-permissions]').forEach(function (button) { button.addEventListener('click', function () { inputs('permissions').forEach(function (field) { field.checked = button.dataset.scPermissions === 'all'; }); }); });
    var permissionFilter = form.querySelector('[data-sc-permission-filter]');
    if (permissionFilter) {
        var permissionCards = Array.from(form.querySelectorAll('[data-sc-permission-card]'));
        var permissionCount = form.querySelector('[data-sc-permission-count]');
        var permissionEmpty = form.querySelector('[data-sc-permission-empty]');
        function filterPermissions() {
            var query = permissionFilter.value.trim().toLowerCase();
            var visible = 0;
            permissionCards.forEach(function (card) {
                var matches = !query || card.textContent.toLowerCase().indexOf(query) !== -1;
                card.hidden = !matches;
                if (matches) visible += 1;
            });
            if (permissionCount) permissionCount.textContent = visible + ' of ' + permissionCards.length + ' permissions';
            if (permissionEmpty) permissionEmpty.hidden = visible !== 0;
        }
        permissionFilter.addEventListener('input', filterPermissions);
        filterPermissions();
    }
    var subresellers = form.querySelector('[data-sc-subresellers-enabled]'); if (subresellers) subresellers.addEventListener('change', function () { if (!subresellers.checked) inputs('groups').forEach(function (field) { field.checked = false; }); });
    form.addEventListener('submit', function (event) {
        event.preventDefault(); form.elements.permissions_selected.value = JSON.stringify(selected('permissions')); form.elements.packages_selected.value = JSON.stringify(selected('packages')); form.elements.groups_selected.value = JSON.stringify(selected('groups')); form.elements.notice_html.value = form.querySelector('[data-sc-notice]').value;
        var error = form.querySelector('[data-sc-group-error]'), submit = form.querySelector('[type="submit"]'); error.hidden = true; submit.disabled = true;
        fetch(form.action, { method: 'POST', body: new FormData(form), credentials: 'same-origin', headers: { Accept: 'application/json, text/javascript, */*; q=0.01', 'X-Requested-With': 'XMLHttpRequest' } }).then(function (response) { return response.text(); }).then(function (text) { var result; try { result = JSON.parse(text); } catch (ignore) { result = null; } if (result && result.location) { window.location.href = result.location; return; } submit.disabled = false; if (result && String(result.status) === form.dataset.statusInvalidName) error.textContent = 'This group name is already in use. Please use another.'; else if (result && String(result.status) === form.dataset.statusInvalidInput) error.textContent = 'Required fields have not been populated. Please check the form.'; else error.textContent = result && result.message ? result.message : 'An error occurred while processing your request.'; error.hidden = false; error.scrollIntoView({ behavior: 'smooth', block: 'nearest' }); }).catch(function () { submit.disabled = false; error.textContent = 'The group could not be saved. Please try again.'; error.hidden = false; });
    });
}());
