<?php
require_once __DIR__ . '/_migration_helpers.php';
$range = (int) ($rRange ?? 0);
$all = is_array($rLineIPs ?? null) && is_array($rLineIPs[$range] ?? null) ? $rLineIPs[$range] : [];
$dateFormat = (string) (($rSettings['date_format'] ?? '') ?: 'Y-m-d');
?>
<section class="sc-migration-list">
    <div class="sc-page-heading"><div><p class="sc-eyebrow">Logs</p><h1>Line IP Usage</h1></div></div>
    <div class="sc-toolbar">
        <label class="sc-search-field"><i class="fe-search" aria-hidden="true"></i><input type="search" data-sc-migration-search placeholder="Search lines"></label>
        <label class="sc-filter-field"><span>Range</span><select onchange="window.location.href='line_ips?range='+encodeURIComponent(this.value)"><option value="0"<?php echo $range === 0 ? ' selected' : ''; ?>>All time</option><option value="604800"<?php echo $range === 604800 ? ' selected' : ''; ?>>Last 7 days</option><option value="86400"<?php echo $range === 86400 ? ' selected' : ''; ?>>Last 24 hours</option><option value="3600"<?php echo $range === 3600 ? ' selected' : ''; ?>>Last hour</option></select></label>
    </div>
    <div class="sc-data-panel"><div class="sc-table-scroll"><table class="sc-data-table" data-sc-migration-table><thead><tr><th>User ID</th><th>Username</th><th>IP count</th><th>Actions</th></tr></thead><tbody>
        <?php if (!$all): ?><tr><td class="sc-table-state" colspan="4">No line IP usage found.</td></tr><?php else: foreach ($all as $item):
            $userId = (int) ($item['user_id'] ?? 0);
            $activityUrl = 'line_activity?user_id=' . $userId;
            if ($range > 0) {
                $activityUrl .= '&range=' . rawurlencode(date($dateFormat, time() - $range) . ' - ' . date($dateFormat));
            }
        ?><tr data-sc-row><td><?php echo $userId; ?></td><td data-sc-cell><?php echo sc_m_escape($item['username'] ?? ''); ?></td><td><?php echo (int) ($item['ip_count'] ?? 0); ?></td><td><a class="sc-row-action" href="<?php echo sc_m_escape($activityUrl); ?>">View logs</a></td></tr><?php endforeach; endif; ?>
    </tbody></table></div></div>
</section>
<?php $xtreampiPageScripts = ['assets/xtreampi/migration.js']; ?>
