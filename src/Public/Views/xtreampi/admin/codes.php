<?php
require_once __DIR__ . '/_migration_helpers.php';
$rows = is_array($codes ?? null) ? $codes : [];
$types = ['Admin', 'Reseller', 'Ministra', 'Admin API', 'Reseller API', 'Ministra XtreamPi - Disbanded', 'Web Player'];
foreach ($rows as &$codeRow) {
    $codeRow['type'] = $types[(int) ($codeRow['type'] ?? 0)] ?? 'Unknown';
    $codeRow['enabled'] = !empty($codeRow['enabled']) ? 'Yes' : 'No';
}
unset($codeRow);
sc_m_list('Access Codes', 'code', 'code', 'code', $rows, ['id' => 'ID', 'code' => 'Code', 'type' => 'Type', 'enabled' => 'Enabled'], 'id', 'code_id');
$xtreampiPageScripts = ['assets/xtreampi/migration.js'];
