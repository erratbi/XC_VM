<?php

use XcVm\Core\Auth\Authorization;
use XcVm\Domain\Server\ServerRepository;
use XcVm\Domain\Stream\CategoryService;

$xtreampiPageScripts = ['assets/xtreampi/streams.js'];
$xtreampiCanAdd = Authorization::check('adv', 'add_stream');
$xtreampiCanEdit = Authorization::check('adv', 'edit_stream');
$xtreampiCanMassEdit = Authorization::check('adv', 'mass_edit_streams');
$xtreampiCanPlay = Authorization::check('adv', 'player');
$xtreampiCanFingerprint = Authorization::check('adv', 'fingerprint');
$xtreampiCanViewConnections = Authorization::check('adv', 'live_connections');

$xtreampiCategories = CategoryService::getAllByType('live');
$xtreampiServers = ServerRepository::getStreamingSimple($rPermissions);
$xtreampiDefaultEntries = intval($rSettings['default_entries'] ?? 25);
if (!in_array($xtreampiDefaultEntries, [10, 25, 50, 100], true)) {
    $xtreampiDefaultEntries = 25;
}
?>
<section class="sc-streams" data-sc-streams
    data-endpoint="table"
    data-default-entries="<?php echo $xtreampiDefaultEntries; ?>"
    data-can-edit="<?php echo $xtreampiCanEdit ? '1' : '0'; ?>"
    data-can-play="<?php echo $xtreampiCanPlay ? '1' : '0'; ?>"
    data-can-fingerprint="<?php echo $xtreampiCanFingerprint ? '1' : '0'; ?>"
    data-can-view-connections="<?php echo $xtreampiCanViewConnections ? '1' : '0'; ?>"
    data-show-images="<?php echo !empty($rSettings['show_images']) ? '1' : '0'; ?>">

    <div class="sc-page-heading">
        <div>
            <p class="sc-eyebrow">Content management</p>
            <h1>Live Streams</h1>
        </div>
        <div class="sc-page-actions">
            <?php if ($xtreampiCanMassEdit): ?>
                <a class="sc-button sc-button-secondary" href="stream_mass"><i class="fe-edit-3" aria-hidden="true"></i> Mass edit</a>
            <?php endif; ?>
            <?php if ($xtreampiCanAdd): ?>
                <a class="sc-button sc-button-primary" href="stream"><i class="fe-plus" aria-hidden="true"></i> Add stream</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="sc-toolbar">
        <label class="sc-search-field">
            <i class="fe-search" aria-hidden="true"></i>
            <span class="sc-visually-hidden">Search streams</span>
            <input type="search" placeholder="Search stream name, ID, or source host" data-sc-stream-search>
        </label>
        <label class="sc-filter-field">
            <span>Server</span>
            <select data-sc-stream-server>
                <option value="">All servers</option>
                <option value="-1">No server</option>
                <?php foreach ($xtreampiServers as $xtreampiServer): ?>
                    <option value="<?php echo intval($xtreampiServer['id']); ?>"><?php echo htmlspecialchars((string) $xtreampiServer['server_name'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="sc-filter-field">
            <span>Category</span>
            <select data-sc-stream-category>
                <option value="">All categories</option>
                <option value="-1">Uncategorised</option>
                <?php foreach ($xtreampiCategories as $xtreampiCat): ?>
                    <option value="<?php echo intval($xtreampiCat['id']); ?>"><?php echo htmlspecialchars((string) $xtreampiCat['category_name'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="sc-filter-field">
            <span>Status</span>
            <select data-sc-stream-status>
                <option value="">All statuses</option>
                <option value="1">Online</option>
                <option value="2">Down</option>
                <option value="3">Stopped</option>
                <option value="5">On demand</option>
                <option value="6">Direct source</option>
            </select>
        </label>
        <label class="sc-filter-field sc-entry-field">
            <span>Per page</span>
            <select data-sc-stream-entries>
                <?php foreach ([10, 25, 50, 100] as $xtreampiEntries): ?>
                    <option value="<?php echo $xtreampiEntries; ?>"<?php echo $xtreampiDefaultEntries === $xtreampiEntries ? ' selected' : ''; ?>><?php echo $xtreampiEntries; ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    </div>

    <div class="sc-data-panel">
        <div class="sc-table-scroll">
            <table class="sc-data-table sc-streams-table">
                <thead>
                    <tr>
                        <th class="sc-col-stream">Stream</th>
                        <th class="sc-col-server">Server</th>
                        <th class="sc-col-status">Status</th>
                        <th class="sc-col-conn">Clients</th>
                        <th class="sc-col-uptime">Uptime</th>
                        <th class="sc-col-info">Stream Info</th>
                        <th class="sc-col-actions"><span class="sc-visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody data-sc-stream-rows>
                    <tr><td class="sc-table-state" colspan="7"><span class="sc-spinner" aria-hidden="true"></span> Loading streams…</td></tr>
                </tbody>
            </table>
        </div>
        <footer class="sc-table-footer">
            <span data-sc-stream-range>Loading…</span>
            <div class="sc-pagination">
                <button type="button" data-sc-stream-previous><i class="fe-chevron-left" aria-hidden="true"></i> Previous</button>
                <span data-sc-stream-page>Page 1</span>
                <button type="button" data-sc-stream-next>Next <i class="fe-chevron-right" aria-hidden="true"></i></button>
            </div>
        </footer>
    </div>
</section>

<!-- Legacy UI Style Stream Player Modal -->
<dialog class="sc-player-dialog" data-sc-player-dialog aria-label="Live Stream Player">
    <div class="sc-player-scaler" data-sc-player-scaler>
        <button class="sc-player-close" type="button" data-sc-player-close title="Close (Esc)" aria-label="Close (Esc)">&#215;</button>
        <iframe class="sc-player-frame" data-sc-player-frame allow="autoplay; fullscreen" frameborder="0"></iframe>
    </div>
</dialog>

<!-- EPG Schedule Modal Dialog -->
<dialog class="sc-playlist-dialog sc-epg-dialog" data-sc-epg-dialog>
    <div class="sc-dialog-heading">
        <div>
            <p class="sc-eyebrow">Electronic Program Guide</p>
            <h2 data-sc-epg-title>Stream EPG</h2>
        </div>
        <button class="sc-dialog-close" type="button" data-sc-epg-close aria-label="Close"><i class="fe-x" aria-hidden="true"></i></button>
    </div>
    <div class="sc-dialog-body" style="padding: 16px 20px;">
        <div class="sc-table-scroll">
            <table class="sc-data-table">
                <thead>
                    <tr>
                        <th style="width: 100px;">Time</th>
                        <th>Program Title</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody data-sc-epg-rows>
                    <tr><td class="sc-table-state" colspan="3">Loading EPG…</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</dialog>

<!-- Fingerprint Modal Dialog -->
<dialog class="sc-playlist-dialog sc-fingerprint-dialog" data-sc-fingerprint-dialog>
    <form method="dialog">
        <div class="sc-dialog-heading">
            <div>
                <p class="sc-eyebrow">Live stream</p>
                <h2>Send Fingerprint</h2>
            </div>
            <button class="sc-dialog-close" value="cancel" aria-label="Close"><i class="fe-x" aria-hidden="true"></i></button>
        </div>
        <div class="sc-dialog-body">
            <div class="sc-fingerprint-grid">
                <label>Type
                    <select data-sc-fingerprint-type>
                        <option value="1">Activity ID</option>
                        <option value="2">Username</option>
                        <option value="3">Message</option>
                    </select>
                </label>
                <label>Size<input type="number" min="1" value="36" data-sc-fingerprint-size></label>
                <label>Colour<input type="color" value="#ffffff" data-sc-fingerprint-color></label>
                <label>Position X<input type="number" min="0" value="10" data-sc-fingerprint-x></label>
                <label>Position Y<input type="number" min="0" value="10" data-sc-fingerprint-y></label>
            </div>
            <label data-sc-fingerprint-message-wrap hidden>Custom message
                <input type="text" data-sc-fingerprint-message placeholder="Enter fingerprint text">
            </label>
            <button class="sc-button sc-button-primary" type="button" data-sc-fingerprint-send><i class="fe-crosshair" aria-hidden="true"></i> Send fingerprint</button>
        </div>
    </form>
</dialog>

<!-- Failures / Restarts Modal Dialog -->
<dialog class="sc-playlist-dialog sc-failures-dialog" data-sc-failures-dialog>
    <div class="sc-dialog-heading">
        <div>
            <p class="sc-eyebrow">Stream Diagnostics</p>
            <h2 data-sc-failures-title>Stream Restarts &amp; Logs</h2>
        </div>
        <button class="sc-dialog-close" type="button" data-sc-failures-close aria-label="Close"><i class="fe-x" aria-hidden="true"></i></button>
    </div>
    <div class="sc-dialog-body">
        <div class="sc-table-scroll" style="max-height: 380px;">
            <table class="sc-data-table">
                <thead>
                    <tr>
                        <th>Server</th>
                        <th>Source IP</th>
                        <th>Status</th>
                        <th>Date &amp; Time</th>
                    </tr>
                </thead>
                <tbody data-sc-failures-rows>
                    <tr><td class="sc-table-state" colspan="4">Loading log entries…</td></tr>
                </tbody>
            </table>
        </div>
        <div style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 16px;">
            <?php if ($xtreampiCanEdit): ?>
            <button class="sc-button sc-button-secondary" type="button" data-sc-failures-clear><i class="fe-trash-2" aria-hidden="true"></i> Clear logs</button>
            <?php endif; ?>
            <button class="sc-button sc-button-secondary" type="button" data-sc-failures-close-btn>Close</button>
        </div>
    </div>
</dialog>
