<?php
require_once __DIR__ . '/_migration_helpers.php';
$rows = is_array($profiles ?? null) ? $profiles : [];
foreach ($rows as &$profileRow) {
    $profileRow['id'] = $profileRow['profile_id'] ?? ($profileRow['id'] ?? '');
    $profileRow['profile_name'] = $profileRow['profile_name'] ?? ($profileRow['name'] ?? '');
}
unset($profileRow);
sc_m_list('Transcoding Profiles', 'profile', 'profile', 'profile', $rows, ['id' => 'ID', 'profile_name' => 'Profile', 'gpu_device' => 'GPU', 'video_codec_cpu' => 'CPU codec', 'audio_codec' => 'Audio codec'], 'id', 'profile_id');
$xtreampiPageScripts = ['assets/xtreampi/migration.js'];
