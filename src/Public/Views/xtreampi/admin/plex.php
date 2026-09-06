<?php

use XcVm\Core\Auth\Authorization;

$xtreampiPageScripts = ['assets/xtreampi/service-sync.js'];
$xtreampiPlexServers = is_array($rPlexServers ?? null) ? $rPlexServers : [];
$xtreampiServers = is_array($rServers ?? null) ? $rServers : [];
$xtreampiCanManagePlex = Authorization::check('adv', 'folder_watch_settings');
$xtreampiEscape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<section class="sc-service-sync" data-sc-service-sync data-item-name="library" data-enable-url="api?action=enable_plex" data-disable-url="api?action=disable_plex" data-kill-url="api?action=kill_plex" data-item-url="api?action=library">
	<div class="sc-page-heading">
		<div><p class="sc-eyebrow">Content automation</p><h1>Plex Sync</h1><p class="sc-section-copy">Sync selected Plex libraries into your XtreamPi movie and series catalogues.</p></div>
		<div class="sc-page-actions"><a class="sc-button sc-button-primary" href="plex_add"><i class="fe-plus" aria-hidden="true"></i> Add library</a></div>
	</div>

	<?php if (isset($_STATUS) && intval($_STATUS) === STATUS_SUCCESS): ?>
		<div class="sc-notice sc-notice-success" role="status"><i class="fe-check-circle" aria-hidden="true"></i><div><strong>Library saved</strong><p>The library will be scanned during the next Plex Sync run.</p></div></div>
	<?php endif; ?>

	<div class="sc-service-sync-actions">
		<?php if ($xtreampiCanManagePlex): ?><button type="button" class="sc-button sc-button-secondary" data-sc-sync-global="enable">Enable all</button><button type="button" class="sc-button sc-button-secondary" data-sc-sync-global="disable">Disable all</button><?php endif; ?>
		<button type="button" class="sc-button sc-button-secondary" data-sc-sync-global="kill">Kill running processes</button>
	</div>

	<div class="sc-data-panel"><div class="sc-table-scroll"><table class="sc-data-table"><thead><tr><th>ID</th><th>Status</th><th>Plex server</th><th>Target server</th><th>Library</th><th>Last run</th><th><span class="sc-visually-hidden">Actions</span></th></tr></thead><tbody data-sc-sync-rows>
		<?php if (!$xtreampiPlexServers): ?><tr><td class="sc-table-state" colspan="7">No Plex libraries have been configured.</td></tr><?php else: foreach ($xtreampiPlexServers as $xtreampiPlex):
			$xtreampiId = intval($xtreampiPlex['id'] ?? 0);
			$xtreampiLastRun = intval($xtreampiPlex['last_run'] ?? 0);
			$xtreampiLibraryName = 'Unknown library';
			foreach ((array) json_decode((string) ($xtreampiPlex['plex_libraries'] ?? '[]'), true) as $xtreampiLibrary) if (intval($xtreampiLibrary['key'] ?? 0) === intval($xtreampiPlex['directory'] ?? 0)) { $xtreampiLibraryName = (string) ($xtreampiLibrary['title'] ?? $xtreampiLibraryName); break; }
			$xtreampiTargetServer = $xtreampiServers[intval($xtreampiPlex['server_id'] ?? 0)]['server_name'] ?? 'Unknown server';
		?>
			<tr data-sc-sync-row="<?php echo $xtreampiId; ?>"><td class="sc-table-secondary">#<?php echo $xtreampiId; ?></td><td><span class="sc-row-status <?php echo !empty($xtreampiPlex['active']) ? 'is-active' : 'is-disabled'; ?>"><?php echo !empty($xtreampiPlex['active']) ? 'Active' : 'Disabled'; ?></span></td><td class="sc-table-secondary"><?php echo $xtreampiEscape($xtreampiPlex['plex_ip'] ?? ''); ?></td><td class="sc-table-secondary"><?php echo $xtreampiEscape($xtreampiTargetServer); ?></td><td><?php echo $xtreampiEscape($xtreampiLibraryName); ?></td><td class="sc-table-secondary"><?php echo $xtreampiLastRun > 0 ? $xtreampiEscape(date('Y-m-d H:i:s', $xtreampiLastRun)) : 'Never'; ?></td><td class="sc-table-actions"><a class="sc-row-action" href="plex_add?id=<?php echo $xtreampiId; ?>">Edit</a><button type="button" class="sc-row-action is-warning" data-sc-sync-action="force" data-sc-sync-id="<?php echo $xtreampiId; ?>">Sync now</button><button type="button" class="sc-row-action is-danger" data-sc-sync-action="delete" data-sc-sync-id="<?php echo $xtreampiId; ?>">Delete</button></td></tr>
		<?php endforeach; endif; ?>
	</tbody></table></div></div>
	<p class="sc-service-sync-result" data-sc-sync-result role="status" aria-live="polite"></p>
</section>
