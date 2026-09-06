<?php
use XcVm\Domain\Security\BlocklistService;
require_once __DIR__ . '/_migration_helpers.php';
$rows = is_array($isps ?? null) ? $isps : BlocklistService::getAllISPs();
sc_m_list("Blocked ISP's", 'isp', 'isp', 'isp', $rows, ['id' => 'ID', 'isp' => 'ISP', 'blocked' => 'Blocked'], 'id', 'isp_id');
$xtreampiPageScripts = ['assets/xtreampi/migration.js'];
