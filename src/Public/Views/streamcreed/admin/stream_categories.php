<?php

use XcVm\Core\Auth\Authorization;

$streamcreedPageScripts = ['assets/streamcreed/categories.js'];
$streamcreedCategories = is_array($rCategories ?? null) ? $rCategories : [];
$streamcreedCategoryTypes = [1 => ['live', 'Live'], 2 => ['movie', 'Movies'], 3 => ['series', 'Series'], 4 => ['radio', 'Radio']];
$streamcreedCanAddCategory = Authorization::check('adv', 'add_cat');
$streamcreedCanEditCategory = Authorization::check('adv', 'edit_cat');
?>
<section class="sc-categories" data-sc-categories>
	<div class="sc-page-heading">
		<div>
			<p class="sc-eyebrow">Service setup</p>
			<h1>Streaming Categories</h1>
		</div>
		<div class="sc-page-actions">
			<?php if ($streamcreedCanAddCategory): ?><a class="sc-button sc-button-primary" href="stream_category"><i class="fe-plus" aria-hidden="true"></i> Add category</a><?php endif; ?>
		</div>
	</div>

	<div class="sc-toolbar">
		<label class="sc-search-field"><i class="fe-search" aria-hidden="true"></i><span class="sc-visually-hidden">Search categories</span><input type="search" placeholder="Search category name or ID" data-sc-category-search></label>
		<label class="sc-filter-field"><span>Content type</span><select data-sc-category-filter><option value="all">All categories</option><option value="live">Live</option><option value="movie">Movies</option><option value="series">Series</option><option value="radio">Radio</option></select></label>
	</div>

	<div class="sc-data-panel">
		<div class="sc-table-scroll">
			<table class="sc-data-table">
				<thead><tr><th>Category</th><th>Content type</th><th>Audience</th><th>Display order</th><th><span class="sc-visually-hidden">Actions</span></th></tr></thead>
				<tbody data-sc-category-rows>
					<?php foreach ($streamcreedCategoryTypes as $streamcreedTypeKey => [$streamcreedType, $streamcreedTypeLabel]): ?>
						<?php foreach ($streamcreedCategories[$streamcreedTypeKey] ?? [] as $streamcreedCategory): ?>
							<?php
							$streamcreedID = intval($streamcreedCategory['id'] ?? 0);
							$streamcreedName = (string) ($streamcreedCategory['category_name'] ?? 'Untitled category');
							$streamcreedAdult = !empty($streamcreedCategory['is_adult']);
							?>
							<tr data-sc-category-row data-type="<?php echo $streamcreedType; ?>" data-search="<?php echo htmlspecialchars(strtolower($streamcreedID . ' ' . $streamcreedName), ENT_QUOTES, 'UTF-8'); ?>">
								<td><div class="sc-table-identity"><?php if ($streamcreedCanEditCategory): ?><a href="stream_category?id=<?php echo $streamcreedID; ?>"><?php echo htmlspecialchars($streamcreedName, ENT_QUOTES, 'UTF-8'); ?></a><?php else: ?><strong><?php echo htmlspecialchars($streamcreedName, ENT_QUOTES, 'UTF-8'); ?></strong><?php endif; ?><small>#<?php echo $streamcreedID; ?></small></div></td>
								<td><span class="sc-row-status is-active"><?php echo $streamcreedTypeLabel; ?></span></td>
								<td><?php if ($streamcreedAdult): ?><span class="sc-tag is-trial">Adult</span><?php else: ?><span class="sc-table-muted">General</span><?php endif; ?></td>
								<td><?php echo intval($streamcreedCategory['cat_order'] ?? 0) ?: '—'; ?></td>
								<td class="sc-table-actions"><?php if ($streamcreedCanEditCategory): ?><a class="sc-row-action" href="stream_category?id=<?php echo $streamcreedID; ?>">Edit in legacy</a><?php endif; ?></td>
							</tr>
						<?php endforeach; ?>
					<?php endforeach; ?>
					<tr data-sc-category-empty><td class="sc-table-state" colspan="5"<?php echo array_filter($streamcreedCategories) ? ' hidden' : ''; ?>>No categories have been created yet.</td></tr>
				</tbody>
			</table>
		</div>
	</div>
</section>
