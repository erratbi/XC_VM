<?php

use XcVm\Core\Auth\Authorization;

$streamcreedPageScripts = ['assets/streamcreed/series.js'];
$streamcreedCanAddSeries = Authorization::check('adv', 'add_series');
$streamcreedCanEditSeries = Authorization::check('adv', 'edit_series');
$streamcreedCanEpisodes = Authorization::check('adv', 'episodes');
$streamcreedCanAddEpisode = Authorization::check('adv', 'add_episode');
$streamcreedCanMassEditSeries = Authorization::check('adv', 'mass_sedits');
$streamcreedDefaultEntries = intval($rSettings['default_entries'] ?? 25);
if (!in_array($streamcreedDefaultEntries, [10, 25, 50, 100], true)) $streamcreedDefaultEntries = 25;
?>
<section class="sc-series" data-sc-series data-endpoint="table" data-default-entries="<?php echo $streamcreedDefaultEntries; ?>" data-can-edit="<?php echo $streamcreedCanEditSeries ? '1' : '0'; ?>" data-can-episodes="<?php echo $streamcreedCanEpisodes ? '1' : '0'; ?>" data-can-add-episode="<?php echo $streamcreedCanAddEpisode ? '1' : '0'; ?>" data-show-images="<?php echo !empty($rSettings['show_images']) ? '1' : '0'; ?>">
    <div class="sc-page-heading"><div><p class="sc-eyebrow">Content management</p><h1>TV Series</h1></div><div class="sc-page-actions"><?php if ($streamcreedCanMassEditSeries): ?><a class="sc-button sc-button-secondary" href="series_mass"><i class="fe-edit-3" aria-hidden="true"></i> Mass edit</a><?php endif; ?><?php if ($streamcreedCanAddSeries): ?><a class="sc-button sc-button-primary" href="serie"><i class="fe-plus" aria-hidden="true"></i> Add series</a><?php endif; ?></div></div>
    <div class="sc-toolbar"><label class="sc-search-field"><i class="fe-search" aria-hidden="true"></i><span class="sc-visually-hidden">Search series</span><input type="search" placeholder="Search title, ID, or first aired date" data-sc-series-search></label><label class="sc-filter-field"><span>Category</span><select data-sc-series-category><option value="">All categories</option><option value="-1">No TMDB match</option><option value="-2">No categories</option><?php foreach ($rCategories as $category): ?><option value="<?php echo (int) $category['id']; ?>"><?php echo htmlspecialchars((string) $category['category_name'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></label><label class="sc-filter-field sc-entry-field"><span>Per page</span><select data-sc-series-entries><?php foreach ([10, 25, 50, 100] as $size): ?><option value="<?php echo $size; ?>"<?php echo $size === $streamcreedDefaultEntries ? ' selected' : ''; ?>><?php echo $size; ?></option><?php endforeach; ?></select></label></div>
    <div class="sc-data-panel"><div class="sc-table-scroll"><table class="sc-data-table sc-series-table"><thead><tr><th class="sc-col-series">Series</th><th>Seasons</th><th>Episodes</th><th>First aired</th><th>Updated</th><th class="sc-col-actions"><span class="sc-visually-hidden">Actions</span></th></tr></thead><tbody data-sc-series-rows><tr><td class="sc-table-state" colspan="6">Loading series…</td></tr></tbody></table></div><footer class="sc-table-footer"><span data-sc-series-range>Loading…</span><div class="sc-pagination"><button type="button" data-sc-series-previous><i class="fe-chevron-left" aria-hidden="true"></i> Previous</button><span data-sc-series-page>Page 1</span><button type="button" data-sc-series-next>Next <i class="fe-chevron-right" aria-hidden="true"></i></button></div></footer></div>
</section>
