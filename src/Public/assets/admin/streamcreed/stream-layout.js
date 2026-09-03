(function () {
    'use strict';

    var root = document.querySelector('[data-sc-stream-editor]');
    var form = root && root.querySelector('form');
    if (!form) return;

    var sectionDetails = {
        'Import Playlist': { icon: 'fe-upload', description: 'Choose the playlist and import behavior.', open: true },
        'Stream Details': { icon: 'fe-edit-2', description: 'Name, artwork, categories, bouquets, and notes.', open: true },
        'Sources': { icon: 'fe-link', description: 'Source URLs, priority, and stream probing.', open: true },
        'Processing': { icon: 'fe-sliders', description: 'Transcoding and codec behavior.' },
        'Connection': { icon: 'fe-globe', description: 'Provider request identity, proxy, cookies, and headers.' },
        'Adaptive & Tracks': { icon: 'fe-layers', description: 'Adaptive variants, title synchronization, and exact track mapping.' },
        'EPG & Archive': { icon: 'fe-calendar', description: 'Programme guide matching and catch-up archive.' },
        'Reliability': { icon: 'fe-activity', description: 'Low latency, delays, probes, and automatic restarts.' },
        'RTMP Push': { icon: 'fe-send', description: 'External RTMP delivery destinations.' },
        'Server Placement': { icon: 'fe-server', description: 'Execution servers and upstream topology.' }
    };
    var sections = Array.from(form.children).filter(function (element) {
        return element.classList && element.classList.contains('sc-form-section');
    });
    if (!sections.length) return;
    form.classList.add('sc-stream-organized-form', 'sc-stream-accordion-form');

    var customMap = form.elements.custom_map;
    if (customMap) {
        var mapLabel = customMap.closest('label');
        var mapPanel = mapLabel && mapLabel.nextElementSibling;
        var processing = sections.find(function (section) {
            var heading = section.querySelector('h2');
            return heading && heading.textContent.trim() === 'Processing';
        });
        var grid = processing && processing.querySelector('.sc-form-grid');
        if (mapLabel && grid) {
            var divider = document.createElement('div');
            divider.className = 'sc-form-span sc-form-divider';
            divider.innerHTML = '<strong>Track selection</strong><small>Choose exactly which video, audio, or subtitle tracks should play.</small>';
            grid.append(divider, mapLabel);
            mapLabel.classList.add('sc-form-span');
            if (mapPanel && mapPanel.classList.contains('sc-map-picker')) grid.append(mapPanel);
        }
    }

    var processingSection = sections.find(function (section) {
        var heading = section.querySelector('h2');
        return heading && heading.textContent.trim() === 'Processing';
    });
    if (processingSection) {
        processingSection.classList.add('sc-processing-section');
        var processingGrid = processingSection.querySelector('.sc-form-grid');
        var connectionSection = document.createElement('section');
        var connectionGrid = document.createElement('div');
        var connectionModes = document.createElement('div');
        var adaptiveSection = document.createElement('section');
        var adaptiveGrid = document.createElement('div');
        connectionSection.className = 'sc-form-section';
        connectionSection.classList.add('sc-connection-section');
        connectionSection.innerHTML = '<h2>Connection</h2>';
        connectionGrid.className = 'sc-form-grid';
        connectionModes.className = 'sc-package-options sc-line-switches sc-connection-modes';
        adaptiveSection.className = 'sc-form-section';
        adaptiveSection.classList.add('sc-adaptive-section');
        adaptiveSection.innerHTML = '<h2>Adaptive &amp; Tracks</h2>';
        adaptiveGrid.className = 'sc-form-grid';
        connectionSection.append(connectionGrid, connectionModes);
        adaptiveSection.append(adaptiveGrid);

        ['user_agent', 'http_proxy', 'cookie', 'headers'].forEach(function (name) {
            var field = form.elements[name];
            var fieldLabel = field && field.closest('label');
            if (fieldLabel) connectionGrid.append(fieldLabel);
        });
        ['direct_source', 'direct_proxy'].forEach(function (name) {
            var field = form.elements[name];
            var card = field && field.closest('.sc-selection-card');
            if (card) connectionModes.append(card);
        });
        var adaptiveLinks = form.querySelector('[data-async-picker][data-action="adaptivelist"]');
        var titleSync = form.querySelector('[data-async-picker][data-action="titlesync"]');
        if (adaptiveLinks) adaptiveGrid.append(adaptiveLinks);
        if (titleSync) adaptiveGrid.append(titleSync);
        if (customMap) {
            var customMapLabel = customMap.closest('label');
            var mapResults = customMapLabel && customMapLabel.nextElementSibling;
            var mapDivider = processingGrid && processingGrid.querySelector('.sc-form-divider');
            if (mapDivider) adaptiveGrid.append(mapDivider);
            if (customMapLabel) adaptiveGrid.append(customMapLabel);
            if (mapResults && mapResults.classList.contains('sc-map-picker')) adaptiveGrid.append(mapResults);
        }
        processingSection.insertAdjacentElement('afterend', adaptiveSection);
        processingSection.insertAdjacentElement('afterend', connectionSection);
        var processingIndex = sections.indexOf(processingSection);
        sections.splice(processingIndex + 1, 0, connectionSection, adaptiveSection);
        if (processingGrid && !processingGrid.children.length) processingGrid.remove();
    }

    var reliability = sections.find(function (section) {
        var heading = section.querySelector('h2');
        return heading && heading.textContent.trim() === 'Reliability';
    });
    if (reliability) {
        var restartTime = form.elements.time_to_restart && form.elements.time_to_restart.closest('label');
        var fpsThreshold = form.elements.fps_threshold && form.elements.fps_threshold.closest('label');
        var restartDays = reliability.querySelector('.sc-stream-subfield');
        var fpsRestart = form.elements.fps_restart && form.elements.fps_restart.closest('.sc-selection-card');
        var rtmpOutput = form.elements.rtmp_output && form.elements.rtmp_output.closest('.sc-selection-card');
        if (form.elements.fps_restart && form.elements.fps_threshold) {
            var syncFpsThreshold = function () {
                form.elements.fps_threshold.disabled = !form.elements.fps_restart.checked;
            };
            form.elements.fps_restart.addEventListener('change', syncFpsThreshold);
            syncFpsThreshold();
        }
        if (restartDays) {
            var daysContainer = restartDays.querySelector('.sc-restart-days');
            var dayInputs = daysContainer ? Array.from(daysContainer.querySelectorAll('input[name="days_to_restart[]"]')) : [];
            if (daysContainer && dayInputs.length) {
                var dayTitle = restartDays.querySelector(':scope > strong');
                var dayPicker = document.createElement('div');
                var daySearch = document.createElement('input');
                var dayOptions = document.createElement('div');
                var dayTokens = document.createElement('div');
                if (dayTitle) dayTitle.textContent = 'Restart days';
                dayPicker.className = 'sc-day-picker';
                daySearch.type = 'search';
                daySearch.placeholder = 'Search and add days';
                daySearch.autocomplete = 'off';
                dayOptions.className = 'sc-owner-options';
                dayOptions.hidden = true;
                dayTokens.className = 'sc-token-list';
                daysContainer.replaceWith(dayPicker);
                dayPicker.append(daySearch, dayOptions, dayTokens);
                dayInputs.forEach(function (input) { input.hidden = true; dayPicker.append(input); });

                function renderDays() {
                    dayTokens.replaceChildren();
                    dayInputs.filter(function (input) { return input.checked; }).forEach(function (input) {
                        var token = document.createElement('span');
                        var remove = document.createElement('button');
                        token.append(document.createTextNode(input.value));
                        remove.type = 'button';
                        remove.textContent = '×';
                        remove.setAttribute('aria-label', 'Remove ' + input.value);
                        remove.onclick = function () { input.checked = false; renderDays(); renderDayOptions(); };
                        token.append(remove);
                        dayTokens.append(token);
                    });
                }
                function renderDayOptions() {
                    var term = daySearch.value.trim().toLowerCase();
                    dayOptions.replaceChildren();
                    dayInputs.filter(function (input) {
                        return !input.checked && (!term || input.value.toLowerCase().includes(term));
                    }).forEach(function (input) {
                        var option = document.createElement('button');
                        option.type = 'button';
                        option.textContent = input.value;
                        option.onclick = function () { input.checked = true; daySearch.value = ''; renderDays(); renderDayOptions(); daySearch.focus(); };
                        dayOptions.append(option);
                    });
                    dayOptions.hidden = !dayOptions.children.length;
                }
                daySearch.onfocus = renderDayOptions;
                daySearch.oninput = renderDayOptions;
                daySearch.onkeydown = function (event) {
                    if (event.key === 'Enter' && dayOptions.firstElementChild) { event.preventDefault(); dayOptions.firstElementChild.click(); }
                };
                document.addEventListener('click', function (event) { if (!dayPicker.contains(event.target)) dayOptions.hidden = true; });
                renderDays();
            }
        }
        var restartLayout = document.createElement('div');
        var scheduled = document.createElement('section');
        var health = document.createElement('section');
        restartLayout.className = 'sc-restart-layout';
        scheduled.className = 'sc-restart-card';
        health.className = 'sc-restart-card';
        scheduled.innerHTML = '<header><i class="fe-calendar"></i><span><strong>Scheduled restart</strong><small>Restart on selected days at the chosen time.</small></span></header>';
        health.innerHTML = '<header><i class="fe-activity"></i><span><strong>FPS-loss restart</strong><small>Restart when the frame rate falls below your threshold.</small></span></header>';
        var scheduledBody = document.createElement('div');
        var healthBody = document.createElement('div');
        scheduledBody.className = 'sc-restart-card-body is-schedule';
        healthBody.className = 'sc-restart-card-body';
        if (restartDays) scheduledBody.append(restartDays);
        if (restartTime) scheduledBody.append(restartTime);
        if (fpsRestart) healthBody.append(fpsRestart);
        if (fpsThreshold) healthBody.append(fpsThreshold);
        scheduled.append(scheduledBody);
        health.append(healthBody);
        restartLayout.append(scheduled, health);
        reliability.append(restartLayout);

        var rtmpSection = sections.find(function (section) {
            var heading = section.querySelector('h2');
            return heading && heading.textContent.trim() === 'RTMP Push';
        });
        if (rtmpOutput && rtmpSection) {
            var rtmpToggle = document.createElement('div');
            rtmpToggle.className = 'sc-rtmp-enable';
            rtmpToggle.append(rtmpOutput);
            var rtmpHeading = rtmpSection.querySelector('.sc-section-heading');
            if (rtmpHeading) rtmpHeading.insertAdjacentElement('afterend', rtmpToggle);
            else rtmpSection.append(rtmpToggle);
        }
        reliability.querySelectorAll('.sc-package-options').forEach(function (container) {
            if (!container.children.length) container.remove();
        });
    }

    function setOpen(section, open) {
        section.classList.toggle('is-open', open);
        var trigger = section.querySelector(':scope > .sc-accordion-trigger');
        var body = section.querySelector(':scope > .sc-accordion-body');
        if (trigger) trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (body) body.hidden = !open;
    }

    sections.forEach(function (section, index) {
        var oldHeading = section.querySelector('h2');
        var title = oldHeading ? oldHeading.textContent.trim() : 'Section ' + (index + 1);
        var details = sectionDetails[title] || { icon: 'fe-circle', description: '' };
        var trigger = document.createElement('button');
        var body = document.createElement('div');
        trigger.className = 'sc-accordion-trigger';
        trigger.type = 'button';
        trigger.innerHTML = '<i class="' + details.icon + '"></i><span><strong>' + title + '</strong><small>' + details.description + '</small></span><i class="fe-chevron-down sc-accordion-chevron"></i>';
        body.className = 'sc-accordion-body';
        while (section.firstChild) body.append(section.firstChild);
        section.append(trigger, body);
        section.classList.add('sc-stream-section-card');

        var directHeading = body.querySelector(':scope > h2');
        if (directHeading) directHeading.remove();
        var compoundHeading = body.querySelector(':scope > .sc-section-heading');
        if (compoundHeading) {
            var headingCopy = compoundHeading.querySelector(':scope > div:first-child');
            if (headingCopy) headingCopy.remove();
            compoundHeading.classList.add('sc-accordion-actions');
        }
        trigger.onclick = function () { setOpen(section, !section.classList.contains('is-open')); };
        setOpen(section, !!details.open);
    });

    form.addEventListener('invalid', function (event) {
        var section = event.target.closest('.sc-form-section');
        if (section) {
            setOpen(section, true);
            section.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }, true);
}());
