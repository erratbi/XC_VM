<?php require_once __DIR__ . '/_migration_helpers.php';
$xtreampiPageScripts = ['assets/xtreampi/ondemand.js'];
?>
<section class="sc-migration-list" data-sc-ondemand>
    <div class="sc-page-heading"><div><p class="sc-eyebrow">Monitoring</p><h1>On-demand scanner</h1></div></div>
    <p class="sc-section-copy">The scanner state is read from the existing <code>table?id=ondemand</code> handler. Filters are server-side and never mutate streams.</p>
    <div class="sc-toolbar">
        <label class="sc-search-field"><span class="sc-visually-hidden">Search streams</span><input id="sc-ondemand-search" type="search" placeholder="Search streams"></label>
        <label class="sc-filter-field"><span>Server ID</span><input id="sc-ondemand-server" type="number" min="1"></label>
        <label class="sc-filter-field"><span>Category ID</span><input id="sc-ondemand-category" type="number" min="1"></label>
        <label class="sc-filter-field"><span>State</span><select id="sc-ondemand-filter"><option value="">All</option><option value="1">Ready</option><option value="2">Down</option><option value="3">Not scanned</option></select></label>
        <label class="sc-filter-field"><span>Per page</span><select id="sc-ondemand-size"><option>10</option><option selected>25</option><option>50</option><option>250</option><option>500</option><option>1000</option></select></label>
    </div>
    <div class="sc-data-panel"><div class="sc-table-scroll"><table class="sc-data-table"><thead><tr><th>ID</th><th>Icon</th><th>Stream</th><th>Server</th><th>Status</th><th>Response</th><th>Stream info</th><th>Last scanned</th></tr></thead><tbody data-sc-ondemand-body><tr><td class="sc-table-state" colspan="8">Loading…</td></tr></tbody></table></div><footer class="sc-table-footer"><span data-sc-ondemand-range>Loading…</span><div class="sc-pagination"><button type="button" data-sc-ondemand-prev>Previous</button><span data-sc-ondemand-page>Page 1</span><button type="button" data-sc-ondemand-next>Next</button></div></footer></div>
</section>
