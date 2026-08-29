<?php
$streamcreedSettings = is_array($rSettings ?? null) ? $rSettings : [];
?>
<section class="sc-settings">
	<div class="sc-page-heading"><div><p class="sc-eyebrow">System</p><h1>Settings</h1></div><div class="sc-page-actions"><a class="sc-button sc-button-secondary" href="settings?admin_ui=legacy"><i class="fe-corner-up-left" aria-hidden="true"></i> Open full settings</a></div></div>
	<div class="sc-data-panel"><div class="sc-settings-list">
		<div class="sc-settings-row"><span>Server name</span><strong><?php echo htmlspecialchars((string) ($streamcreedSettings['server_name'] ?? 'XC_VM'), ENT_QUOTES, 'UTF-8'); ?></strong></div>
		<div class="sc-settings-row"><span>Default entries per page</span><strong><?php echo intval($streamcreedSettings['default_entries'] ?? 25); ?></strong></div>
		<div class="sc-settings-row"><span>Redis handler</span><strong><?php echo !empty($streamcreedSettings['redis_handler']) ? 'Enabled' : 'Disabled'; ?></strong></div>
		<div class="sc-settings-row"><span>Stream grouping</span><strong><?php echo !empty($streamcreedSettings['streams_grouped']) ? 'Enabled' : 'Disabled'; ?></strong></div>
		<div class="sc-settings-row"><span>Panel version</span><strong><?php echo htmlspecialchars((string) ($BinVersion ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?></strong></div>
		<div class="sc-settings-row"><span>Operating system</span><strong><?php echo htmlspecialchars((string) ($BinOS ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?></strong></div>
	</div></div>
	<p class="sc-settings-note"><i class="fe-info" aria-hidden="true"></i> Advanced configuration remains available in the full legacy settings form while this area is being migrated.</p>
</section>
