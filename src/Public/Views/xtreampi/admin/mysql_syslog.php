<?php
use XcVm\Core\Auth\Authorization;

$xtreampiPageScripts = ['assets/xtreampi/mysql-syslog.js'];
$xtreampiCanBlockSyslog = Authorization::check('adv', 'block_ips');
$xtreampiCanExportSyslog = Authorization::check('adv', 'backups');
?>
<section class="sc-logs" data-sc-mysql-syslog data-can-block="<?php echo $xtreampiCanBlockSyslog ? '1' : '0'; ?>">
    <div class="sc-page-heading">
        <div>
            <p class="sc-eyebrow">Logs</p>
            <h1>System logs</h1>
            <p>Inspect database and server errors, trace their source, and block abusive remote addresses.</p>
        </div>
        <?php if ($xtreampiCanExportSyslog): ?>
            <div class="sc-page-actions">
                <button class="sc-button sc-button-secondary" type="button" data-sc-mysql-export><i class="fe-download" aria-hidden="true"></i> Export CSV</button>
            </div>
        <?php endif; ?>
    </div>

    <p class="sc-form-error" data-sc-mysql-error role="alert" hidden></p>
    <div class="sc-toolbar">
        <label class="sc-search-field">
            <i class="fe-search" aria-hidden="true"></i>
            <span class="sc-visually-hidden">Search system logs</span>
            <input type="search" placeholder="Search IP, type, or error" data-sc-mysql-search>
        </label>
        <label class="sc-filter-field sc-entry-field">
            <span>Per page</span>
            <select data-sc-mysql-entries>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
                <option value="250">250</option>
            </select>
        </label>
        <label class="sc-filter-field sc-entry-field">
            <span>Sort by</span>
            <select data-sc-mysql-order-column>
                <option value="0">Date</option>
                <option value="1">Server</option>
                <option value="2">Type</option>
                <option value="3">Error</option>
                <option value="4">IP</option>
            </select>
        </label>
        <label class="sc-filter-field sc-entry-field">
            <span>Direction</span>
            <select data-sc-mysql-order-dir>
                <option value="desc">Newest / Z–A</option>
                <option value="asc">Oldest / A–Z</option>
            </select>
        </label>
    </div>

    <div class="sc-data-panel">
        <div class="sc-table-scroll">
            <table class="sc-data-table">
                <thead>
                    <tr>
                        <th scope="col">Date</th>
                        <th scope="col">Server</th>
                        <th scope="col">Type</th>
                        <th scope="col">Error</th>
                        <th scope="col">IP</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody data-sc-mysql-body>
                    <tr><td class="sc-table-state" colspan="6">Loading system logs…</td></tr>
                </tbody>
            </table>
        </div>
        <footer class="sc-table-footer">
            <span data-sc-mysql-range>Loading…</span>
            <div class="sc-pagination">
                <button type="button" data-sc-mysql-previous>Previous</button>
                <span data-sc-mysql-page>Page 1</span>
                <button type="button" data-sc-mysql-next>Next</button>
            </div>
        </footer>
    </div>
</section>
