<?php
use XcVm\Domain\Stream\StreamConfigRepository;

require_once __DIR__ . '/_migration_helpers.php';
$series = [];
foreach ((array) ($rSeries ?? []) as $item) {
    $series[(int) ($item['id'] ?? 0)] = (string) ($item['title'] ?? '');
}
$profiles = ['0' => 'Transcoding disabled'];
foreach (StreamConfigRepository::getTranscodeProfiles() as $profile) {
    $id = $profile['profile_id'] ?? ($profile['id'] ?? 0);
    $profiles[(string) $id] = (string) ($profile['profile_name'] ?? ($profile['name'] ?? ''));
}
sc_m_bulk(
    'Mass Edit Episodes',
    'post.php?action=episodes_mass&referer=episodes_mass',
    'streams',
    [
        ['name' => 'serie_name', 'label' => 'Series', 'type' => 'select', 'options' => $series],
        ['name' => 'direct_source', 'label' => 'Direct source', 'type' => 'checkbox'],
        ['name' => 'movie_symlink', 'label' => 'Create symlink', 'type' => 'checkbox'],
        ['name' => 'remove_subtitles', 'label' => 'Remove subtitles', 'type' => 'checkbox'],
        ['name' => 'read_native', 'label' => 'Read native frames', 'type' => 'checkbox'],
        ['name' => 'target_container', 'label' => 'Target container', 'type' => 'select', 'options' => array_combine(['mp4', 'mkv', 'avi', 'mpg', 'flv', '3gp', 'm4v', 'wmv', 'mov', 'ts'], ['MP4', 'MKV', 'AVI', 'MPG', 'FLV', '3GP', 'M4V', 'WMV', 'MOV', 'TS'])],
        ['name' => 'transcode_profile_id', 'label' => 'Transcode profile', 'type' => 'select', 'options' => $profiles, 'default' => '0'],
        ['name' => 'server_tree', 'value_name' => 'server_tree_data', 'label' => 'Server assignment JSON', 'type' => 'textarea', 'placeholder' => '[{"id":1,"parent":"source"}]'],
        ['name' => 'server_type', 'label' => 'Server operation', 'type' => 'select', 'options' => ['SET' => 'Replace', 'ADD' => 'Add', 'DEL' => 'Remove'], 'default' => 'SET', 'apply' => false],
        ['name' => 'reencode_on_edit', 'label' => 'Re-encode after edit', 'type' => 'checkbox', 'apply' => false],
        ['name' => 'reprocess_tmdb', 'label' => 'Reprocess TMDB metadata', 'type' => 'checkbox', 'apply' => false],
        ['name' => 'restart_on_edit', 'label' => 'Restart after edit', 'type' => 'checkbox', 'apply' => false],
    ]
);
$xtreampiPageScripts = ['assets/xtreampi/migration.js'];
