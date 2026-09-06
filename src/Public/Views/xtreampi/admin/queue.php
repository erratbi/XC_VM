<?php require_once __DIR__ . '/_migration_helpers.php'; $xtreampiPageScripts = ['assets/xtreampi/queue.js']; ?>
<section class="sc-migration-list" data-sc-queue>
    <div class="sc-page-heading"><div><p class="sc-eyebrow">Operations</p><h1>Encoding queue</h1></div><div class="sc-page-actions"><button type="button" class="sc-button sc-button-secondary" data-sc-queue-export>Export CSV</button></div></div>
    <p class="sc-section-copy">Queue actions use the existing <code>api?action=queue</code> endpoint and require confirmation before stopping or deleting work.</p>
    <div class="sc-data-panel"><div class="sc-table-scroll"><table class="sc-data-table"><thead><tr><th>Position</th><th>Stream</th><th>Server</th><th>Status</th><th>Added</th><th>Actions</th></tr></thead><tbody data-sc-queue-body><tr><td class="sc-table-state" colspan="6">Loading…</td></tr></tbody></table></div><footer class="sc-table-footer"><span data-sc-queue-range>Loading…</span><div class="sc-pagination"><button type="button" data-sc-queue-prev>Previous</button><span data-sc-queue-page>Page 1</span><button type="button" data-sc-queue-next>Next</button></div></footer></div>
</section>
