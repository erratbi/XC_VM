<?php

use XcVm\Core\Auth\Authorization;

$streamcreedPageScripts = ['assets/streamcreed/bouquets.js'];
$streamcreedCanAddBouquet = Authorization::check('adv', 'add_bouquet');
$streamcreedCanEditBouquet = Authorization::check('adv', 'edit_bouquet');
$streamcreedCanOrderBouquet = Authorization::check('adv', 'bouquet_order');
$streamcreedBouquets = is_array($rBouquets ?? null) ? array_values($rBouquets) : [];
$streamcreedCountItems = static function (array $bouquet, string $key): int {
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
			<?php if ($streamcreedCanOrderBouquet): ?>
				<a class="sc-button sc-button-secondary" href="bouquet_order"><i class="fe-list" aria-hidden="true"></i> Bouquet order</a>
			<?php endif; ?>
			<?php if ($streamcreedCanAddBouquet): ?>
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
					<?php foreach ($streamcreedBouquets as $streamcreedBouquet): ?>
						<?php
						$streamcreedID = intval($streamcreedBouquet['id'] ?? 0);
						$streamcreedName = (string) ($streamcreedBouquet['bouquet_name'] ?? 'Untitled bouquet');
						$streamcreedLive = $streamcreedCountItems($streamcreedBouquet, 'bouquet_channels');
						$streamcreedMovies = $streamcreedCountItems($streamcreedBouquet, 'bouquet_movies');
						$streamcreedSeries = $streamcreedCountItems($streamcreedBouquet, 'bouquet_series');
						$streamcreedRadio = $streamcreedCountItems($streamcreedBouquet, 'bouquet_radios');
						$streamcreedTotal = $streamcreedLive + $streamcreedMovies + $streamcreedSeries + $streamcreedRadio;
						?>
						<tr data-sc-bouquet-row data-search="<?php echo htmlspecialchars(strtolower($streamcreedID . ' ' . $streamcreedName), ENT_QUOTES, 'UTF-8'); ?>">
							<td><div class="sc-table-identity"><?php if ($streamcreedCanEditBouquet): ?><a href="bouquet?id=<?php echo $streamcreedID; ?>"><?php echo htmlspecialchars($streamcreedName, ENT_QUOTES, 'UTF-8'); ?></a><?php else: ?><strong><?php echo htmlspecialchars($streamcreedName, ENT_QUOTES, 'UTF-8'); ?></strong><?php endif; ?><small>#<?php echo $streamcreedID; ?></small></div></td>
							<td><?php echo number_format($streamcreedLive); ?></td>
							<td><?php echo number_format($streamcreedMovies); ?></td>
							<td><?php echo number_format($streamcreedSeries); ?></td>
							<td><?php echo number_format($streamcreedRadio); ?></td>
							<td><span class="sc-row-status is-active"><?php echo number_format($streamcreedTotal); ?> items</span></td>
							<td class="sc-table-actions"><?php if ($streamcreedCanEditBouquet): ?><a class="sc-row-action" href="bouquet?id=<?php echo $streamcreedID; ?>">Edit in legacy</a><?php endif; ?></td>
						</tr>
					<?php endforeach; ?>
					<tr data-sc-bouquet-empty<?php echo count($streamcreedBouquets) ? ' hidden' : ''; ?>><td class="sc-table-state" colspan="7">No bouquets have been created yet.</td></tr>
				</tbody>
			</table>
		</div>
	</div>
</section>
