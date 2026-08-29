<?php

use XcVm\Core\Auth\Authorization;

$streamcreedPageScripts = ['assets/streamcreed/packages.js'];
$streamcreedPackages = is_array($packages ?? null) ? array_values($packages) : [];
$streamcreedCanAddPackage = Authorization::check('adv', 'add_package');
$streamcreedCanEditPackage = Authorization::check('adv', 'edit_package');
$streamcreedCountJson = static function (array $package, string $field): int {
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
			<?php if ($streamcreedCanAddPackage): ?><a class="sc-button sc-button-primary" href="package"><i class="fe-plus" aria-hidden="true"></i> Add package</a><?php endif; ?>
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
					<?php foreach ($streamcreedPackages as $streamcreedPackage): ?>
						<?php
						$streamcreedID = intval($streamcreedPackage['id'] ?? 0);
						$streamcreedName = (string) ($streamcreedPackage['package_name'] ?? 'Untitled package');
						$streamcreedTrial = !empty($streamcreedPackage['is_trial']);
						$streamcreedOfficial = !empty($streamcreedPackage['is_official']);
						$streamcreedBouquets = $streamcreedCountJson($streamcreedPackage, 'bouquets');
						$streamcreedGroups = $streamcreedCountJson($streamcreedPackage, 'groups');
						$streamcreedOutputs = $streamcreedCountJson($streamcreedPackage, 'output_formats');
						?>
						<tr data-sc-package-row data-type="<?php echo $streamcreedTrial ? 'trial' : 'standard'; ?>" data-official="<?php echo $streamcreedOfficial ? '1' : '0'; ?>" data-search="<?php echo htmlspecialchars(strtolower($streamcreedID . ' ' . $streamcreedName), ENT_QUOTES, 'UTF-8'); ?>">
							<td><div class="sc-table-identity"><?php if ($streamcreedCanEditPackage): ?><a href="package?id=<?php echo $streamcreedID; ?>"><?php echo htmlspecialchars($streamcreedName, ENT_QUOTES, 'UTF-8'); ?></a><?php else: ?><strong><?php echo htmlspecialchars($streamcreedName, ENT_QUOTES, 'UTF-8'); ?></strong><?php endif; ?><small>#<?php echo $streamcreedID; ?></small></div></td>
							<td><span class="sc-row-status <?php echo $streamcreedTrial ? 'is-expired' : 'is-active'; ?>"><?php echo $streamcreedTrial ? 'Trial' : 'Standard'; ?></span></td>
							<td><span class="sc-row-status <?php echo $streamcreedOfficial ? 'is-active' : 'is-disabled'; ?>"><?php echo $streamcreedOfficial ? 'Yes' : 'No'; ?></span></td>
							<td><?php echo intval($streamcreedPackage['max_connections'] ?? 0) ?: 'Unlimited'; ?></td>
							<td><span class="sc-connection-link"><?php echo number_format($streamcreedBouquets); ?></span></td>
							<td><span class="sc-connection-link"><?php echo number_format($streamcreedGroups); ?></span></td>
							<td><span class="sc-connection-link"><?php echo number_format($streamcreedOutputs); ?></span></td>
							<td class="sc-table-actions"><?php if ($streamcreedCanEditPackage): ?><a class="sc-row-action" href="package?id=<?php echo $streamcreedID; ?>">Edit in legacy</a><?php endif; ?></td>
						</tr>
					<?php endforeach; ?>
					<tr data-sc-package-empty<?php echo count($streamcreedPackages) ? ' hidden' : ''; ?>><td class="sc-table-state" colspan="8">No packages have been created yet.</td></tr>
				</tbody>
			</table>
		</div>
	</div>
</section>
