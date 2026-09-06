<?php

use XcVm\Core\Util\TimeUtils;

$xtreampiPageScripts = ['assets/xtreampi/cache.js'];
$xtreampiCacheSettings = is_array($rSettings ?? null) ? $rSettings : [];
$xtreampiCacheEnabled = !empty($xtreampiCacheSettings['enable_cache']);
$xtreampiRedisEnabled = !empty($xtreampiCacheSettings['redis_handler']);
$xtreampiEscape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$xtreampiCron = ['*/5', '*', '*', '*', '*'];
$xtreampiLineTotal = $xtreampiStreamTotal = $xtreampiSeriesTotal = 0;
if (isset($db)) {
	$db->query("SELECT `time` FROM `crontab` WHERE `filename` = 'cache_engine';");
	if ($db->num_rows() > 0) {
		$xtreampiCron = array_pad(explode(' ', (string) ($db->get_row()['time'] ?? '')), 5, '*');
	}
	foreach (['lines' => 'xtreampiLineTotal', 'streams' => 'xtreampiStreamTotal', 'streams_series' => 'xtreampiSeriesTotal'] as $xtreampiTable => $xtreampiVariable) {
		$db->query('SELECT `id` FROM `' . $xtreampiTable . '`;');
		${$xtreampiVariable} = $db->num_rows();
	}
}
$xtreampiCachedLines = defined('LINES_TMP_PATH') ? count(glob(LINES_TMP_PATH . 'line_i_*') ?: []) : 0;
$xtreampiCachedStreams = defined('STREAMS_TMP_PATH') ? count(glob(STREAMS_TMP_PATH . 'stream_*') ?: []) : 0;
$xtreampiCachedSeries = defined('SERIES_TMP_PATH') ? max(count(glob(SERIES_TMP_PATH . 'series_*') ?: []) - 2, 0) : 0;
$xtreampiCacheComplete = defined('CACHE_TMP_PATH') && file_exists(CACHE_TMP_PATH . 'cache_complete');
$xtreampiCacheUsage = null;
if (defined('MAIN_HOME') && is_dir(MAIN_HOME . 'tmp')) {
	$xtreampiTotalSpace = @disk_total_space(MAIN_HOME . 'tmp');
	$xtreampiFreeSpace = @disk_free_space(MAIN_HOME . 'tmp');
	if ($xtreampiTotalSpace && $xtreampiFreeSpace !== false) $xtreampiCacheUsage = 100 - intval($xtreampiFreeSpace / $xtreampiTotalSpace * 100);
}
$xtreampiRedisOnline = null;
$xtreampiRedisAuthenticated = null;
if ($xtreampiRedisEnabled && class_exists('Redis')) {
	try {
		$xtreampiRedis = new Redis();
		$xtreampiRedisOnline = $xtreampiRedis->connect((string) (($rServers[SERVER_ID]['server_ip'] ?? '127.0.0.1')), 6379, 1.0);
		if ($xtreampiRedisOnline) $xtreampiRedisAuthenticated = $xtreampiRedis->auth((string) ($xtreampiCacheSettings['redis_password'] ?? ''));
	} catch (Throwable $xtreampiRedisError) {
		$xtreampiRedisOnline = false;
		$xtreampiRedisAuthenticated = false;
	}
}
$xtreampiPerformance = ($xtreampiCacheEnabled ? 75 : 0) + ($xtreampiRedisEnabled ? 25 : 0);
$xtreampiPerformanceLabel = $xtreampiPerformance === 100 ? 'Maximum' : ($xtreampiPerformance >= 75 ? 'Good' : ($xtreampiPerformance >= 25 ? 'Fair' : 'Poor'));
?>
<section class="sc-cache" data-sc-cache>
	<div class="sc-page-heading"><div><p class="sc-eyebrow">System</p><h1>Cache &amp; Redis</h1><p class="sc-section-copy">Manage cached panel data and the Redis connection handler.</p></div></div>

	<?php if (isset($_STATUS) && intval($_STATUS) === STATUS_SUCCESS): ?><div class="sc-notice sc-notice-success" role="status"><i class="fe-check-circle" aria-hidden="true"></i><div><strong>Cache settings saved</strong><p>The cache schedule and settings have been updated.</p></div></div><?php endif; ?>
	<?php if ($xtreampiCacheEnabled && $xtreampiCacheUsage !== null && $xtreampiCacheUsage >= 90): ?><div class="sc-notice sc-notice-danger" role="alert"><i class="fe-alert-triangle" aria-hidden="true"></i><div><strong>Cache storage is nearly full</strong><p>The tmpfs cache mount is <?php echo $xtreampiCacheUsage; ?>% full. Increase its size before caching stops working correctly.</p></div></div><?php endif; ?>
	<?php if ($xtreampiCacheEnabled && !$xtreampiCacheComplete): ?><div class="sc-notice sc-notice-info"><i class="fe-info" aria-hidden="true"></i><div><strong>Cache is still being built</strong><p>New connections and playlist responses remain limited until the cache is complete.</p></div></div><?php endif; ?>

	<div class="sc-cache-panel sc-cache-performance"><div><p class="sc-eyebrow">Service performance</p><h2><?php echo $xtreampiPerformanceLabel; ?> performance</h2><p><?php echo $xtreampiPerformance === 100 ? 'Caching and the Redis connection handler are enabled.' : ($xtreampiCacheEnabled ? 'Caching is enabled. Redis can improve high-throughput connection handling.' : 'Caching is disabled. Enable it to improve panel performance under load.'); ?></p></div><div class="sc-cache-meter" aria-label="Performance score <?php echo $xtreampiPerformance; ?> out of 100"><span style="width: <?php echo $xtreampiPerformance; ?>%"></span></div></div>

	<div class="sc-cache-layout">
		<section class="sc-form sc-cache-card" aria-labelledby="cache-settings-heading">
			<div class="sc-section-heading"><div><h2 id="cache-settings-heading">XtreamPi cache</h2><p>Refresh stream, line, and series data on a schedule.</p></div><span class="sc-row-status <?php echo $xtreampiCacheEnabled ? 'is-active' : 'is-disabled'; ?>"><?php echo $xtreampiCacheEnabled ? 'Enabled' : 'Disabled'; ?></span></div>
			<?php if ($xtreampiCacheEnabled): ?>
				<form action="post.php?action=cache" method="post" data-sc-cache-form>
					<div class="sc-form-grid"><label for="minute">Cron minute<input id="minute" name="minute" value="<?php echo $xtreampiEscape($xtreampiCron[0]); ?>" pattern="[0-9/*,-]+" required><small>Numbers, ranges, commas, slashes, and asterisks are accepted.</small></label><label for="hour">Cron hour<input id="hour" name="hour" value="<?php echo $xtreampiEscape($xtreampiCron[1]); ?>" pattern="[0-9/*,-]+" required></label><label for="cache_thread_count">Thread count<input type="number" min="0" step="1" inputmode="numeric" id="cache_thread_count" name="cache_thread_count" value="<?php echo intval($xtreampiCacheSettings['cache_thread_count'] ?? 0); ?>" required></label><label class="sc-cache-toggle" for="cache_changes"><input name="cache_changes" id="cache_changes" type="checkbox" value="1"<?php echo !empty($xtreampiCacheSettings['cache_changes']) ? ' checked' : ''; ?>><span><strong>Update changes only</strong><small>Skip unchanged cache entries during the scheduled refresh.</small></span></label></div>
					<div class="sc-form-error" data-sc-cache-form-error hidden></div><div class="sc-form-actions"><button class="sc-button sc-button-primary" type="submit">Save schedule</button><button class="sc-button sc-button-secondary" type="button" data-sc-cache-action="regenerate_cache">Regenerate cache</button><button class="sc-button sc-button-secondary is-danger" type="button" data-sc-cache-action="disable_cache">Disable cache</button></div>
				</form>
			<?php else: ?><p class="sc-section-copy">Caching is disabled. Enabling it starts the existing cache engine and cache handler.</p><div class="sc-form-actions"><button class="sc-button sc-button-primary" type="button" data-sc-cache-action="enable_cache">Enable cache</button></div><?php endif; ?>
		</section>

		<section class="sc-form sc-cache-card" aria-labelledby="redis-settings-heading">
			<div class="sc-section-heading"><div><h2 id="redis-settings-heading">Redis connection handler</h2><p>Manage active client connections with Redis instead of MySQL.</p></div><span class="sc-row-status <?php echo $xtreampiRedisEnabled ? 'is-active' : 'is-disabled'; ?>"><?php echo $xtreampiRedisEnabled ? 'Enabled' : 'Disabled'; ?></span></div>
			<?php if ($xtreampiRedisEnabled): ?><div class="sc-cache-health"><div><span>Server</span><strong class="<?php echo $xtreampiRedisOnline ? 'is-ok' : 'is-error'; ?>"><?php echo $xtreampiRedisOnline ? 'Online' : 'Offline'; ?></strong></div><div><span>Authentication</span><strong class="<?php echo $xtreampiRedisAuthenticated ? 'is-ok' : 'is-error'; ?>"><?php echo $xtreampiRedisAuthenticated ? 'Authenticated' : 'Invalid password'; ?></strong></div></div><p class="sc-section-copy">Disabling Redis disconnects active clients. Clearing its database drops all active connections.</p><div class="sc-form-actions"><button class="sc-button sc-button-secondary" type="button" data-sc-cache-action="clear_redis">Clear Redis database</button><button class="sc-button sc-button-secondary is-danger" type="button" data-sc-cache-action="disable_handler">Disable handler</button></div><?php else: ?><p class="sc-section-copy">Enable Redis when your connection volume would benefit from moving live connection management out of MySQL.</p><div class="sc-form-actions"><button class="sc-button sc-button-primary" type="button" data-sc-cache-action="enable_handler">Enable handler</button></div><?php endif; ?>
		</section>
	</div>

	<section class="sc-data-panel sc-cache-metrics"><div class="sc-section-heading"><div><h2>Cache coverage</h2><p>Current cached records compared with database totals.</p></div><span>Last refresh: <?php echo !empty($xtreampiCacheSettings['last_cache']) ? $xtreampiEscape(date((string) ($xtreampiCacheSettings['datetime_format'] ?? 'Y-m-d H:i:s'), intval($xtreampiCacheSettings['last_cache']))) : 'Never'; ?></span></div><div class="sc-cache-grid"><div><span>Streams</span><strong><?php echo number_format($xtreampiCachedStreams); ?> / <?php echo number_format($xtreampiStreamTotal); ?></strong></div><div><span>Lines</span><strong><?php echo number_format($xtreampiCachedLines); ?> / <?php echo number_format($xtreampiLineTotal); ?></strong></div><div><span>Series</span><strong><?php echo number_format($xtreampiCachedSeries); ?> / <?php echo number_format($xtreampiSeriesTotal); ?></strong></div><div><span>Last refresh duration</span><strong><?php echo $xtreampiEscape(TimeUtils::secondsToTime(intval($xtreampiCacheSettings['last_cache_taken'] ?? 0))); ?></strong></div></div></section>
	<p class="sc-cache-result" data-sc-cache-result role="status" aria-live="polite"></p>
</section>
