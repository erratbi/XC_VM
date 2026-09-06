<?php
use XcVm\Core\Auth\Authorization;

$xtreampiPageScripts = ['assets/xtreampi/ips.js'];
$xtreampiIPs = is_array($ips ?? null) ? $ips : [];
$xtreampiCanManageIPs = Authorization::check('adv', 'block_ips');
$xtreampiStatus = isset($_GET['status']) ? (int) $_GET['status'] : null;
$xtreampiDateFormat = (string) ($rSettings['datetime_format'] ?? 'Y-m-d H:i:s');
$xtreampiEscape = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$xtreampiStatusMessage = '';
if ($xtreampiStatus !== null && defined('STATUS_FLUSH') && $xtreampiStatus === (int) STATUS_FLUSH) {
    $xtreampiStatusMessage = 'All blocked IP addresses have been flushed from the database.';
} elseif ($xtreampiStatus !== null && defined('STATUS_SUCCESS') && $xtreampiStatus === (int) STATUS_SUCCESS) {
    $xtreampiStatusMessage = 'The IP address was added to the block list and propagated across all servers.';
}
?>
<section class="sc-inventory" data-sc-blocked-ips>
    <div class="sc-page-heading">
        <div>
            <p class="sc-eyebrow">Access security</p>
            <h1>Blocked IP addresses</h1>
            <p>Review addresses blocked across the panel and remove entries when they are no longer needed.</p>
        </div>
        <?php if ($xtreampiCanManageIPs): ?>
            <div class="sc-page-actions">
                <a class="sc-button sc-button-primary" href="ip"><i class="fe-plus" aria-hidden="true"></i> Block IP</a>
                <a class="sc-button sc-button-secondary" href="ips?flush=1" data-sc-flush><i class="fe-trash-2" aria-hidden="true"></i> Flush blocks</a>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($xtreampiStatusMessage !== ''): ?>
        <p class="sc-section-copy" data-sc-status role="status"><?php echo $xtreampiEscape($xtreampiStatusMessage); ?></p>
    <?php else: ?>
        <p class="sc-section-copy" data-sc-status role="status" hidden></p>
    <?php endif; ?>
    <p class="sc-form-error" data-sc-error role="alert" hidden></p>

    <div class="sc-toolbar">
        <label class="sc-search-field">
            <i class="fe-search" aria-hidden="true"></i>
            <span class="sc-visually-hidden">Search blocked IP addresses</span>
            <input type="search" placeholder="Search IP addresses or notes" data-sc-ip-search>
        </label>
        <span class="sc-toolbar-note"><i class="fe-shield" aria-hidden="true"></i> Changes use the existing blocklist service.</span>
    </div>

    <div class="sc-data-panel">
        <div class="sc-table-scroll">
            <table class="sc-data-table">
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">IP address</th>
                        <th scope="col">Notes</th>
                        <th scope="col">Date</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody data-sc-blocked-ip-rows>
                    <?php if ($xtreampiIPs): ?>
                        <?php foreach ($xtreampiIPs as $xtreampiIP): ?>
                            <?php
                            $xtreampiIPId = (int) ($xtreampiIP['id'] ?? 0);
                            $xtreampiIPValue = (string) ($xtreampiIP['ip'] ?? '');
                            $xtreampiIPNotes = (string) ($xtreampiIP['notes'] ?? '');
                            $xtreampiIPDate = isset($xtreampiIP['date']) ? date($xtreampiDateFormat, (int) $xtreampiIP['date']) : '';
                            ?>
                            <tr data-sc-blocked-ip-row data-ip-id="<?php echo $xtreampiIPId; ?>">
                                <td><?php echo $xtreampiIPId; ?></td>
                                <td><?php echo $xtreampiEscape($xtreampiIPValue); ?></td>
                                <td><?php echo $xtreampiEscape($xtreampiIPNotes); ?></td>
                                <td><?php echo $xtreampiEscape($xtreampiIPDate); ?></td>
                                <td>
                                    <?php if ($xtreampiCanManageIPs): ?>
                                        <button class="sc-row-action is-danger" type="button" data-sc-ip-delete data-ip-id="<?php echo $xtreampiIPId; ?>"><i class="fe-trash-2" aria-hidden="true"></i> Delete</button>
                                    <?php else: ?>
                                        <span>—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr data-sc-blocked-ip-empty>
                            <td class="sc-table-state" colspan="5">No blocked IP addresses.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
