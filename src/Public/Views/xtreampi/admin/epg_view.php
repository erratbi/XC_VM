<?php
require_once __DIR__ . '/_migration_helpers.php';

$xtreampiIds = array_map('intval', (array) ($rStreamIDs ?? []));
$xtreampiQuery = is_array($_GET ?? null) ? $_GET : [];
$xtreampiCategory = (int) ($xtreampiQuery['category'] ?? 0);
$xtreampiPage = max((int) ($rPageInt ?? 1), 1);
$xtreampiPages = max((int) ($rPages ?? 1), 1);
$xtreampiEntries = (int) ($rLimit ?? 25);
?>
<section class="sc-epg-view" data-sc-epg-view data-stream-ids="<?php echo sc_m_escape(implode(',', $xtreampiIds)); ?>" data-category="<?php echo $xtreampiCategory; ?>">
    <div class="sc-page-heading">
        <div><p class="sc-eyebrow">Guide</p><h1>TV Guide</h1><p class="sc-section-copy">Select a programme to inspect its description or open the recording form when archive is available.</p></div>
    </div>
    <form class="sc-toolbar" method="get" action="epg_view">
        <label class="sc-search-field"><i class="fe-search" aria-hidden="true"></i><span class="sc-visually-hidden">Search channels</span><input type="search" name="search" value="<?php echo sc_m_escape($xtreampiQuery['search'] ?? ''); ?>" placeholder="Search channels"></label>
        <label class="sc-filter-field"><span>Category ID</span><input type="number" name="category" value="<?php echo $xtreampiCategory ?: ''; ?>"></label>
        <label class="sc-filter-field"><span>Sort</span><select name="sort"><option value="">Configured order</option><option value="name"<?php echo ($xtreampiQuery['sort'] ?? '') === 'name' ? ' selected' : ''; ?>>Alphabetical</option><option value="added"<?php echo ($xtreampiQuery['sort'] ?? '') === 'added' ? ' selected' : ''; ?>>Date added</option></select></label>
        <label class="sc-filter-field"><span>Channels per page</span><select name="entries"><?php foreach ([10, 25, 50, 250, 500, 1000] as $xtreampiOption): ?><option value="<?php echo $xtreampiOption; ?>"<?php echo $xtreampiEntries === $xtreampiOption ? ' selected' : ''; ?>><?php echo $xtreampiOption; ?></option><?php endforeach; ?></select></label>
        <button class="sc-button sc-button-primary" type="submit">Search</button>
        <a class="sc-button sc-button-secondary" href="epg_view">Clear</a>
    </form>
    <div class="sc-epg-toolbar"><button class="sc-button sc-button-secondary" type="button" data-sc-epg-shift="-3">Previous 3 hours</button><span data-sc-epg-window>Loading schedule…</span><button class="sc-button sc-button-secondary" type="button" data-sc-epg-shift="3">Next 3 hours</button><button class="sc-button sc-button-secondary" type="button" data-sc-epg-now>Now</button></div>
    <div class="sc-data-panel"><div class="sc-table-scroll"><table class="sc-data-table"><thead><tr><th>Channel</th><th>Category</th><th>Programme schedule</th></tr></thead><tbody data-sc-epg-rows><?php if (!$xtreampiIds): ?><tr><td class="sc-table-state" colspan="3">No live streams or programmes match these filters.</td></tr><?php else: ?><tr><td class="sc-table-state" colspan="3">Loading schedule…</td></tr><?php endif; ?></tbody></table></div></div>
    <?php if ($xtreampiPages > 1): ?><nav class="sc-pagination" aria-label="Guide pages"><span>Page <?php echo $xtreampiPage; ?> of <?php echo $xtreampiPages; ?></span><?php if ($xtreampiPage > 1): ?><a class="sc-button sc-button-secondary" href="epg_view?<?php echo http_build_query(array_merge($xtreampiQuery, ['page' => $xtreampiPage - 1])); ?>">Previous</a><?php endif; ?><?php if ($xtreampiPage < $xtreampiPages): ?><a class="sc-button sc-button-secondary" href="epg_view?<?php echo http_build_query(array_merge($xtreampiQuery, ['page' => $xtreampiPage + 1])); ?>">Next</a><?php endif; ?></nav><?php endif; ?>
</section>
<dialog class="sc-dialog" data-sc-epg-dialog><div class="sc-dialog-heading"><h2 data-sc-epg-title>Programme</h2><button class="sc-dialog-close" type="button" data-sc-epg-close aria-label="Close">×</button></div><div class="sc-dialog-body"><p data-sc-epg-date></p><p data-sc-epg-description></p><a class="sc-button sc-button-primary" data-sc-epg-record hidden>Schedule recording</a></div></dialog>
<?php $xtreampiPageScripts = ['assets/xtreampi/epg-view.js']; ?>
