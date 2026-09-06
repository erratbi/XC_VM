<?php

use XcVm\Core\Auth\Authorization;

$xtreampiPageScripts = ['assets/xtreampi/service-sync.js'];
$xtreampiWatchFolders = is_array($rFolders ?? null) ? $rFolders : [];
$xtreampiServers = is_array($rServers ?? null) ? $rServers : [];
$xtreampiCanManageWatch = Authorization::check('adv', 'folder_watch_settings');
$xtreampiEscape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<section class="sc-service-sync" data-sc-service-sync data-item-name="folder" data-enable-url="api?action=enable_watch" data-disable-url="api?action=disable_watch" data-kill-url="api?action=kill_watch" data-item-url="api?action=folder">
	<div class="sc-page-heading">
		<div><p class="sc-eyebrow">Content automation</p><h1>Watch folders</h1><p class="sc-section-copy">Scan configured directories and process new movies or series into your library.</p></div>
		<div class="sc-page-actions"><a class="sc-button sc-button-primary" href="watch_add"><i class="fe-plus" aria-hidden="true"></i> Add folder</a></div>
	</div>

	<?php if (isset($_STATUS) && intval($_STATUS) === STATUS_SUCCESS): ?>
		<div class="sc-notice sc-notice-success" role="status"><i class="fe-check-circle" aria-hidden="true"></i><div><strong>Folder saved</strong><p>The folder will be scanned during the next Watch run.</p></div></div>
	<?php endif; ?>

	<div class="sc-service-sync-actions">
		<?php if ($xtreampiCanManageWatch): ?><button type="button" class="sc-button sc-button-secondary" data-sc-sync-global="enable">Enable all</button><button type="button" class="sc-button sc-button-secondary" data-sc-sync-global="disable">Disable all</button><?php endif; ?>
		<button type="button" class="sc-button sc-button-secondary" data-sc-sync-global="kill">Kill running processes</button>
	</div>

	<div class="sc-data-panel"><div class="sc-table-scroll"><table class="sc-data-table"><thead><tr><th>ID</th><th>Status</th><th>Type</th><th>Server</th><th>Directory</th><th>Last run</th><th><span class="sc-visually-hidden">Actions</span></th></tr></thead><tbody data-sc-sync-rows>
		<?php if (!$xtreampiWatchFolders): ?><tr><td class="sc-table-state" colspan="7">No watch folders have been configured.</td></tr><?php else: foreach ($xtreampiWatchFolders as $xtreampiFolder):
			$xtreampiId = intval($xtreampiFolder['id'] ?? 0);
			$xtreampiType = (string) ($xtreampiFolder['type'] ?? '');
			$xtreampiLastRun = intval($xtreampiFolder['last_run'] ?? 0);
			$xtreampiServer = $xtreampiServers[intval($xtreampiFolder['server_id'] ?? 0)]['server_name'] ?? 'Unknown server';
		?>
			<tr data-sc-sync-row="<?php echo $xtreampiId; ?>"><td class="sc-table-secondary">#<?php echo $xtreampiId; ?></td><td><span class="sc-row-status <?php echo !empty($xtreampiFolder['active']) ? 'is-active' : 'is-disabled'; ?>"><?php echo !empty($xtreampiFolder['active']) ? 'Active' : 'Disabled'; ?></span></td><td><?php echo $xtreampiEscape($xtreampiType === 'series' ? 'Series' : 'Movies'); ?></td><td class="sc-table-secondary"><?php echo $xtreampiEscape($xtreampiServer); ?></td><td class="sc-service-sync-path"><?php echo $xtreampiEscape($xtreampiFolder['directory'] ?? ''); ?></td><td class="sc-table-secondary"><?php echo $xtreampiLastRun > 0 ? $xtreampiEscape(date('Y-m-d H:i:s', $xtreampiLastRun)) : 'Never'; ?></td><td class="sc-table-actions"><a class="sc-row-action" href="watch_add?id=<?php echo $xtreampiId; ?>">Edit</a><button type="button" class="sc-row-action is-warning" data-sc-sync-action="force" data-sc-sync-id="<?php echo $xtreampiId; ?>">Run now</button><button type="button" class="sc-row-action is-danger" data-sc-sync-action="delete" data-sc-sync-id="<?php echo $xtreampiId; ?>">Delete</button></td></tr>
		<?php endforeach; endif; ?>
	</tbody></table></div></div>
	<p class="sc-service-sync-result" data-sc-sync-result role="status" aria-live="polite"></p>
</section>
