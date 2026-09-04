<?php

use XcVm\Core\Auth\Authorization;

$streamcreedPageScripts = ['assets/streamcreed/bouquet-sort.js'];
$streamcreedCanEdit = Authorization::check('adv', 'edit_bouquet');
$streamcreedBouquet = is_array($rBouquet ?? null) ? $rBouquet : [];
$streamcreedOrdered = is_array($rOrdered ?? null) ? $rOrdered : [];
$streamcreedTypes = [
    'stream' => ['Live streams', 'fe-play', 'stream_display_name'],
    'movie' => ['VOD movies', 'fe-film', 'stream_display_name'],
    'series' => ['TV series', 'fe-tv', 'title'],
    'radio' => ['Radio stations', 'fe-radio', 'stream_display_name'],
];
?>
<section class="sc-bouquet-sort" data-sc-bouquet-sort>
    <div class="sc-page-heading">
        <div>
            <p class="sc-eyebrow">Content access</p>
            <h1>Reorder <?php echo htmlspecialchars((string) ($streamcreedBouquet['bouquet_name'] ?? 'bouquet'), ENT_QUOTES, 'UTF-8'); ?></h1>
        </div>
        <div class="sc-page-actions"><a class="sc-button sc-button-secondary" href="bouquets"><i class="fe-chevron-left" aria-hidden="true"></i> Back to bouquets</a></div>
    </div>

    <form class="sc-form" action="post.php?action=bouquet_sort&amp;referer=bouquets" method="post">
        <input type="hidden" name="reorder" value="<?php echo (int) ($streamcreedBouquet['id'] ?? 0); ?>">
        <input type="hidden" name="stream_order_array" value="{}">
        <section class="sc-form-section">
            <div class="sc-section-heading"><div><h2>Content order</h2><p class="sc-section-copy">Drag an item to set the order clients receive within this bouquet.</p></div></div>
            <div class="sc-sort-groups">
                <?php foreach ($streamcreedTypes as $streamcreedType => $streamcreedTypeConfig): ?>
                    <?php $streamcreedItems = is_array($streamcreedOrdered[$streamcreedType] ?? null) ? $streamcreedOrdered[$streamcreedType] : []; ?>
                    <section class="sc-sort-group" data-sc-bouquet-sort-group="<?php echo $streamcreedType; ?>">
                        <div class="sc-sort-group-heading"><h3><i class="<?php echo $streamcreedTypeConfig[1]; ?>" aria-hidden="true"></i> <?php echo $streamcreedTypeConfig[0]; ?></h3><button class="sc-button sc-button-secondary" type="button" data-sc-bouquet-sort-alpha="<?php echo $streamcreedType; ?>">Sort A–Z</button></div>
                        <ol class="sc-sort-list" data-sc-bouquet-sort-list="<?php echo $streamcreedType; ?>">
                            <?php foreach ($streamcreedItems as $streamcreedItem): ?>
                                <?php $streamcreedName = (string) ($streamcreedItem[$streamcreedTypeConfig[2]] ?? 'Untitled item'); ?>
                                <li draggable="true" data-item-id="<?php echo (int) ($streamcreedItem['id'] ?? 0); ?>"><span class="sc-sort-handle" title="Drag to reorder"><i class="fe-menu" aria-hidden="true"></i></span><div><strong><?php echo htmlspecialchars($streamcreedName, ENT_QUOTES, 'UTF-8'); ?></strong><small>#<?php echo (int) ($streamcreedItem['id'] ?? 0); ?></small></div><span data-sc-bouquet-sort-position></span></li>
                            <?php endforeach; ?>
                        </ol>
                        <?php if (!$streamcreedItems): ?><p class="sc-sort-empty">No <?php echo strtolower($streamcreedTypeConfig[0]); ?> are assigned to this bouquet.</p><?php endif; ?>
                    </section>
                <?php endforeach; ?>
            </div>
        </section>
        <div class="sc-form-error" data-sc-bouquet-sort-error hidden></div>
        <div class="sc-form-actions">
            <?php if ($streamcreedCanEdit): ?><button class="sc-button sc-button-primary" type="submit">Save content order</button><?php endif; ?>
            <a class="sc-button sc-button-secondary" href="bouquets">Cancel</a>
        </div>
    </form>
</section>
