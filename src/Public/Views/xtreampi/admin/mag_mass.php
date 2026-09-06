<?php
require_once __DIR__ . '/_migration_helpers.php';

sc_m_bulk(
    'Mass Edit MAG Devices',
    'post.php?action=mag_mass&referer=mag_mass',
    'devices_selected',
    [
        ['name' => 'member_id', 'label' => 'Owner ID', 'type' => 'number'],
        ['name' => 'parent_password', 'label' => 'Adult PIN'],
        ['name' => 'exp_date', 'label' => 'Expiration', 'type' => 'date'],
        ['name' => 'admin_notes', 'label' => 'Admin notes', 'type' => 'textarea'],
        ['name' => 'reseller_notes', 'label' => 'Reseller notes', 'type' => 'textarea'],
        ['name' => 'force_server_id', 'label' => 'Forced server ID', 'type' => 'number'],
        ['name' => 'forced_country', 'label' => 'Forced country'],
        ['name' => 'is_isplock', 'label' => 'ISP lock', 'type' => 'checkbox'],
        ['name' => 'is_trial', 'label' => 'Trial device', 'type' => 'checkbox'],
        ['name' => 'lock_device', 'label' => 'Device lock', 'type' => 'checkbox'],
        ['name' => 'modern_theme', 'label' => 'Modern theme', 'type' => 'checkbox'],
        ['name' => 'bouquets', 'value_name' => 'bouquets_selected', 'label' => 'Bouquet IDs (JSON or comma-separated)', 'type' => 'textarea', 'placeholder' => '[1,2]'],
        ['name' => 'no_expire', 'label' => 'Never expire', 'type' => 'checkbox', 'apply' => false],
        ['name' => 'reset_isp_lock', 'label' => 'Reset current ISP', 'type' => 'checkbox', 'apply' => false],
        ['name' => 'reset_device_lock', 'label' => 'Reset device lock', 'type' => 'checkbox', 'apply' => false],
        ['name' => 'message_type', 'label' => 'Device event', 'type' => 'select', 'options' => ['' => 'No event', 'send_msg' => 'Send message', 'play_channel' => 'Play channel', 'reboot' => 'Reboot portal'], 'apply' => false],
        ['name' => 'message', 'label' => 'Event message', 'type' => 'textarea', 'apply' => false],
        ['name' => 'selected_channel', 'label' => 'Channel ID for play event', 'type' => 'number', 'apply' => false],
        ['name' => 'reboot_portal', 'label' => 'Reboot after event', 'type' => 'checkbox', 'apply' => false],
    ]
);
$xtreampiPageScripts = ['assets/xtreampi/migration.js'];
