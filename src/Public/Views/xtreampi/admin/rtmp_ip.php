<?php
require_once __DIR__ . '/_migration_helpers.php';
$row = is_array($rIPArr ?? null) ? $rIPArr : null;
sc_m_editor($row ? 'Edit RTMP IP' : 'Add RTMP IP', 'post.php?action=rtmp_ip&referer=rtmp_ips', 'rtmp_ips', $row, [['name' => 'ip', 'label' => 'IP address', 'required' => true], ['name' => 'password', 'label' => 'Password (blank generates one)', 'type' => 'text'], ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea'], ['name' => 'push', 'label' => 'Allow push', 'type' => 'checkbox'], ['name' => 'pull', 'label' => 'Allow pull', 'type' => 'checkbox']], 'submit_ip', $row ? 'Edit' : 'Add');
$xtreampiPageScripts = ['assets/xtreampi/migration.js'];
