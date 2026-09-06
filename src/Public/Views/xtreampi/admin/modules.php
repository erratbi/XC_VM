<?php

$xtreampiPageScripts = ['assets/xtreampi/modules.js'];
$xtreampiModules = is_array($modules ?? null) ? $modules : [];
$xtreampiEscape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<section class="sc-modules" data-sc-modules>
	<div class="sc-page-heading">
		<div><p class="sc-eyebrow">System</p><h1>Modules</h1><p class="sc-section-copy">Install, update, and manage the panel extensions available on this server.</p></div>
		<div class="sc-page-actions"><form method="post" data-sc-module-form><input type="hidden" name="module_action" value="check_updates"><button class="sc-button sc-button-secondary" type="submit"><i class="fe-refresh-cw" aria-hidden="true"></i> Check for updates</button></form></div>
	</div>

	<?php if (!empty($moduleFlash) && is_array($moduleFlash)): ?>
		<div class="sc-notice sc-module-result <?php echo ($moduleFlash['type'] ?? '') === 'danger' ? 'sc-notice-danger' : 'sc-notice-info'; ?>" role="status"><i class="fe-info" aria-hidden="true"></i><div><strong>Module manager</strong><p><?php echo $xtreampiEscape($moduleFlash['message'] ?? ''); ?></p></div></div>
	<?php endif; ?>
	<div class="sc-notice sc-module-result" data-sc-module-result role="status" aria-live="polite" hidden><i class="fe-info" aria-hidden="true"></i><div><strong data-sc-module-result-title>Module manager</strong><p data-sc-module-result-message></p></div></div>

	<div class="sc-module-install-grid">
		<form class="sc-form sc-module-install" method="post" data-sc-module-form>
			<input type="hidden" name="module_action" value="platform_install">
			<div class="sc-section-heading"><div><h2>Install from store</h2><p>Enter the module slug from the platform store. The latest approved version is installed for this server.</p></div></div>
			<label for="module_slug">Module slug<input id="module_slug" name="module_slug" autocomplete="off" placeholder="module-slug" required><small>Set the Modules API Key in Settings → API before installing from the store.</small></label>
			<div class="sc-form-actions"><button class="sc-button sc-button-primary" type="submit"><i class="fe-download" aria-hidden="true"></i> Install latest</button></div>
		</form>

		<form class="sc-form sc-module-install" method="post" enctype="multipart/form-data" data-sc-module-form data-sc-module-upload>
			<input type="hidden" name="module_action" value="upload_install">
			<div class="sc-section-heading"><div><h2>Upload module package</h2><p>Upload a signed module package from your computer.</p></div></div>
			<label class="sc-module-file" for="module_zip"><span class="sc-module-file-icon"><i class="fe-upload-cloud" aria-hidden="true"></i></span><span><strong data-sc-module-file-name>Choose a module package</strong><small>ZIP, TAR, TGZ, and TAR.GZ packages are supported.</small></span><input id="module_zip" name="module_zip" type="file" accept=".zip,.tar.gz,.tgz,.tar" required data-sc-module-file-input></label>
			<div class="sc-form-actions"><button class="sc-button sc-button-primary" type="submit" disabled data-sc-module-upload-button><i class="fe-upload" aria-hidden="true"></i> Upload and install</button></div>
		</form>
	</div>

	<section class="sc-data-panel sc-module-list" data-sc-module-list aria-labelledby="module-list-heading">
		<div class="sc-module-list-heading"><div><h2 id="module-list-heading">Available modules</h2><p>Module changes are applied through the existing module manager and may take a moment to complete.</p></div><span><?php echo count($xtreampiModules); ?> found</span></div>
		<div class="sc-table-scroll"><table class="sc-data-table"><thead><tr><th>Module</th><th>Description</th><th>Version</th><th>Requires core</th><th>Status</th><th><span class="sc-visually-hidden">Actions</span></th></tr></thead><tbody>
			<?php if (!$xtreampiModules): ?><tr><td class="sc-table-state" colspan="6">No module packages are available.</td></tr><?php else: foreach ($xtreampiModules as $xtreampiModule):
				$xtreampiName = (string) ($xtreampiModule['name'] ?? '');
				$xtreampiInstalled = (string) ($xtreampiModule['installed_version'] ?? '');
				$xtreampiAvailable = (string) ($xtreampiModule['available_version'] ?? '');
				$xtreampiManifestVersion = (string) ($xtreampiModule['version'] ?? '');
				$xtreampiLatest = $xtreampiAvailable !== '' ? $xtreampiAvailable : $xtreampiManifestVersion;
				$xtreampiHasUpdate = $xtreampiInstalled !== '' && $xtreampiLatest !== '' && version_compare($xtreampiLatest, $xtreampiInstalled, '>');
				$xtreampiWarnings = is_array($xtreampiModule['dependency_warnings'] ?? null) ? $xtreampiModule['dependency_warnings'] : [];
			?>
				<tr>
					<td><div class="sc-table-identity"><strong><?php echo $xtreampiEscape($xtreampiName ?: 'Unnamed module'); ?></strong><small><?php echo $xtreampiEscape($xtreampiModule['slug'] ?? ''); ?></small></div></td>
					<td class="sc-module-description"><?php echo $xtreampiEscape($xtreampiModule['description'] ?? '—'); ?></td>
					<td><div class="sc-table-activity"><strong><?php echo $xtreampiEscape($xtreampiInstalled !== '' ? $xtreampiInstalled : ($xtreampiManifestVersion ?: '—')); ?></strong><?php if ($xtreampiHasUpdate): ?><span>Update <?php echo $xtreampiEscape($xtreampiLatest); ?> available</span><?php endif; ?></div></td>
					<td class="sc-table-secondary"><?php echo $xtreampiEscape($xtreampiModule['requires_core'] ?? '—'); ?></td>
					<td><div class="sc-module-status"><span class="sc-row-status <?php echo $xtreampiInstalled === '' ? 'is-neutral' : (!empty($xtreampiModule['enabled']) ? 'is-active' : 'is-disabled'); ?>"><?php echo $xtreampiInstalled === '' ? 'Not installed' : (!empty($xtreampiModule['enabled']) ? 'Enabled' : 'Disabled'); ?></span><?php if ($xtreampiWarnings): ?><span class="sc-module-warning" title="<?php echo $xtreampiEscape(implode(' ', array_map('strval', $xtreampiWarnings))); ?>"><i class="fe-alert-triangle" aria-hidden="true"></i> <?php echo count($xtreampiWarnings) === 1 ? 'Dependency issue' : 'Dependency issues'; ?></span><?php endif; ?></div></td>
					<td class="sc-table-actions"><div class="sc-module-actions">
						<?php if ($xtreampiInstalled === ''): ?><form method="post" data-sc-module-form><input type="hidden" name="module_name" value="<?php echo $xtreampiEscape($xtreampiName); ?>"><input type="hidden" name="module_action" value="install"><button class="sc-row-action is-primary" type="submit">Install</button></form><?php endif; ?>
						<?php if ($xtreampiHasUpdate): ?><form method="post" data-sc-module-form><input type="hidden" name="module_name" value="<?php echo $xtreampiEscape($xtreampiName); ?>"><input type="hidden" name="module_action" value="update"><button class="sc-row-action is-warning" type="submit">Update <?php echo $xtreampiEscape($xtreampiLatest); ?></button></form><?php endif; ?>
						<?php if (($xtreampiModule['source'] ?? '') === 'platform' && !empty($xtreampiModule['previous_version'])): ?><form method="post" data-sc-module-form data-sc-module-confirm="Roll back <?php echo $xtreampiEscape($xtreampiName); ?> to version <?php echo $xtreampiEscape($xtreampiModule['previous_version']); ?>?"><input type="hidden" name="module_name" value="<?php echo $xtreampiEscape($xtreampiName); ?>"><input type="hidden" name="module_action" value="platform_rollback"><button class="sc-row-action" type="submit">Rollback</button></form><?php endif; ?>
						<?php if (($xtreampiModule['source'] ?? '') === 'platform'): ?><form method="post" data-sc-module-form><input type="hidden" name="module_name" value="<?php echo $xtreampiEscape($xtreampiName); ?>"><input type="hidden" name="module_action" value="renew_license"><button class="sc-row-action" type="submit">Renew license</button></form><?php endif; ?>
						<form method="post" data-sc-module-form><input type="hidden" name="module_name" value="<?php echo $xtreampiEscape($xtreampiName); ?>"><input type="hidden" name="module_action" value="<?php echo !empty($xtreampiModule['enabled']) ? 'disable' : 'enable'; ?>"><button class="sc-row-action" type="submit"><?php echo !empty($xtreampiModule['enabled']) ? 'Disable' : 'Enable'; ?></button></form>
						<?php if ($xtreampiInstalled !== ''): ?><form method="post" data-sc-module-form data-sc-module-confirm="Uninstall <?php echo $xtreampiEscape($xtreampiName); ?>? Its files remain available until you delete the module."><input type="hidden" name="module_name" value="<?php echo $xtreampiEscape($xtreampiName); ?>"><input type="hidden" name="module_action" value="uninstall"><button class="sc-row-action is-warning" type="submit">Uninstall</button></form><?php endif; ?>
						<form method="post" data-sc-module-form data-sc-module-confirm="Delete <?php echo $xtreampiEscape($xtreampiName); ?> completely? This removes its files and module tables."><input type="hidden" name="module_name" value="<?php echo $xtreampiEscape($xtreampiName); ?>"><input type="hidden" name="module_action" value="delete"><button class="sc-row-action is-danger" type="submit">Delete</button></form>
					</div></td>
				</tr>
			<?php endforeach; endif; ?>
		</tbody></table></div>
	</section>
</section>
