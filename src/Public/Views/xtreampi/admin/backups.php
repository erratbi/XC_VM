<?php

use XcVm\Core\Backup\BackupService;

$xtreampiPageScripts = ['assets/xtreampi/backups.js'];
$xtreampiBackupSettings = is_array($rSettings ?? null) ? $rSettings : [];
$xtreampiEscape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$xtreampiSchedule = (string) ($xtreampiBackupSettings['automatic_backups'] ?? 'off');
if (!in_array($xtreampiSchedule, ['off', 'hourly', 'daily', 'weekly', 'monthly'], true)) $xtreampiSchedule = 'off';
$xtreampiDropboxToken = (string) ($xtreampiBackupSettings['dropbox_token'] ?? '');
$xtreampiDropboxUnavailable = $xtreampiDropboxToken !== '' && !BackupService::checkRemoteConnection();
?>
<section class="sc-backups" data-sc-backups data-table-endpoint="table" data-api-endpoint="api">
	<div class="sc-page-heading">
		<div>
			<p class="sc-eyebrow">System</p>
			<h1>Backups</h1>
			<p class="sc-section-copy">Set retention rules, manage database snapshots, and restore a saved snapshot when needed.</p>
		</div>
	</div>

	<?php if (isset($_STATUS) && intval($_STATUS) === STATUS_SUCCESS): ?>
		<div class="sc-notice sc-notice-success" role="status"><i class="fe-check-circle" aria-hidden="true"></i><div><strong>Backup settings saved</strong><p>Your automatic and Dropbox backup settings have been updated.</p></div></div>
	<?php else: ?>
		<div class="sc-notice sc-notice-info"><i class="fe-info" aria-hidden="true"></i><div><strong>Database backups exclude logs</strong><p>Restoring a database backup clears panel logs. Keep separate copies if you need to retain them.</p></div></div>
	<?php endif; ?>

	<?php if ($xtreampiDropboxUnavailable): ?>
		<div class="sc-notice sc-notice-danger" role="alert"><i class="fe-alert-triangle" aria-hidden="true"></i><div><strong>Dropbox could not be reached</strong><p>Generate a new Dropbox token or check the saved token before relying on remote backups.</p></div></div>
	<?php endif; ?>

	<form class="sc-form sc-backup-form" action="post.php?action=backups" method="post" data-sc-backup-settings>
		<section class="sc-form-section">
			<div class="sc-section-heading"><div><h2>Backup policy</h2><p>Control when backups run and how many copies are retained.</p></div></div>
			<div class="sc-form-grid">
				<label for="automatic_backups">Automatic backups
					<select name="automatic_backups" id="automatic_backups">
						<?php foreach (['off' => 'Off', 'hourly' => 'Hourly', 'daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly'] as $xtreampiValue => $xtreampiLabel): ?>
							<option value="<?php echo $xtreampiEscape($xtreampiValue); ?>"<?php echo $xtreampiSchedule === $xtreampiValue ? ' selected' : ''; ?>><?php echo $xtreampiEscape($xtreampiLabel); ?></option>
						<?php endforeach; ?>
					</select>
					<small>Generate a full database backup at this interval.</small>
				</label>
				<label for="backups_to_keep">Local backups to keep
					<input type="number" min="0" step="1" inputmode="numeric" id="backups_to_keep" name="backups_to_keep" value="<?php echo $xtreampiEscape(intval($xtreampiBackupSettings['backups_to_keep'] ?? 0)); ?>" data-sc-backup-number>
					<small>Enter 0 to keep every local backup.</small>
				</label>
			</div>
		</section>

		<section class="sc-form-section">
			<div class="sc-section-heading"><div><h2>Dropbox copy</h2><p>Upload local backups to Dropbox after they are created.</p></div></div>
			<div class="sc-form-grid">
				<label class="sc-backup-toggle" for="dropbox_remote"><input name="dropbox_remote" id="dropbox_remote" type="checkbox" value="1"<?php echo !empty($xtreampiBackupSettings['dropbox_remote']) ? ' checked' : ''; ?>><span><strong>Enable Dropbox backups</strong><small>Copy each completed local backup to the configured Dropbox account.</small></span></label>
				<label for="dropbox_keep">Dropbox backups to keep
					<input type="number" min="0" step="1" inputmode="numeric" id="dropbox_keep" name="dropbox_keep" value="<?php echo $xtreampiEscape(intval($xtreampiBackupSettings['dropbox_keep'] ?? 0)); ?>" data-sc-backup-number>
					<small>Enter 0 to keep every Dropbox backup.</small>
				</label>
				<label class="sc-form-span" for="dropbox_token">Dropbox token
					<input type="text" id="dropbox_token" name="dropbox_token" value="<?php echo $xtreampiEscape($xtreampiDropboxToken); ?>" autocomplete="off">
					<small>Create an application token in Dropbox and paste it here to enable remote backups.</small>
				</label>
			</div>
		</section>

		<div class="sc-form-error" data-sc-backup-settings-error hidden></div>
		<div class="sc-form-actions"><button class="sc-button sc-button-primary" type="submit" name="submit_settings" value="Save Changes" data-sc-backup-save>Save backup settings</button></div>
	</form>

	<section class="sc-backup-inventory" aria-labelledby="backup-inventory-heading">
		<div class="sc-section-heading">
			<div><h2 id="backup-inventory-heading">Available backups</h2><p>Local and Dropbox copies are combined by filename. Refresh after a new backup has finished.</p></div>
			<div class="sc-page-actions"><button class="sc-button sc-button-secondary" type="button" data-sc-backup-refresh><i class="fe-refresh-cw" aria-hidden="true"></i> Refresh</button><button class="sc-button sc-button-primary" type="button" data-sc-backup-create><i class="fe-plus" aria-hidden="true"></i> Create backup now</button></div>
		</div>
		<div class="sc-data-panel">
			<div class="sc-table-scroll"><table class="sc-data-table sc-backup-table"><thead><tr><th>Date</th><th>Filename</th><th>Size</th><th>Local</th><th>Dropbox</th><th><span class="sc-visually-hidden">Actions</span></th></tr></thead><tbody data-sc-backup-rows><tr><td class="sc-table-state" colspan="6"><span class="sc-spinner" aria-hidden="true"></span> Loading backups…</td></tr></tbody></table></div>
			<footer class="sc-table-footer"><span data-sc-backup-summary>Loading backups…</span><span data-sc-backup-result role="status" aria-live="polite"></span></footer>
		</div>
	</section>

	<div class="sc-notice sc-notice-danger sc-backup-restore-result" data-sc-backup-restore-result role="alert" hidden><i class="fe-alert-triangle" aria-hidden="true"></i><div><strong data-sc-backup-restore-title>Restore response received</strong><p data-sc-backup-restore-message>Exit XtreamPi and verify the service before signing in again.</p></div></div>
</section>
