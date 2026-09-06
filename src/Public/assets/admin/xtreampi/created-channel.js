(function () {
    'use strict';
    var root = document.querySelector('[data-sc-created-channel]'); if (!root) return;
    var form = root.querySelector('form'), payload = root.querySelector('[data-sc-server-tree]'), videoPayload = root.querySelector('[data-sc-video-files]'), videoInput = root.querySelector('[data-sc-video-files-input]');
    form.addEventListener('submit', function () {
        var nodes = [{ id: 'source', parent: '#', text: 'Online' }, { id: 'offline', parent: '#', text: 'Offline' }];
        root.querySelectorAll('[data-sc-server][type="checkbox"]:checked').forEach(function (input) { nodes.push({ id: input.value, parent: input.getAttribute('data-parent') || 'offline', text: input.parentNode.textContent.trim() }); });
        payload.value = JSON.stringify(nodes);
        if (videoPayload && videoInput) {
            videoPayload.value = JSON.stringify(videoInput.value.split(/\r?\n/).map(function (value) { return value.trim(); }).filter(Boolean));
        }
    });
}());
