<?php

use XcVm\Core\Auth\Authorization;
use XcVm\Domain\Stream\ConnectionTracker;

$xtreampiPageScripts = ['assets/xtreampi/servers.js'];
$xtreampiServers = array_values(array_filter($rServers, static fn(array $server): bool => intval($server['server_type'] ?? 0) === 0));
usort($xtreampiServers, static fn(array $left, array $right): int => intval($left['order'] ?? $left['id']) <=> intval($right['order'] ?? $right['id']));
$xtreampiCanEditServers = Authorization::check('adv', 'edit_server');
$xtreampiCanOrderServers = Authorization::check('adv', 'server_order');
$xtreampiCanMonitorProcesses = Authorization::check('adv', 'process_monitor');
$xtreampiCanViewConnections = Authorization::check('adv', 'live_connections');
$xtreampiRedisHandler = !empty($rSettings['redis_handler']);
$xtreampiMainVersion = (string) ($rServers[SERVER_ID]['xc_vm_version'] ?? XC_VM_VERSION);

$xtreampiStatus = static function (array $server): array {
	if (empty($server['enabled'])) return ['disabled', 'Disabled'];
	if (!empty($server['server_online'])) return ['online', 'Online'];
	return match (intval($server['status'] ?? 0)) {
		3 => ['installing', 'Installing'],
		4 => ['warning', 'Install failed'],
		5 => ['installing', 'Updating'],
		default => ['offline', 'Offline'],
	};
};
?>
<section class="sc-inventory" data-sc-server-inventory>
	<div class="sc-page-heading">
		<div>
			<p class="sc-eyebrow">Infrastructure</p>
			<h1>Streaming Servers</h1>
		</div>
		<div class="sc-page-actions">
			<?php if ($xtreampiCanOrderServers): ?>
				<a class="sc-button sc-button-secondary" href="server_order"><i class="fe-list" aria-hidden="true"></i> Server order</a>
			<?php endif; ?>
			<a class="sc-button sc-button-secondary" href="server_install?proxy"><i class="fe-shield" aria-hidden="true"></i> Install proxy</a>
			<a class="sc-button sc-button-primary" href="server_install"><i class="fe-plus" aria-hidden="true"></i> Install server</a>
		</div>
	</div>

	<div class="sc-toolbar">
		<label class="sc-search-field">
			<i class="fe-search" aria-hidden="true"></i>
			<span class="sc-visually-hidden">Search servers</span>
			<input type="search" placeholder="Search by name, domain, or IP" data-sc-server-search>
		</label>
		<label class="sc-filter-field">
			<span>Status</span>
			<select data-sc-server-status-filter>
				<option value="all">All statuses</option>
				<option value="online">Online</option>
				<option value="offline">Offline</option>
				<option value="disabled">Disabled</option>
				<option value="installing">Installing / updating</option>
				<option value="warning">Install failed</option>
			</select>
		</label>
	</div>

	<div class="sc-inventory-grid">
		<?php foreach ($xtreampiServers as $server): ?>
			<?php
			$serverID = intval($server['id']);
			[$statusKey, $statusLabel] = $xtreampiStatus($server);
			$watchdog = json_decode((string) ($server['watchdog_data'] ?? ''), true);
			if (!is_array($watchdog)) $watchdog = [];
			$cpu = !empty($server['server_online']) ? max(0, min(100, intval($watchdog['cpu'] ?? 0))) : 0;
			$memory = !empty($server['server_online']) ? max(0, min(100, intval($watchdog['total_mem_used_percent'] ?? 0))) : 0;
			$disk = 0;
			if (!empty($server['server_online']) && !empty($watchdog['total_disk_space'])) {
				$disk = max(0, min(100, intval(($watchdog['total_disk_space'] - ($watchdog['free_disk_space'] ?? 0)) / $watchdog['total_disk_space'] * 100)));
			}
			$connections = $xtreampiRedisHandler ? intval($server['connections'] ?? 0) : intval(ConnectionTracker::getLiveConnections($serverID));
			$sent = !empty($server['server_online']) ? intval($watchdog['bytes_sent'] ?? 0) : 0;
			$received = !empty($server['server_online']) ? intval($watchdog['bytes_received'] ?? 0) : 0;
			$domain = trim(explode(',', (string) ($server['domain_name'] ?? ''))[0]);
			$version = trim((string) ($server['xc_vm_version'] ?? ''));
			$versionMismatch = $version !== '' && $xtreampiMainVersion !== '' && $version !== $xtreampiMainVersion;
			$searchValue = strtolower(implode(' ', [$server['server_name'] ?? '', $server['server_ip'] ?? '', $server['private_ip'] ?? '', $domain]));
			?>
			<article class="sc-inventory-card" data-sc-inventory-card data-status="<?php echo htmlspecialchars($statusKey, ENT_QUOTES, 'UTF-8'); ?>" data-search="<?php echo htmlspecialchars($searchValue, ENT_QUOTES, 'UTF-8'); ?>">
				<header class="sc-inventory-card-header">
					<div class="sc-server-identity">
						<span class="sc-status-dot is-<?php echo htmlspecialchars($statusKey, ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></span>
						<div><h2><?php echo htmlspecialchars((string) $server['server_name'], ENT_QUOTES, 'UTF-8'); ?></h2><p><?php echo !empty($server['is_main']) ? 'Main Server' : 'Load Balancer'; ?><?php echo !empty($server['enable_proxy']) ? ' · Proxied' : ''; ?></p></div>
					</div>
					<span class="sc-status-badge is-<?php echo htmlspecialchars($statusKey, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?></span>
				</header>

				<div class="sc-inventory-card-body">
					<div class="sc-server-addresses">
						<div><span>Public IP</span><strong><?php echo htmlspecialchars((string) ($server['server_ip'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?></strong></div>
						<div><span>Domain</span><strong><?php echo $domain !== '' ? htmlspecialchars($domain, ENT_QUOTES, 'UTF-8') : '—'; ?></strong></div>
					</div>

					<div class="sc-server-kpis">
						<div><span>Connections</span><strong><?php echo number_format($connections); ?><small> / <?php echo number_format(intval($server['total_clients'] ?? 0)); ?></small></strong></div>
						<div><span>Traffic</span><strong><?php echo number_format($sent / 125000, 0); ?> ↑ <small><?php echo number_format($received / 125000, 0); ?> ↓ Mbps</small></strong></div>
						<div><span>Latency</span><strong><?php echo number_format(!empty($server['server_online']) ? floatval($server['ping'] ?? 0) : 0, 0); ?> <small>ms</small></strong></div>
						<div><span>Version</span><strong class="<?php echo $versionMismatch ? 'sc-text-warning' : ''; ?>"><?php echo $version !== '' ? htmlspecialchars($version, ENT_QUOTES, 'UTF-8') : 'N/A'; ?></strong></div>
					</div>

					<div class="sc-resource-grid">
						<div class="sc-resource"><div><span>CPU</span><b><?php echo $cpu; ?>%</b></div><div class="sc-progress"><i class="<?php echo $cpu > 75 ? 'is-danger' : ($cpu > 50 ? 'is-warning' : ''); ?>" style="width:<?php echo $cpu; ?>%"></i></div></div>
						<div class="sc-resource"><div><span>RAM</span><b><?php echo $memory; ?>%</b></div><div class="sc-progress"><i class="<?php echo $memory > 75 ? 'is-danger' : ($memory > 50 ? 'is-warning' : ''); ?>" style="width:<?php echo $memory; ?>%"></i></div></div>
						<div class="sc-resource"><div><span>Disk</span><b><?php echo $disk; ?>%</b></div><div class="sc-progress"><i class="<?php echo $disk > 85 ? 'is-danger' : ($disk > 70 ? 'is-warning' : ''); ?>" style="width:<?php echo $disk; ?>%"></i></div></div>
					</div>
				</div>

				<footer class="sc-inventory-card-footer">
					<span>Order <?php echo intval(($server['order'] ?? 0) ?: $serverID); ?><?php echo !empty($server['network_guaranteed_speed']) ? ' · ' . number_format(floatval($server['network_guaranteed_speed']), 0) . ' Mbps capacity' : ''; ?></span>
					<div>
						<?php if ($xtreampiCanViewConnections): ?><a href="live_connections?server=<?php echo $serverID; ?>">Connections</a><?php endif; ?>
						<?php if ($xtreampiCanMonitorProcesses): ?><a href="process_monitor?server=<?php echo $serverID; ?>">Processes</a><?php endif; ?>
						<a href="server_view?id=<?php echo $serverID; ?>">Monitor</a>
						<?php if ($xtreampiCanEditServers): ?><a class="sc-card-primary-action" href="server?id=<?php echo $serverID; ?>">Edit</a><?php endif; ?>
					</div>
				</footer>
			</article>
		<?php endforeach; ?>
	</div>

	<div class="sc-empty-state sc-filter-empty" data-sc-filter-empty hidden><i class="fe-search" aria-hidden="true"></i><strong>No servers match these filters</strong></div>
</section>
