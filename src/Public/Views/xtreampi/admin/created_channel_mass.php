<?php
require_once __DIR__ . '/_migration_helpers.php';
$xtreampiProfiles = ['0' => 'Copy codecs', '-1' => 'Symlink files'];
foreach ((array) ($rTranscodeProfiles ?? []) as $xtreampiProfile) {
    $xtreampiProfileId = $xtreampiProfile['profile_id'] ?? ($xtreampiProfile['id'] ?? 0);
    $xtreampiProfiles[(string) $xtreampiProfileId] = (string) ($xtreampiProfile['profile_name'] ?? ($xtreampiProfile['name'] ?? 'Profile ' . $xtreampiProfileId));
}

sc_m_bulk(
    'Mass Edit Created Channels',
    'post.php?action=created_channel_mass&referer=created_channel_mass',
    'streams',
    [
        ['name' => 'category_id', 'value_name' => 'category_id[]', 'label' => 'Categories', 'type' => 'textarea', 'placeholder' => 'Category IDs, separated by commas or spaces'],
        ['name' => 'category_id_type', 'label' => 'Category operation', 'type' => 'select', 'options' => ['SET' => 'Replace', 'ADD' => 'Add', 'DEL' => 'Remove'], 'default' => 'SET', 'apply' => false],
        ['name' => 'bouquets', 'value_name' => 'bouquets[]', 'label' => 'Bouquets', 'type' => 'textarea', 'placeholder' => 'Bouquet IDs, separated by commas or spaces'],
        ['name' => 'bouquets_type', 'label' => 'Bouquet operation', 'type' => 'select', 'options' => ['SET' => 'Replace', 'ADD' => 'Add', 'DEL' => 'Remove'], 'default' => 'SET', 'apply' => false],
        ['name' => 'transcode_profile_id', 'label' => 'Transcode profile', 'type' => 'select', 'options' => $xtreampiProfiles, 'default' => '0'],
        ['name' => 'rtmp_output', 'label' => 'RTMP output', 'type' => 'checkbox'],
        ['name' => 'allow_record', 'label' => 'Allow recording', 'type' => 'checkbox'],
        ['name' => 'server_tree', 'value_name' => 'server_tree_data', 'label' => 'Server assignment JSON', 'type' => 'textarea', 'placeholder' => '[{"id":1,"parent":"source"}]'],
        ['name' => 'server_type', 'label' => 'Server operation', 'type' => 'select', 'options' => ['SET' => 'Replace', 'ADD' => 'Add', 'DEL' => 'Remove'], 'default' => 'SET', 'apply' => false],
        ['name' => 'on_demand', 'value_name' => 'on_demand[]', 'label' => 'On-demand servers (with server assignment)', 'type' => 'textarea', 'placeholder' => 'Server IDs, separated by commas or spaces', 'apply' => false],
        ['name' => 'reencode_on_edit', 'label' => 'Re-encode after edit', 'type' => 'checkbox', 'apply' => false],
        ['name' => 'restart_on_edit', 'label' => 'Restart after edit', 'type' => 'checkbox', 'apply' => false],
    ]
);
$xtreampiPageScripts = ['assets/xtreampi/migration.js'];
