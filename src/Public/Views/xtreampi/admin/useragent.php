<?php
require_once __DIR__ . '/_migration_helpers.php';
$row = is_array($rUAArr ?? null) ? $rUAArr : null;
sc_m_editor($row ? 'Edit blocked user-agent' : 'Add blocked user-agent', 'post.php?action=useragent&referer=useragents', 'useragents', $row, [['name' => 'user_agent', 'label' => 'User-agent', 'required' => true], ['name' => 'exact_match', 'label' => 'Exact match', 'type' => 'checkbox']], 'submit_ua', $row ? 'Edit' : 'Block');
$xtreampiPageScripts = ['assets/xtreampi/migration.js'];
