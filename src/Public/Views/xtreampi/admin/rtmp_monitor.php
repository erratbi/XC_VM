<?php

use XcVm\Core\Auth\Authorization;
use XcVm\Core\Http\RequestManager;

$xtreampiPageScripts = ['assets/xtreampi/rtmp-monitor.js'];
$xtreampiRtmp = is_array($rRTMPInfo ?? null) ? $rRTMPInfo : [];
$xtreampiServers = is_array($rServers ?? null) ? $rServers : [];
$xtreampiServerId = intval(RequestManager::getAll()['server'] ?? SERVER_ID);
$xtreampiServer = is_array($xtreampiServers[$xtreampiServerId] ?? null) ? $xtreampiServers[$xtreampiServerId] : [];
$xtreampiRawStreams = $xtreampiRtmp['server']['application']['live']['stream'] ?? [];
if (!is_array($xtreampiRawStreams)) $xtreampiRawStreams = [];
$xtreampiStreams = isset($xtreampiRawStreams['name']) ? [$xtreampiRawStreams] : array_values($xtreampiRawStreams);
usort($xtreampiStreams, static fn (array $left, array $right): int => strnatcasecmp((string) ($left['name'] ?? ''), (string) ($right['name'] ?? '')));
$xtreampiSettings = is_array($rSettings ?? null) ? $rSettings : [];
$xtreampiEntryOptions = [10, 25, 50, 250, 500, 1000];
$xtreampiEntries = intval($xtreampiSettings['default_entries'] ?? 10);
if (!in_array($xtreampiEntries, $xtreampiEntryOptions, true)) $xtreampiEntries = 10;
$xtreampiCanManage = Authorization::check('adv', 'rtmp');
$xtreampiEscape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$xtreampiDuration = static function ($seconds): string {
	$seconds = max(0, intval($seconds));
	if ($seconds >= 86400) return sprintf('%02dd %02dh %02dm', intdiv($seconds, 86400), intdiv($seconds, 3600) % 24, intdiv($seconds, 60) % 60);
	return sprintf('%02dh %02dm %02ds', intdiv($seconds, 3600), intdiv($seconds, 60) % 60, $seconds % 60);
};
?>
<section class="sc-rtmp-monitor" data-sc-rtmp-monitor data-server-id="<?php echo $xtreampiServerId; ?>">
	<div class="sc-page-heading"><div><p class="sc-eyebrow">Infrastructure</p><h1>RTMP Management</h1><p class="sc-section-copy">Inspect the RTMP process and live publishers on each server.</p></div><div class="sc-page-actions"><button class="sc-button sc-button-secondary" type="button" data-sc-rtmp-refresh><i class="fe-refresh-cw" aria-hidden="true"></i> Refresh</button></div></div>

	<?php if (!$xtreampiRtmp): ?><div class="sc-notice sc-notice-danger" role="alert"><i class="fe-alert-triangle" aria-hidden="true"></i><div><strong>RTMP statistics are unavailable</strong><p>The selected server did not return RTMP status information. Check its connection and try again.</p></div></div><?php endif; ?>
	<div class="sc-notice sc-rtmp-result" data-sc-rtmp-result role="status" aria-live="polite" hidden><i class="fe-info" aria-hidden="true"></i><div><strong data-sc-rtmp-result-title>RTMP monitor</strong><p data-sc-rtmp-result-message></p></div></div>

	<div class="sc-summary-grid sc-rtmp-summary">
		<div class="sc-summary-card"><span class="sc-summary-icon sc-rtmp-icon"><i class="fe-cpu" aria-hidden="true"></i></span><div><strong><?php echo $xtreampiEscape($xtreampiRtmp['pid'] ?? '—'); ?></strong><span>RTMP process ID</span></div></div>
		<div class="sc-summary-card"><span class="sc-summary-icon sc-rtmp-icon"><i class="fe-layers" aria-hidden="true"></i></span><div><strong><?php echo $xtreampiEscape($xtreampiRtmp['nginx_version'] ?? '—'); ?></strong><span>NGINX version</span></div></div>
		<div class="sc-summary-card"><span class="sc-summary-icon sc-rtmp-icon"><i class="fe-film" aria-hidden="true"></i></span><div><strong><?php echo $xtreampiEscape($xtreampiRtmp['nginx_http_flv_version'] ?? '—'); ?></strong><span>HTTP-FLV version</span></div></div>
		<div class="sc-summary-card"><span class="sc-summary-icon sc-rtmp-icon"><i class="fe-clock" aria-hidden="true"></i></span><div><strong><?php echo $xtreampiDuration($xtreampiRtmp['uptime'] ?? 0); ?></strong><span>Process uptime</span></div></div>
		<div class="sc-summary-card"><span class="sc-summary-icon sc-rtmp-icon"><i class="fe-arrow-down-left" aria-hidden="true"></i></span><div><strong><?php echo number_format(floatval($xtreampiRtmp['bw_in'] ?? 0) / 1000000, 2); ?> <small>Mbps</small></strong><span>Input bandwidth</span></div></div>
		<div class="sc-summary-card"><span class="sc-summary-icon sc-rtmp-icon"><i class="fe-arrow-up-right" aria-hidden="true"></i></span><div><strong><?php echo number_format(floatval($xtreampiRtmp['bw_out'] ?? 0) / 1000000, 2); ?> <small>Mbps</small></strong><span>Output bandwidth</span></div></div>
	</div>

	<div class="sc-toolbar">
		<label class="sc-search-field"><i class="fe-search" aria-hidden="true"></i><span class="sc-visually-hidden">Search RTMP streams</span><input type="search" placeholder="Search stream, URL, or publisher IP" data-sc-rtmp-search></label>
		<label class="sc-filter-field"><span>Server</span><select data-sc-rtmp-server><?php foreach ($xtreampiServers as $xtreampiId => $xtreampiCandidate): ?><option value="<?php echo intval($xtreampiCandidate['id'] ?? $xtreampiId); ?>"<?php echo intval($xtreampiCandidate['id'] ?? $xtreampiId) === $xtreampiServerId ? ' selected' : ''; ?>><?php echo $xtreampiEscape($xtreampiCandidate['server_name'] ?? ('Server #' . $xtreampiId)); ?></option><?php endforeach; ?></select></label>
		<label class="sc-filter-field sc-entry-field"><span>Per page</span><select data-sc-rtmp-entries><?php foreach ($xtreampiEntryOptions as $xtreampiOption): ?><option value="<?php echo $xtreampiOption; ?>"<?php echo $xtreampiEntries === $xtreampiOption ? ' selected' : ''; ?>><?php echo $xtreampiOption; ?></option><?php endforeach; ?></select></label>
	</div>

	<div class="sc-data-panel"><div class="sc-table-scroll"><table class="sc-data-table"><thead><tr><th>ID</th><th>RTMP URL</th><th>Publisher IP</th><th>Uptime</th><th>Clients</th><th>Stream information</th><th><span class="sc-visually-hidden">Actions</span></th></tr></thead><tbody data-sc-rtmp-rows>
		<?php foreach ($xtreampiStreams as $xtreampiStream):
			$xtreampiClients = $xtreampiStream['client'] ?? [];
			if (!is_array($xtreampiClients)) $xtreampiClients = [];
			if (isset($xtreampiClients['id'])) $xtreampiClients = [$xtreampiClients];
			$xtreampiPublisher = '';
			$xtreampiViewerCount = count($xtreampiClients);
			foreach ($xtreampiClients as $xtreampiClient) if (intval($xtreampiStream['time'] ?? 0) <= intval($xtreampiClient['time'] ?? -1)) { $xtreampiPublisher = (string) ($xtreampiClient['address'] ?? ''); $xtreampiViewerCount--; break; }
			$xtreampiName = (string) ($xtreampiStream['name'] ?? '');
			$xtreampiVideo = is_array($xtreampiStream['meta']['video'] ?? null) ? $xtreampiStream['meta']['video'] : [];
			$xtreampiAudio = is_array($xtreampiStream['meta']['audio'] ?? null) ? $xtreampiStream['meta']['audio'] : [];
		?>
			<tr data-sc-rtmp-row><td><strong><?php echo $xtreampiEscape($xtreampiName ?: '—'); ?></strong></td><td class="sc-rtmp-url"><?php echo $xtreampiEscape((string) ($xtreampiServer['rtmp_server'] ?? '') . $xtreampiName); ?></td><td><?php if ($xtreampiPublisher !== ''): ?><button class="sc-connection-link" type="button" data-sc-rtmp-whois="<?php echo $xtreampiEscape($xtreampiPublisher); ?>"><?php echo $xtreampiEscape($xtreampiPublisher); ?></button><?php else: ?>—<?php endif; ?></td><td class="sc-table-secondary"><?php echo $xtreampiDuration(intval($xtreampiStream['time'] ?? 0) / 1000); ?></td><td><span class="sc-row-status <?php echo $xtreampiViewerCount > 0 ? 'is-active' : 'is-disabled'; ?>"><?php echo max(0, $xtreampiViewerCount); ?> clients</span></td><td class="sc-rtmp-meta"><span><?php echo number_format(floatval($xtreampiStream['bw_in'] ?? 0) / 1000, 0); ?> Kbps</span><span><?php echo $xtreampiEscape(($xtreampiVideo['width'] ?? '—') . ' × ' . ($xtreampiVideo['height'] ?? '—')); ?></span><span><?php echo $xtreampiEscape($xtreampiVideo['codec'] ?? '—'); ?> / <?php echo $xtreampiEscape($xtreampiAudio['codec'] ?? '—'); ?></span><span><?php echo number_format(floatval($xtreampiVideo['frame_rate'] ?? 0), 0); ?> FPS</span></td><td class="sc-table-actions"><?php if ($xtreampiCanManage && $xtreampiName !== ''): ?><button class="sc-row-action is-danger" type="button" data-sc-rtmp-kill="<?php echo $xtreampiEscape($xtreampiName); ?>">Kill stream</button><?php endif; ?></td></tr>
		<?php endforeach; ?>
		<tr data-sc-rtmp-empty><td class="sc-table-state" colspan="7">No active RTMP streams match this server.</td></tr>
	</tbody></table></div><footer class="sc-table-footer"><span data-sc-rtmp-summary></span><div class="sc-pagination"><button type="button" data-sc-rtmp-previous><i class="fe-chevron-left" aria-hidden="true"></i> Previous</button><span data-sc-rtmp-page>Page 1</span><button type="button" data-sc-rtmp-next>Next <i class="fe-chevron-right" aria-hidden="true"></i></button></div></footer></div>

	<dialog class="sc-dialog sc-whois-dialog" data-sc-rtmp-whois-dialog><header><h2 data-sc-rtmp-whois-title>IP details</h2><button type="button" data-sc-rtmp-whois-close aria-label="Close">×</button></header><div class="sc-dialog-body" data-sc-rtmp-whois-body></div></dialog>
</section>
