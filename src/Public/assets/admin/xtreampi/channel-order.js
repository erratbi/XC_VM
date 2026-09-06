(function () {
    'use strict';
    var root = document.querySelector('[data-sc-channel-order]'); if (!root) return;
    var drag;
    root.querySelectorAll('[data-sc-order-list]').forEach(function (list) {
        list.addEventListener('dragstart', function (event) { drag = event.target.closest('li'); });
        list.addEventListener('dragover', function (event) { event.preventDefault(); var target = event.target.closest('li'); if (drag && target && drag !== target) list.insertBefore(drag, target); });
    });
    var form = root.querySelector('form');
    form.addEventListener('submit', function () { var order = []; root.querySelectorAll('[data-sc-order-list]').forEach(function (list) { list.querySelectorAll('li[data-id]').forEach(function (item) { order.push(item.getAttribute('data-id')); }); }); form.querySelector('[data-sc-order-payload]').value = JSON.stringify(order); });
}());
