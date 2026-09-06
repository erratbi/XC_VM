<?php
require_once __DIR__ . '/_migration_helpers.php';

sc_m_bulk(
    'Mass Edit Enigma Devices',
    'post.php?action=enigma_mass&referer=enigma_mass',
    'devices_selected',
    [
        ['name' => 'member_id', 'label' => 'Owner ID', 'type' => 'number'],
        ['name' => 'exp_date', 'label' => 'Expiration', 'type' => 'date'],
        ['name' => 'admin_notes', 'label' => 'Admin notes', 'type' => 'textarea'],
        ['name' => 'reseller_notes', 'label' => 'Reseller notes', 'type' => 'textarea'],
        ['name' => 'force_server_id', 'label' => 'Forced server ID', 'type' => 'number'],
        ['name' => 'forced_country', 'label' => 'Forced country'],
        ['name' => 'is_isplock', 'label' => 'ISP lock', 'type' => 'checkbox'],
        ['name' => 'is_trial', 'label' => 'Trial device', 'type' => 'checkbox'],
        ['name' => 'bouquets', 'value_name' => 'bouquets_selected', 'label' => 'Bouquet IDs (JSON or comma-separated)', 'type' => 'textarea', 'placeholder' => '[1,2]'],
        ['name' => 'no_expire', 'label' => 'Never expire', 'type' => 'checkbox', 'apply' => false],
        ['name' => 'reset_isp_lock', 'label' => 'Reset current ISP', 'type' => 'checkbox', 'apply' => false],
        ['name' => 'reset_device_lock', 'label' => 'Reset device lock', 'type' => 'checkbox', 'apply' => false],
    ]
);
$xtreampiPageScripts = ['assets/xtreampi/migration.js'];
