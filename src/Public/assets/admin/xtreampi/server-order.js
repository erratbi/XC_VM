(function () {
    'use strict';
    var root = document.querySelector('[data-sc-server-order]'), form = root && root.querySelector('form'), list = root && root.querySelector('[data-sc-server-order-list]');
    if (!form || !list) return;
    var dragging = null;
    function updatePositions() { Array.from(list.children).forEach(function (item, index) { item.querySelector('[data-sc-server-order-position]').textContent = String(index + 1); }); }
    list.addEventListener('dragstart', function (event) { var item = event.target.closest('li'); if (!item) return; dragging = item; item.classList.add('is-dragging'); event.dataTransfer.effectAllowed = 'move'; });
    list.addEventListener('dragover', function (event) { var target = event.target.closest('li'); if (!dragging || !target || target === dragging) return; event.preventDefault(); var box = target.getBoundingClientRect(); list.insertBefore(dragging, event.clientY < box.top + box.height / 2 ? target : target.nextElementSibling); });
    list.addEventListener('dragend', function () { if (dragging) dragging.classList.remove('is-dragging'); dragging = null; updatePositions(); });
    form.onsubmit = function (event) { event.preventDefault(); var submit = form.querySelector('[type=submit]'), error = form.querySelector('[data-sc-server-order-error]'); form.elements.server_order.value = JSON.stringify(Array.from(list.children).map(function (item) { return { id: Number(item.dataset.serverId) }; })); error.hidden = true; submit.disabled = true; fetch(form.action, { method: 'POST', body: new FormData(form), credentials: 'same-origin', headers: { Accept: 'application/json, text/javascript, */*; q=0.01', 'X-Requested-With': 'XMLHttpRequest' } }).then(function (response) { return response.text(); }).then(function (text) { var result; try { result = JSON.parse(text); } catch (ignore) { result = null; } if (result && result.location) { location.href = result.location; return; } submit.disabled = false; error.textContent = 'The server order could not be saved.'; error.hidden = false; }).catch(function () { submit.disabled = false; error.textContent = 'The server order could not be saved.'; error.hidden = false; }); };
    updatePositions();
}());
