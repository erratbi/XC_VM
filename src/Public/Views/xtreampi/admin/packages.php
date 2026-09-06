<?php

use XcVm\Core\Auth\Authorization;

$xtreampiPageScripts = ['assets/xtreampi/packages.js'];
$xtreampiPackages = is_array($packages ?? null) ? array_values($packages) : [];
$xtreampiCanAddPackage = Authorization::check('adv', 'add_package');
$xtreampiCanEditPackage = Authorization::check('adv', 'edit_package');
$xtreampiCountJson = static function (array $package, string $field): int {
	$value = json_decode((string) ($package[$field] ?? '[]'), true);
	return is_array($value) ? count($value) : 0;
};
?>
<section class="sc-packages" data-sc-packages>
	<div class="sc-page-heading">
		<div>
			<p class="sc-eyebrow">Service setup</p>
			<h1>Packages</h1>
		</div>
		<div class="sc-page-actions">
			<?php if ($xtreampiCanAddPackage): ?><a class="sc-button sc-button-primary" href="package"><i class="fe-plus" aria-hidden="true"></i> Add package</a><?php endif; ?>
		</div>
	</div>

	<div class="sc-toolbar">
		<label class="sc-search-field"><i class="fe-search" aria-hidden="true"></i><span class="sc-visually-hidden">Search packages</span><input type="search" placeholder="Search package name or ID" data-sc-package-search></label>
		<label class="sc-filter-field"><span>Type</span><select data-sc-package-filter><option value="all">All packages</option><option value="trial">Trial packages</option><option value="standard">Standard packages</option><option value="official">Official packages</option></select></label>
	</div>

	<div class="sc-data-panel">
		<div class="sc-table-scroll">
			<table class="sc-data-table">
				<thead><tr><th>Package</th><th>Type</th><th>Official</th><th>Connections</th><th>Bouquets</th><th>Groups</th><th>Outputs</th><th><span class="sc-visually-hidden">Actions</span></th></tr></thead>
				<tbody data-sc-package-rows>
					<?php foreach ($xtreampiPackages as $xtreampiPackage): ?>
						<?php
						$xtreampiID = intval($xtreampiPackage['id'] ?? 0);
						$xtreampiName = (string) ($xtreampiPackage['package_name'] ?? 'Untitled package');
						$xtreampiTrial = !empty($xtreampiPackage['is_trial']);
						$xtreampiOfficial = !empty($xtreampiPackage['is_official']);
						$xtreampiBouquets = $xtreampiCountJson($xtreampiPackage, 'bouquets');
						$xtreampiGroups = $xtreampiCountJson($xtreampiPackage, 'groups');
						$xtreampiOutputs = $xtreampiCountJson($xtreampiPackage, 'output_formats');
						?>
						<tr data-sc-package-row data-package-id="<?php echo $xtreampiID; ?>" data-type="<?php echo $xtreampiTrial ? 'trial' : 'standard'; ?>" data-official="<?php echo $xtreampiOfficial ? '1' : '0'; ?>" data-search="<?php echo htmlspecialchars(strtolower($xtreampiID . ' ' . $xtreampiName), ENT_QUOTES, 'UTF-8'); ?>">
							<td><div class="sc-table-identity"><?php if ($xtreampiCanEditPackage): ?><a href="package?id=<?php echo $xtreampiID; ?>"><?php echo htmlspecialchars($xtreampiName, ENT_QUOTES, 'UTF-8'); ?></a><?php else: ?><strong><?php echo htmlspecialchars($xtreampiName, ENT_QUOTES, 'UTF-8'); ?></strong><?php endif; ?><small>#<?php echo $xtreampiID; ?></small></div></td>
							<td><span class="sc-row-status <?php echo $xtreampiTrial ? 'is-expired' : 'is-active'; ?>"><?php echo $xtreampiTrial ? 'Trial' : 'Standard'; ?></span></td>
							<td><span class="sc-row-status <?php echo $xtreampiOfficial ? 'is-active' : 'is-disabled'; ?>"><?php echo $xtreampiOfficial ? 'Yes' : 'No'; ?></span></td>
							<td><?php echo intval($xtreampiPackage['max_connections'] ?? 0) ?: 'Unlimited'; ?></td>
							<td><span class="sc-connection-link"><?php echo number_format($xtreampiBouquets); ?></span></td>
							<td><span class="sc-connection-link"><?php echo number_format($xtreampiGroups); ?></span></td>
							<td><span class="sc-connection-link"><?php echo number_format($xtreampiOutputs); ?></span></td>
							<td class="sc-table-actions"><?php if ($xtreampiCanEditPackage): ?><a class="sc-row-action" href="package?id=<?php echo $xtreampiID; ?>">Edit</a><button class="sc-row-action is-danger" type="button" data-sc-package-delete>Delete</button><?php endif; ?></td>
						</tr>
					<?php endforeach; ?>
					<tr data-sc-package-empty<?php echo count($xtreampiPackages) ? ' hidden' : ''; ?>><td class="sc-table-state" colspan="8">No packages have been created yet.</td></tr>
				</tbody>
			</table>
		</div>
	</div>
</section>
