<?php
require_once __DIR__ . '/_migration_helpers.php';
$row = is_array($rProvider ?? null) ? $rProvider : [];
sc_m_editor($row ? 'Edit stream provider' : 'Add stream provider', 'post.php?action=provider&referer=providers', 'providers', $row, [['name' => 'name', 'label' => 'Provider name', 'required' => true], ['name' => 'ip', 'label' => 'Server IP or domain', 'required' => true], ['name' => 'port', 'label' => 'Port', 'type' => 'number', 'default' => '80', 'required' => true], ['name' => 'username', 'label' => 'Username', 'required' => true], ['name' => 'password', 'label' => 'Password (kept server-side)', 'type' => 'password', 'required' => true], ['name' => 'enabled', 'label' => 'Enabled', 'type' => 'checkbox', 'default' => 1], ['name' => 'ssl', 'label' => 'Use SSL', 'type' => 'checkbox'], ['name' => 'legacy', 'label' => 'Legacy XC API', 'type' => 'checkbox'], ['name' => 'hls', 'label' => 'Use HLS', 'type' => 'checkbox']], 'submit_provider', $row ? 'Edit' : 'Add');
if ($row && !empty($row['id'])):
?>
<section class="sc-form-section sc-provider-actions" data-sc-provider-id="<?php echo (int) $row['id']; ?>">
    <div class="sc-page-actions"><button class="sc-button sc-button-secondary" type="button" data-sc-provider-import><i class="fe-download" aria-hidden="true"></i> Import EPG source</button></div>
    <p class="sc-section-copy" data-sc-provider-status>Import the provider XMLTV source using the existing provider handler.</p>
</section>
<?php
endif;
$xtreampiPageScripts = ['assets/xtreampi/migration.js'];
