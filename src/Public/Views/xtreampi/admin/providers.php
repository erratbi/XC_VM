<?php
require_once __DIR__ . '/_migration_helpers.php';
$rows = is_array($providers ?? null) ? $providers : [];
foreach ($rows as &$providerRow) {
    $data = json_decode((string) ($providerRow['data'] ?? '{}'), true) ?: [];
    $providerRow['status'] = !empty($providerRow['enabled']) ? (!empty($providerRow['status']) ? 'Online' : 'Offline') : 'Disabled';
    $providerRow['endpoint'] = ($providerRow['ip'] ?? '') . ':' . ($providerRow['port'] ?? '');
    $providerRow['connections'] = ($data['active_connections'] ?? 0) . ' / ' . (($data['max_connections'] ?? 0) ?: '∞');
    $providerRow['streams'] = $data['streams'] ?? 0;
    $providerRow['movies'] = $data['movies'] ?? 0;
    $providerRow['series'] = $data['series'] ?? 0;
}
unset($providerRow);
sc_m_list('Stream Providers', 'provider', 'provider', 'provider', $rows, ['id' => 'ID', 'status' => 'Status', 'name' => 'Provider', 'endpoint' => 'Endpoint', 'username' => 'Username', 'connections' => 'Connections', 'streams' => 'Streams', 'movies' => 'Movies', 'series' => 'Series'], 'id', 'id');
$xtreampiPageScripts = ['assets/xtreampi/migration.js'];
