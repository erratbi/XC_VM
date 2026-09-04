(function () {
    'use strict';
    var root = document.querySelector('[data-sc-server-editor]'), form = root && root.querySelector('form');
    if (!form) return;
    function bindTags(editor) {
        var input = editor.querySelector('[data-server-tag-input]'), values = editor.querySelector('[data-server-tag-values]'), empty = editor.querySelector('[data-server-tag-empty]'), name = editor.dataset.name;
        function update() { empty.hidden = values.children.length > 0; }
        function remove(button) { button.onclick = function () { button.parentNode.remove(); update(); }; }
        values.querySelectorAll('button').forEach(remove);
        function add(raw) { var value = raw.trim(); if (!value || Array.from(values.querySelectorAll('input')).some(function (field) { return field.value === value; })) return; if (name.indexOf('ports') !== -1 && (!/^\d+$/.test(value) || Number(value) < 80 || Number(value) > 65535)) return; var token = document.createElement('span'), field = document.createElement('input'), button = document.createElement('button'); token.append(document.createTextNode(value)); field.type = 'hidden'; field.name = name; field.value = value; button.type = 'button'; button.textContent = '×'; button.setAttribute('aria-label', 'Remove'); token.append(field, button); values.append(token); remove(button); input.value = ''; update(); }
        input.addEventListener('keydown', function (event) { if (event.key === 'Enter' || event.key === ',') { event.preventDefault(); add(input.value.replace(/,$/, '')); } });
        input.addEventListener('blur', function () { add(input.value); }); update();
    }
    form.querySelectorAll('[data-server-tags]').forEach(bindTags);
    var regenerate = form.querySelector('[data-regenerate-ssl]');
    if (regenerate) regenerate.onclick = function () { form.elements.regenerate_ssl.value = '1'; form.requestSubmit(); };
    form.onsubmit = function (event) { event.preventDefault(); var error = form.querySelector('[data-sc-server-error]'), submit = form.querySelector('[type=submit]'); error.hidden = true; submit.disabled = true; fetch(form.action, { method: 'POST', body: new FormData(form), credentials: 'same-origin', headers: { Accept: 'application/json, text/javascript, */*; q=0.01', 'X-Requested-With': 'XMLHttpRequest' } }).then(function (response) { return response.text(); }).then(function (text) { var result; try { result = JSON.parse(text); } catch (ignore) { result = null; } if (result && result.location) { location.href = result.location; return; } submit.disabled = false; error.textContent = result && result.message ? result.message : 'The server could not be saved. Check the server addresses and settings.'; error.hidden = false; error.scrollIntoView({ behavior: 'smooth', block: 'nearest' }); }).catch(function () { submit.disabled = false; error.textContent = 'The server could not be saved. Please try again.'; error.hidden = false; }); };
}());
