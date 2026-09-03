<?php

use XcVm\Core\Auth\Authorization;
use XcVm\Domain\Server\ServerRepository;
use XcVm\Domain\Stream\CategoryService;

$streamcreedPageScripts = ['assets/streamcreed/streams.js'];
$streamcreedCanAdd = Authorization::check('adv', 'add_stream');
$streamcreedCanEdit = Authorization::check('adv', 'edit_stream');
$streamcreedCanMassEdit = Authorization::check('adv', 'mass_edit_streams');
$streamcreedCanPlay = Authorization::check('adv', 'player');
$streamcreedCanFingerprint = Authorization::check('adv', 'fingerprint');
$streamcreedCanViewConnections = Authorization::check('adv', 'live_connections');

$streamcreedCategories = CategoryService::getAllByType('live');
$streamcreedServers = ServerRepository::getStreamingSimple($rPermissions);
$streamcreedDefaultEntries = intval($rSettings['default_entries'] ?? 25);
if (!in_array($streamcreedDefaultEntries, [10, 25, 50, 100], true)) {
    $streamcreedDefaultEntries = 25;
}
?>
<section class="sc-streams" data-sc-streams
    data-endpoint="table"
    data-default-entries="<?php echo $streamcreedDefaultEntries; ?>"
    data-can-edit="<?php echo $streamcreedCanEdit ? '1' : '0'; ?>"
    data-can-play="<?php echo $streamcreedCanPlay ? '1' : '0'; ?>"
    data-can-fingerprint="<?php echo $streamcreedCanFingerprint ? '1' : '0'; ?>"
    data-can-view-connections="<?php echo $streamcreedCanViewConnections ? '1' : '0'; ?>">

    <div class="sc-page-heading">
        <div>
            <p class="sc-eyebrow">Content management</p>
            <h1>Live Streams</h1>
        </div>
        <div class="sc-page-actions">
            <?php if ($streamcreedCanMassEdit): ?>
                <a class="sc-button sc-button-secondary" href="stream_mass"><i class="fe-edit-3" aria-hidden="true"></i> Mass edit</a>
            <?php endif; ?>
            <?php if ($streamcreedCanAdd): ?>
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
                <?php foreach ($streamcreedServers as $streamcreedServer): ?>
                    <option value="<?php echo intval($streamcreedServer['id']); ?>"><?php echo htmlspecialchars((string) $streamcreedServer['server_name'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="sc-filter-field">
            <span>Category</span>
            <select data-sc-stream-category>
                <option value="">All categories</option>
                <option value="-1">Uncategorised</option>
                <?php foreach ($streamcreedCategories as $streamcreedCat): ?>
                    <option value="<?php echo intval($streamcreedCat['id']); ?>"><?php echo htmlspecialchars((string) $streamcreedCat['category_name'], ENT_QUOTES, 'UTF-8'); ?></option>
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
                <?php foreach ([10, 25, 50, 100] as $streamcreedEntries): ?>
                    <option value="<?php echo $streamcreedEntries; ?>"<?php echo $streamcreedDefaultEntries === $streamcreedEntries ? ' selected' : ''; ?>><?php echo $streamcreedEntries; ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    </div>

    <div class="sc-data-panel">
        <div class="sc-table-scroll">
            <table class="sc-data-table">
                <thead>
                    <tr>
                        <th>Stream</th>
                        <th>Server</th>
                        <th>Status</th>
                        <th>Connections</th>
                        <th>Uptime</th>
                        <th>Bitrate</th>
                        <th><span class="sc-visually-hidden">Actions</span></th>
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

<!-- Player Modal Dialog -->
<dialog class="sc-playlist-dialog sc-player-dialog" data-sc-player-dialog>
    <div class="sc-dialog-heading">
        <div>
            <p class="sc-eyebrow">Live preview</p>
            <h2 data-sc-player-title>Stream Player</h2>
        </div>
        <button class="sc-dialog-close" type="button" data-sc-player-close aria-label="Close"><i class="fe-x" aria-hidden="true"></i></button>
    </div>
    <div class="sc-dialog-body" style="padding: 16px;">
        <iframe class="sc-player-frame" data-sc-player-frame allow="autoplay; fullscreen"></iframe>
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
            <button class="sc-button sc-button-secondary" type="button" data-sc-failures-clear><i class="fe-trash-2" aria-hidden="true"></i> Clear logs</button>
            <button class="sc-button sc-button-secondary" type="button" data-sc-failures-close-btn>Close</button>
        </div>
    </div>
</dialog>
