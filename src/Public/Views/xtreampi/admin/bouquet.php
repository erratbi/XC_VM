<?php
$xtreampiPageScripts = ['assets/xtreampi/bouquet.js'];
$xtreampiEditing = is_array($rBouquetArr ?? null) && (int) ($rBouquetArr['id'] ?? 0) > 0;
$xtreampiName = htmlspecialchars((string) ($rBouquetArr['bouquet_name'] ?? ''), ENT_QUOTES, 'UTF-8');
$xtreampiCategories = static function (array $categories): array {
    $options = [];
    foreach ($categories as $id => $category) {
        $categoryID = (int) ($category['id'] ?? $id);
        $categoryName = (string) ($category['category_name'] ?? '');
        if ($categoryID > 0 && $categoryName !== '') {
            $options[] = ['id' => $categoryID, 'name' => $categoryName];
        }
    }
    usort($options, static fn(array $left, array $right): int => strcasecmp($left['name'], $right['name']));
    return $options;
};
$xtreampiContentGroups = [
    ['stream', 'Live Streams', 'bouquets_streams', $rBouquetChannels ?? [], $xtreampiCategories($liveCategories ?? [])],
    ['movies', 'Movies', 'bouquets_vod', $rBouquetMovies ?? [], $xtreampiCategories($movieCategories ?? [])],
    ['series', 'Series', 'bouquets_series', $rBouquetSeries ?? [], $xtreampiCategories($seriesCategories ?? [])],
    ['radios', 'Radio Stations', 'bouquets_radios', $rBouquetRadios ?? [], $xtreampiCategories($radioCategories ?? [])],
];
?>
<section class="sc-group-editor" data-sc-bouquet-editor>
    <div class="sc-page-heading">
        <div><p class="sc-eyebrow">Content Setup</p><h1><?php echo $xtreampiEditing ? 'Edit Bouquet' : 'Add Bouquet'; ?></h1></div>
        <div class="sc-page-actions"><a class="sc-button sc-button-secondary" href="bouquets"><i class="fe-chevron-left" aria-hidden="true"></i> Back to bouquets</a></div>
    </div>
    <form class="sc-form sc-group-form" action="post.php?action=bouquet&amp;referer=bouquets" method="post" enctype="multipart/form-data">
        <?php if ($xtreampiEditing): ?><input type="hidden" name="edit" value="<?php echo (int) $rBouquetArr['id']; ?>"><?php endif; ?>
        <input type="hidden" name="bouquet_data" value="">
        <section class="sc-form-section"><h2>Bouquet Details</h2><div class="sc-form-grid"><label class="sc-form-span">Bouquet Name<input type="text" name="bouquet_name" required value="<?php echo $xtreampiName; ?>" placeholder="Bouquet name"></label></div></section>
        <section class="sc-form-section sc-bouquet-picker" data-sc-content-picker>
            <div class="sc-section-heading"><div><h2>Included Content</h2><p class="sc-section-copy">Browse one content type at a time. Selections stay in place while you search, filter, and change pages.</p></div><span class="sc-selection-summary" data-sc-selection-summary>0 items selected</span></div>
            <div class="sc-picker-tabs" role="tablist" aria-label="Bouquet content type">
                <?php foreach ($xtreampiContentGroups as $index => [$key, $label]): ?>
                    <button type="button" role="tab" id="sc-bouquet-tab-<?php echo $key; ?>" aria-controls="sc-bouquet-panel-<?php echo $key; ?>" aria-selected="<?php echo $index === 0 ? 'true' : 'false'; ?>" tabindex="<?php echo $index === 0 ? '0' : '-1'; ?>" data-sc-picker-tab="<?php echo $key; ?>"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?><span data-sc-tab-count="<?php echo $key; ?>">0</span></button>
                <?php endforeach; ?>
            </div>
            <?php foreach ($xtreampiContentGroups as $index => [$key, $label, $source, $selected, $categories]): ?>
                <section class="sc-picker-panel" id="sc-bouquet-panel-<?php echo $key; ?>" role="tabpanel" aria-labelledby="sc-bouquet-tab-<?php echo $key; ?>" data-sc-content-group="<?php echo $key; ?>" data-sc-table-source="<?php echo $source; ?>" data-selected="<?php echo htmlspecialchars(json_encode(array_values(array_map('intval', (array) $selected))), ENT_QUOTES, 'UTF-8'); ?>"<?php echo $index === 0 ? '' : ' hidden'; ?>>
                    <div class="sc-picker-toolbar">
                        <label class="sc-picker-search"><span class="sc-visually-hidden">Search <?php echo strtolower($label); ?></span><i class="fe-search" aria-hidden="true"></i><input type="search" autocomplete="off" placeholder="Search <?php echo strtolower($label); ?> by name or ID" data-sc-picker-search></label>
                        <label class="sc-picker-category"><span class="sc-visually-hidden">Filter by category</span><select data-sc-picker-category><option value="">All categories</option><?php foreach ($categories as $category): ?><option value="<?php echo (int) $category['id']; ?>"><?php echo htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></label>
                        <button class="sc-button sc-button-secondary" type="button" data-sc-picker-toggle-page>Toggle page</button><button class="sc-button sc-button-secondary" type="button" data-sc-picker-clear>Clear selected</button>
                    </div>
                    <div class="sc-table-scroll"><table class="sc-data-table sc-picker-table"><thead><tr><th class="sc-picker-check"><input type="checkbox" aria-label="Toggle visible items" data-sc-picker-toggle-page-checkbox></th><th>Content</th><th>Category</th></tr></thead><tbody data-sc-picker-rows><tr><td class="sc-table-state" colspan="3">Loading <?php echo strtolower($label); ?>…</td></tr></tbody></table></div>
                    <footer class="sc-table-footer sc-picker-footer"><span data-sc-picker-status>Loading…</span><div class="sc-picker-pagination"><button class="sc-row-action" type="button" data-sc-picker-previous disabled>Previous</button><span data-sc-picker-page>Page 1</span><button class="sc-row-action" type="button" data-sc-picker-next disabled>Next</button></div></footer>
                </section>
            <?php endforeach; ?>
        </section>
        <div class="sc-form-error" data-sc-bouquet-error hidden></div>
        <div class="sc-form-actions"><button class="sc-button sc-button-primary" type="submit" name="submit_bouquet" value="<?php echo $xtreampiEditing ? 'Edit' : 'Add'; ?>"><?php echo $xtreampiEditing ? 'Save Bouquet' : 'Add Bouquet'; ?></button><a class="sc-button sc-button-secondary" href="bouquets">Cancel</a></div>
    </form>
</section>
