<?php

use XcVm\Core\Auth\Authorization;

$xtreampiSelectedServer = isset($_GET['server_id']) ? intval($_GET['server_id']) : null;
$xtreampiVisibleServers = array_values(array_filter($rOrderedServers, static function (array $server) use ($xtreampiSelectedServer): bool {
	if (empty($server['enabled'])) {
		return false;
	}
	return $xtreampiSelectedServer === null || intval($server['id']) === $xtreampiSelectedServer;
}));
$xtreampiStatsUrl = './api?action=stats';
if ($xtreampiSelectedServer !== null) {
	$xtreampiStatsUrl .= '&server_id=' . $xtreampiSelectedServer;
}
$xtreampiServerType = static function (array $server): string {
	if (intval($server['server_type'] ?? 0) !== 0) {
		return !empty($server['enabled']) ? 'Proxy Server' : 'Proxy Disabled';
	}
	if (!empty($server['is_main'])) {
		return 'Main Server (IPTV Panel)';
	}
	return !empty($server['enabled']) ? 'Load Balancer' : 'Server Disabled';
};
?>
<?php if (!Authorization::check('adv', 'index')): ?>
	<section class="sc-notice sc-notice-danger" role="alert">
		<i class="fe-alert-triangle" aria-hidden="true"></i>
		<div>
			<strong>Dashboard access is restricted</strong>
			<p>Your account does not have permission to view dashboard statistics.</p>
		</div>
	</section>
<?php else: ?>
	<section class="sc-dashboard" data-sc-dashboard data-stats-url="<?php echo htmlspecialchars($xtreampiStatsUrl, ENT_QUOTES, 'UTF-8'); ?>" data-selected-server="<?php echo $xtreampiSelectedServer ?? ''; ?>">
		<div class="sc-section-heading sc-overview-heading">
			<h1>Overview</h1>
			<div class="sc-heading-line"></div>
			<label class="sc-server-filter">
				<span class="sc-visually-hidden">Filter dashboard by server</span>
				<select data-sc-server-filter>
					<option value="">All servers</option>
					<?php foreach ($rOrderedServers as $server): ?>
						<?php if (empty($server['enabled'])) continue; ?>
						<option value="<?php echo intval($server['id']); ?>"<?php echo $xtreampiSelectedServer === intval($server['id']) ? ' selected' : ''; ?>><?php echo htmlspecialchars((string) $server['server_name'], ENT_QUOTES, 'UTF-8'); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
		</div>

		<div class="sc-metric-grid" aria-live="polite">
			<a class="sc-metric-card" href="live_connections">
				<span class="sc-metric-icon sc-tone-amber"><i class="fe-user" aria-hidden="true"></i></span>
				<strong data-sc-stat="online-users">0</strong>
				<span>Online Users</span>
			</a>
			<a class="sc-metric-card" href="live_connections">
				<span class="sc-metric-icon sc-tone-blue"><i class="fe-box" aria-hidden="true"></i></span>
				<strong data-sc-stat="open-connections">0</strong>
				<span>Open Connections</span>
			</a>
			<div class="sc-metric-card">
				<span class="sc-metric-icon sc-tone-green"><i class="fe-upload-cloud" aria-hidden="true"></i></span>
				<strong><span data-sc-stat="output">0</span> <small>Mbps</small></strong>
				<span>Total Output</span>
			</div>
			<div class="sc-metric-card">
				<span class="sc-metric-icon sc-tone-red"><i class="fe-download-cloud" aria-hidden="true"></i></span>
				<strong><span data-sc-stat="input">0</span> <small>Mbps</small></strong>
				<span>Total Input</span>
			</div>
			<a class="sc-metric-card" href="streams">
				<span class="sc-metric-icon sc-tone-purple"><i class="fe-list" aria-hidden="true"></i></span>
				<strong><span data-sc-stat="online-streams">0</span>/<span data-sc-stat="offline-streams">0</span></strong>
				<span>Online / Offline</span>
			</a>
			<div class="sc-metric-card">
				<span class="sc-metric-icon sc-tone-cyan"><i class="fe-wifi" aria-hidden="true"></i></span>
				<strong data-sc-stat="active-requests">0</strong>
				<span>Active Requests</span>
			</div>
		</div>

		<div class="sc-section-heading sc-server-heading">
			<h2>Server Info</h2>
			<div class="sc-heading-line"></div>
			<span class="sc-live-state" data-sc-live-state><i></i> Connecting</span>
		</div>

		<div class="sc-server-grid">
			<?php foreach ($xtreampiVisibleServers as $server): ?>
				<?php $serverID = intval($server['id']); ?>
				<article class="sc-server-card<?php echo empty($server['server_online']) ? ' is-offline' : ''; ?>" data-sc-server="<?php echo $serverID; ?>">
					<header class="sc-server-card-header">
						<div>
							<h3><?php echo htmlspecialchars((string) $server['server_name'], ENT_QUOTES, 'UTF-8'); ?></h3>
							<span><?php echo htmlspecialchars($xtreampiServerType($server), ENT_QUOTES, 'UTF-8'); ?></span>
						</div>
						<a href="server_view?id=<?php echo $serverID; ?>">Server Monitor <i class="fe-external-link" aria-hidden="true"></i></a>
					</header>
					<div class="sc-server-card-body">
						<div class="sc-server-facts">
							<dl><dt>Open Connections</dt><dd data-sc-server-stat="connections">0</dd></dl>
							<dl><dt>Uptime</dt><dd data-sc-server-stat="uptime">--</dd></dl>
							<dl><dt>Active Requests</dt><dd data-sc-server-stat="requests">0</dd></dl>
							<dl><dt>Live Streams</dt><dd data-sc-server-stat="streams">0</dd></dl>
							<dl><dt>Upload</dt><dd><span data-sc-server-stat="upload">0</span> Mbps</dd></dl>
							<dl><dt>Download</dt><dd><span data-sc-server-stat="download">0</span> Mbps</dd></dl>
						</div>
						<div class="sc-server-resources">
							<div class="sc-resource"><div><span>RAM</span><b data-sc-server-value="mem">0%</b></div><div class="sc-progress"><i data-sc-server-bar="mem"></i></div></div>
							<div class="sc-resource"><div><span>CPU</span><b data-sc-server-value="cpu">0%</b></div><div class="sc-progress"><i data-sc-server-bar="cpu"></i></div></div>
							<div class="sc-resource"><div><span>Disk</span><b data-sc-server-value="fs">0%</b></div><div class="sc-progress"><i data-sc-server-bar="fs"></i></div></div>
							<div class="sc-resource"><div><span>I/O</span><b data-sc-server-value="io">0%</b></div><div class="sc-progress"><i data-sc-server-bar="io"></i></div></div>
						</div>
					</div>
					<div class="sc-server-offline-message"><i class="fe-alert-circle" aria-hidden="true"></i> Server offline</div>
				</article>
			<?php endforeach; ?>
			<?php if ($xtreampiVisibleServers === []): ?>
				<div class="sc-empty-state"><i class="fe-server" aria-hidden="true"></i><strong>No enabled servers found</strong></div>
			<?php endif; ?>
		</div>
	</section>
<?php endif; ?>
