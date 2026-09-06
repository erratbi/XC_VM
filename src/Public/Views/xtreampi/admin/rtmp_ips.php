<?php
require_once __DIR__ . '/_migration_helpers.php';
$rows = is_array($ips ?? null) ? $ips : [];
sc_m_list("RTMP IP's", 'rtmp_ip', 'rtmp_ip', 'rtmp_ip', $rows, ['id' => 'ID', 'ip' => 'IP address', 'password' => 'Password', 'push' => 'Push', 'pull' => 'Pull'], 'id', 'ip');
$xtreampiPageScripts = ['assets/xtreampi/migration.js'];
