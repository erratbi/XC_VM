<?php
use XcVm\Domain\Security\BlocklistService;
require_once __DIR__ . '/_migration_helpers.php';
$rows = BlocklistService::getAllUserAgents();
sc_m_list('Blocked User-Agents', 'useragent', 'useragent', 'useragent', is_array($rows) ? $rows : [], ['id' => 'ID', 'user_agent' => 'User-agent', 'exact_match' => 'Exact match'], 'id', 'ua_id');
$xtreampiPageScripts = ['assets/xtreampi/migration.js'];
