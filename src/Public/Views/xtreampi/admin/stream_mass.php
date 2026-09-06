<?php
require_once __DIR__ . '/_migration_helpers.php';

sc_m_bulk(
    'Mass Edit Streams',
    'post.php?action=stream_mass&referer=stream_mass',
    'streams',
    [
        ['name' => 'category_id', 'value_name' => 'category_id[]', 'label' => 'Categories', 'type' => 'textarea', 'placeholder' => 'Category IDs, separated by commas or spaces'],
        ['name' => 'category_id_type', 'label' => 'Category operation', 'type' => 'select', 'options' => ['SET' => 'Replace', 'ADD' => 'Add', 'DEL' => 'Remove'], 'default' => 'SET', 'apply' => false],
        ['name' => 'bouquets', 'value_name' => 'bouquets[]', 'label' => 'Bouquets', 'type' => 'textarea', 'placeholder' => 'Bouquet IDs, separated by commas or spaces'],
        ['name' => 'bouquets_type', 'label' => 'Bouquet operation', 'type' => 'select', 'options' => ['SET' => 'Replace', 'ADD' => 'Add', 'DEL' => 'Remove'], 'default' => 'SET', 'apply' => false],
        ['name' => 'gen_timestamps', 'label' => 'Generate timestamps', 'type' => 'checkbox'],
        ['name' => 'read_native', 'label' => 'Read native', 'type' => 'checkbox'],
        ['name' => 'stream_all', 'label' => 'Stream all', 'type' => 'checkbox'],
        ['name' => 'allow_record', 'label' => 'Allow recording', 'type' => 'checkbox'],
        ['name' => 'direct_source', 'label' => 'Direct source', 'type' => 'checkbox'],
        ['name' => 'direct_proxy', 'label' => 'Direct proxy', 'type' => 'checkbox'],
        ['name' => 'rtmp_output', 'label' => 'RTMP output', 'type' => 'checkbox'],
        ['name' => 'fps_restart', 'label' => 'FPS restart', 'type' => 'checkbox'],
        ['name' => 'delay_minutes', 'label' => 'Delay (minutes)', 'type' => 'number'],
        ['name' => 'probesize_ondemand', 'label' => 'On-demand probe size', 'type' => 'number'],
        ['name' => 'fps_threshold', 'label' => 'FPS threshold', 'type' => 'number'],
        ['name' => 'custom_sid', 'label' => 'Custom SID'],
        ['name' => 'user_agent', 'label' => 'User-agent'],
        ['name' => 'http_proxy', 'label' => 'HTTP proxy'],
        ['name' => 'cookie', 'label' => 'Cookie'],
        ['name' => 'headers', 'label' => 'Headers', 'type' => 'textarea'],
        ['name' => 'transcode_profile_id', 'label' => 'Transcode profile', 'type' => 'number'],
        ['name' => 'days_to_restart', 'value_name' => 'days_to_restart[]', 'label' => 'Restart days', 'type' => 'textarea', 'placeholder' => '0–6 day numbers'],
        ['name' => 'time_to_restart', 'label' => 'Restart time (HH:MM)', 'type' => 'time', 'apply' => false],
        ['name' => 'server_tree', 'value_name' => 'server_tree_data', 'label' => 'Server assignment JSON', 'type' => 'textarea', 'placeholder' => '[{"id":1,"parent":"source"}]'],
        ['name' => 'server_type', 'label' => 'Server operation', 'type' => 'select', 'options' => ['SET' => 'Replace', 'ADD' => 'Add', 'DEL' => 'Remove'], 'default' => 'SET', 'apply' => false],
        ['name' => 'on_demand', 'value_name' => 'on_demand[]', 'label' => 'On-demand servers (with server assignment)', 'type' => 'textarea', 'placeholder' => 'Server IDs, separated by commas or spaces', 'apply' => false],
        ['name' => 'tv_archive_server_id', 'label' => 'Timeshift server ID', 'type' => 'number'],
        ['name' => 'vframes_server_id', 'label' => 'Thumbnail server ID', 'type' => 'number'],
        ['name' => 'llod', 'label' => 'Low latency on demand', 'type' => 'number'],
        ['name' => 'tv_archive_duration', 'label' => 'Timeshift duration', 'type' => 'number'],
        ['name' => 'restart_on_edit', 'label' => 'Restart after edit', 'type' => 'checkbox', 'apply' => false],
    ]
);
$xtreampiPageScripts = ['assets/xtreampi/migration.js'];
