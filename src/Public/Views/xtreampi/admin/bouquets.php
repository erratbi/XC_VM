<?php

use XcVm\Core\Auth\Authorization;

$xtreampiPageScripts = ['assets/xtreampi/bouquets.js'];
$xtreampiCanAddBouquet = Authorization::check('adv', 'add_bouquet');
$xtreampiCanEditBouquet = Authorization::check('adv', 'edit_bouquet');
$xtreampiCanOrderBouquet = Authorization::check('adv', 'bouquet_order');
$xtreampiBouquets = is_array($rBouquets ?? null) ? array_values($rBouquets) : [];
$xtreampiCountItems = static function (array $bouquet, string $key): int {
	$items = json_decode((string) ($bouquet[$key] ?? '[]'), true);
	return is_array($items) ? count($items) : 0;
};
?>
<section class="sc-bouquets" data-sc-bouquets>
	<div class="sc-page-heading">
		<div>
			<p class="sc-eyebrow">Content access</p>
			<h1>Bouquets</h1>
		</div>
		<div class="sc-page-actions">
			<?php if ($xtreampiCanOrderBouquet): ?>
				<a class="sc-button sc-button-secondary" href="bouquet_order"><i class="fe-list" aria-hidden="true"></i> Bouquet order</a>
			<?php endif; ?>
			<?php if ($xtreampiCanAddBouquet): ?>
				<a class="sc-button sc-button-primary" href="bouquet"><i class="fe-plus" aria-hidden="true"></i> Add bouquet</a>
			<?php endif; ?>
		</div>
	</div>

	<div class="sc-toolbar">
		<label class="sc-search-field">
			<i class="fe-search" aria-hidden="true"></i>
			<span class="sc-visually-hidden">Search bouquets</span>
			<input type="search" placeholder="Search by bouquet name or ID" data-sc-bouquet-search>
		</label>
	</div>

	<div class="sc-data-panel">
		<div class="sc-table-scroll">
			<table class="sc-data-table">
				<thead><tr><th>Bouquet</th><th>Live streams</th><th>Movies</th><th>Series</th><th>Radio stations</th><th>Assigned content</th><th><span class="sc-visually-hidden">Actions</span></th></tr></thead>
				<tbody data-sc-bouquet-rows>
					<?php foreach ($xtreampiBouquets as $xtreampiBouquet): ?>
						<?php
						$xtreampiID = intval($xtreampiBouquet['id'] ?? 0);
						$xtreampiName = (string) ($xtreampiBouquet['bouquet_name'] ?? 'Untitled bouquet');
						$xtreampiLive = $xtreampiCountItems($xtreampiBouquet, 'bouquet_channels');
						$xtreampiMovies = $xtreampiCountItems($xtreampiBouquet, 'bouquet_movies');
						$xtreampiSeries = $xtreampiCountItems($xtreampiBouquet, 'bouquet_series');
						$xtreampiRadio = $xtreampiCountItems($xtreampiBouquet, 'bouquet_radios');
						$xtreampiTotal = $xtreampiLive + $xtreampiMovies + $xtreampiSeries + $xtreampiRadio;
						?>
						<tr data-sc-bouquet-row data-bouquet-id="<?php echo $xtreampiID; ?>" data-search="<?php echo htmlspecialchars(strtolower($xtreampiID . ' ' . $xtreampiName), ENT_QUOTES, 'UTF-8'); ?>">
							<td><div class="sc-table-identity"><?php if ($xtreampiCanEditBouquet): ?><a href="bouquet?id=<?php echo $xtreampiID; ?>"><?php echo htmlspecialchars($xtreampiName, ENT_QUOTES, 'UTF-8'); ?></a><?php else: ?><strong><?php echo htmlspecialchars($xtreampiName, ENT_QUOTES, 'UTF-8'); ?></strong><?php endif; ?><small>#<?php echo $xtreampiID; ?></small></div></td>
							<td><?php echo number_format($xtreampiLive); ?></td>
							<td><?php echo number_format($xtreampiMovies); ?></td>
							<td><?php echo number_format($xtreampiSeries); ?></td>
							<td><?php echo number_format($xtreampiRadio); ?></td>
							<td><span class="sc-row-status is-active"><?php echo number_format($xtreampiTotal); ?> items</span></td>
							<td class="sc-table-actions"><?php if ($xtreampiCanEditBouquet): ?><a class="sc-row-action" href="bouquet?id=<?php echo $xtreampiID; ?>" title="Edit bouquet"><i class="fe-edit-2" aria-hidden="true"></i></a><a class="sc-row-action" href="bouquet_sort?id=<?php echo $xtreampiID; ?>" title="Reorder bouquet content"><i class="fe-list" aria-hidden="true"></i></a><a class="sc-row-action" href="bouquet?duplicate=<?php echo $xtreampiID; ?>" title="Duplicate bouquet"><i class="fe-copy" aria-hidden="true"></i></a><button class="sc-row-action" type="button" title="Delete bouquet" aria-label="Delete bouquet" data-sc-bouquet-delete="<?php echo $xtreampiID; ?>"><i class="fe-trash-2" aria-hidden="true"></i></button><?php endif; ?></td>
						</tr>
					<?php endforeach; ?>
					<tr data-sc-bouquet-empty<?php echo count($xtreampiBouquets) ? ' hidden' : ''; ?>><td class="sc-table-state" colspan="7">No bouquets have been created yet.</td></tr>
				</tbody>
			</table>
		</div>
	</div>
</section>
