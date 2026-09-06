(function () {
    'use strict';
    var root = document.querySelector('[data-sc-radio-editor]');
    if (!root) return;
    var direct = root.querySelector('[data-sc-radio-direct]');
    var disabledNames = ['custom_ffmpeg', 'probesize_ondemand', 'user_agent', 'http_proxy', 'cookie', 'headers', 'force_input_acodec', 'days_to_restart[]', 'time_to_restart', 'on_demand[]', 'restart_on_edit'];
    function update() {
        disabledNames.forEach(function (name) {
            root.querySelectorAll('[name="' + name + '"]').forEach(function (field) { field.disabled = !!(direct && direct.checked); });
        });
    }
    if (direct) { direct.addEventListener('change', update); update(); }
}());
