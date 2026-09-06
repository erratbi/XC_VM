(function () {
    'use strict';

    var root = document.querySelector('[data-sc-bouquet-sort]');
    if (!root) return;
    var form = root.querySelector('form');
    var lists = root.querySelectorAll('[data-sc-bouquet-sort-list]');
    var dragging = null;

    function update(list) {
        Array.prototype.forEach.call(list.children, function (item, index) {
            item.querySelector('[data-sc-bouquet-sort-position]').textContent = String(index + 1);
        });
    }

    Array.prototype.forEach.call(lists, function (list) {
        update(list);
        list.addEventListener('dragstart', function (event) {
            var item = event.target.closest('li');
            if (!item) return;
            dragging = item;
            item.classList.add('is-dragging');
            event.dataTransfer.effectAllowed = 'move';
        });
        list.addEventListener('dragover', function (event) {
            var target = event.target.closest('li');
            if (!dragging || !target || dragging === target) return;
            event.preventDefault();
            var box = target.getBoundingClientRect();
            list.insertBefore(dragging, event.clientY < box.top + box.height / 2 ? target : target.nextElementSibling);
        });
        list.addEventListener('dragend', function () {
            if (dragging) dragging.classList.remove('is-dragging');
            dragging = null;
            update(list);
        });
    });

    Array.prototype.forEach.call(root.querySelectorAll('[data-sc-bouquet-sort-alpha]'), function (button) {
        button.addEventListener('click', function () {
            var list = root.querySelector('[data-sc-bouquet-sort-list="' + button.dataset.scBouquetSortAlpha + '"]');
            Array.prototype.slice.call(list.children).sort(function (a, b) {
                return a.querySelector('strong').textContent.localeCompare(b.querySelector('strong').textContent);
            }).forEach(function (item) { list.appendChild(item); });
            update(list);
        });
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        var order = {}, submit = form.querySelector('[type="submit"]'), error = form.querySelector('[data-sc-bouquet-sort-error]');
        Array.prototype.forEach.call(lists, function (list) {
            order[list.dataset.scBouquetSortList] = Array.prototype.map.call(list.children, function (item) { return Number(item.dataset.itemId); });
        });
        form.elements.stream_order_array.value = JSON.stringify(order);
        error.hidden = true;
        submit.disabled = true;
        fetch(form.action, { method: 'POST', body: new FormData(form), credentials: 'same-origin', headers: { 'Accept': 'application/json, text/javascript, */*; q=0.01', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (response) { return response.text(); })
            .then(function (text) {
                var result;
                try { result = JSON.parse(text); } catch (ignore) { result = null; }
                if (result && result.location) { window.location.href = result.location; return; }
                submit.disabled = false;
                error.textContent = 'The bouquet content order could not be saved.';
                error.hidden = false;
            }).catch(function () {
                submit.disabled = false;
                error.textContent = 'The bouquet content order could not be saved.';
                error.hidden = false;
            });
    });
}());
