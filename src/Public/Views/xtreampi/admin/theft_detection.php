<?php

use XcVm\Core\Auth\Authorization;

$xtreampiPageScripts = ['assets/xtreampi/theft-detection.js'];
$xtreampiTheftRanges = [0 => 'All time', 604800 => 'Last 7 days', 86400 => 'Last 24 hours', 3600 => 'Last hour'];
$xtreampiRange = intval($range ?? 0);
if (!array_key_exists($xtreampiRange, $xtreampiTheftRanges)) $xtreampiRange = 0;
$xtreampiDetectionData = is_array($theftDetection ?? null) ? $theftDetection : [];
$xtreampiTheftRows = is_array($xtreampiDetectionData[$xtreampiRange] ?? null) ? array_values($xtreampiDetectionData[$xtreampiRange]) : [];
usort($xtreampiTheftRows, static fn (array $left, array $right): int => intval($right['vod_count'] ?? 0) <=> intval($left['vod_count'] ?? 0));
$xtreampiSettings = is_array($rSettings ?? null) ? $rSettings : [];
$xtreampiEntryOptions = [10, 25, 50, 250, 500, 1000];
$xtreampiEntries = intval($xtreampiSettings['default_entries'] ?? 10);
if (!in_array($xtreampiEntries, $xtreampiEntryOptions, true)) $xtreampiEntries = 10;
$xtreampiDateFormat = (string) ($xtreampiSettings['date_format'] ?? 'Y-m-d');
$xtreampiLogDates = $xtreampiRange > 0 ? date($xtreampiDateFormat, time() - $xtreampiRange) . ' - ' . date($xtreampiDateFormat) : '';
$xtreampiCanEditLines = Authorization::check('adv', 'edit_user');
$xtreampiEscape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<section class="sc-theft-detection" data-sc-theft-detection>
	<div class="sc-page-heading"><div><p class="sc-eyebrow">Security</p><h1>VOD Theft Detection</h1><p class="sc-section-copy">Review accounts with unusually high video-on-demand activity, then inspect their recent line activity.</p></div></div>

	<div class="sc-toolbar">
		<label class="sc-search-field"><i class="fe-search" aria-hidden="true"></i><span class="sc-visually-hidden">Search detected accounts</span><input type="search" placeholder="Search user ID or username" data-sc-theft-search></label>
		<label class="sc-filter-field"><span>Monitoring window</span><select data-sc-theft-range><?php foreach ($xtreampiTheftRanges as $xtreampiValue => $xtreampiLabel): ?><option value="<?php echo $xtreampiValue; ?>"<?php echo $xtreampiRange === $xtreampiValue ? ' selected' : ''; ?>><?php echo $xtreampiEscape($xtreampiLabel); ?></option><?php endforeach; ?></select></label>
		<label class="sc-filter-field sc-entry-field"><span>Per page</span><select data-sc-theft-entries><?php foreach ($xtreampiEntryOptions as $xtreampiOption): ?><option value="<?php echo $xtreampiOption; ?>"<?php echo $xtreampiEntries === $xtreampiOption ? ' selected' : ''; ?>><?php echo $xtreampiOption; ?></option><?php endforeach; ?></select></label>
	</div>

	<div class="sc-data-panel"><div class="sc-table-scroll"><table class="sc-data-table"><thead><tr><th>User ID</th><th>Username</th><th>VOD views</th><th><span class="sc-visually-hidden">Actions</span></th></tr></thead><tbody data-sc-theft-rows>
		<?php foreach ($xtreampiTheftRows as $xtreampiRow):
			$xtreampiUserId = intval($xtreampiRow['user_id'] ?? 0);
			$xtreampiUsername = (string) ($xtreampiRow['username'] ?? '');
			$xtreampiLogUrl = 'line_activity?' . http_build_query(array_filter(['search' => $xtreampiUsername, 'dates' => $xtreampiLogDates], static fn ($value): bool => $value !== ''), '', '&', PHP_QUERY_RFC3986);
		?>
			<tr data-sc-theft-row><td><?php if ($xtreampiCanEditLines && $xtreampiUserId > 0): ?><a class="sc-stream-name" href="line?id=<?php echo $xtreampiUserId; ?>">#<?php echo $xtreampiUserId; ?></a><?php else: ?>#<?php echo $xtreampiUserId ?: '—'; ?><?php endif; ?></td><td><?php if ($xtreampiCanEditLines && $xtreampiUserId > 0): ?><a class="sc-stream-name" href="line?id=<?php echo $xtreampiUserId; ?>"><?php echo $xtreampiEscape($xtreampiUsername); ?></a><?php else: ?><?php echo $xtreampiEscape($xtreampiUsername ?: '—'); ?><?php endif; ?></td><td><strong><?php echo number_format(intval($xtreampiRow['vod_count'] ?? 0)); ?></strong></td><td class="sc-table-actions"><a class="sc-row-action" href="<?php echo $xtreampiEscape($xtreampiLogUrl); ?>">View logs</a></td></tr>
		<?php endforeach; ?>
		<tr data-sc-theft-empty><td class="sc-table-state" colspan="4">No detected VOD activity matches this window.</td></tr>
	</tbody></table></div><footer class="sc-table-footer"><span data-sc-theft-range-summary></span><div class="sc-pagination"><button type="button" data-sc-theft-previous><i class="fe-chevron-left" aria-hidden="true"></i> Previous</button><span data-sc-theft-page>Page 1</span><button type="button" data-sc-theft-next>Next <i class="fe-chevron-right" aria-hidden="true"></i></button></div></footer></div>
</section>
