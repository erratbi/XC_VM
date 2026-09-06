<?php
require_once __DIR__ . '/_migration_helpers.php';
$row = is_array($rISPArr ?? null) ? $rISPArr : null;
sc_m_editor($row ? 'Edit blocked ISP' : 'Add blocked ISP', 'post.php?action=isp&referer=isps', 'isps', $row, [['name' => 'isp', 'label' => 'ISP name', 'required' => true], ['name' => 'blocked', 'label' => 'Block immediately', 'type' => 'checkbox']], 'submit_isp', $row ? 'Edit' : 'Block');
$xtreampiPageScripts = ['assets/xtreampi/migration.js'];
