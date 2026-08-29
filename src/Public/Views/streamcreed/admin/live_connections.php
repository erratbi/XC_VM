<?php

use XcVm\Core\Auth\Authorization;
use XcVm\Domain\Server\ServerRepository;

$streamcreedPageScripts = ['assets/streamcreed/connections.js'];
$streamcreedServers = array_filter(ServerRepository::getAll(), static fn(array $server): bool => !empty($server['enabled']));
$streamcreedRedisHandler = !empty($rSettings['redis_handler']);
$streamcreedDefaultEntries = intval($rSettings['default_entries'] ?? 25);
if (!in_array($streamcreedDefaultEntries, [10, 25, 50, 100], true)) $streamcreedDefaultEntries = 25;
$streamcreedConnectionPermissions = [
	'lines' => Authorization::check('adv', 'users'),
	'mags' => Authorization::check('adv', 'edit_mag'),
	'enigmas' => Authorization::check('adv', 'edit_e2'),
	'hmacs' => Authorization::check('adv', 'add_hmac'),
	'servers' => Authorization::check('adv', 'servers'),
];
?>
<section class="sc-connections" data-sc-connections data-endpoint="table" data-default-entries="<?php echo $streamcreedDefaultEntries; ?>" data-can-lines="<?php echo $streamcreedConnectionPermissions['lines'] ? '1' : '0'; ?>" data-can-mags="<?php echo $streamcreedConnectionPermissions['mags'] ? '1' : '0'; ?>" data-can-enigmas="<?php echo $streamcreedConnectionPermissions['enigmas'] ? '1' : '0'; ?>" data-can-hmacs="<?php echo $streamcreedConnectionPermissions['hmacs'] ? '1' : '0'; ?>" data-can-servers="<?php echo $streamcreedConnectionPermissions['servers'] ? '1' : '0'; ?>">
	<div class="sc-page-heading">
		<div>
			<p class="sc-eyebrow">Real-time activity</p>
			<h1>Live Connections</h1>
		</div>
		<div class="sc-page-actions">
			<a class="sc-button sc-button-secondary" href="line_activity"><i class="fe-clock" aria-hidden="true"></i> Activity logs</a>
		</div>
	</div>

	<div class="sc-toolbar">
		<?php if (!$streamcreedRedisHandler): ?>
			<label class="sc-search-field">
				<i class="fe-search" aria-hidden="true"></i>
				<span class="sc-visually-hidden">Search live connections</span>
				<input type="search" placeholder="Search user, stream, IP, player, or ISP" data-sc-connection-search>
			</label>
		<?php else: ?>
			<span class="sc-toolbar-note"><i class="fe-zap" aria-hidden="true"></i> Real-time Redis index</span>
		<?php endif; ?>
		<label class="sc-filter-field">
			<span>Server</span>
			<select data-sc-connection-server>
				<option value="">All servers</option>
				<?php foreach ($streamcreedServers as $server): ?>
					<option value="<?php echo intval($server['id']); ?>"><?php echo htmlspecialchars((string) $server['server_name'], ENT_QUOTES, 'UTF-8'); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
		<label class="sc-filter-field sc-entry-field">
			<span>Per page</span>
			<select data-sc-connection-entries>
				<?php foreach ([10, 25, 50, 100] as $streamcreedEntries): ?>
					<option value="<?php echo $streamcreedEntries; ?>"<?php echo $streamcreedDefaultEntries === $streamcreedEntries ? ' selected' : ''; ?>><?php echo $streamcreedEntries; ?></option>
				<?php endforeach; ?>
			</select>
		</label>
	</div>

	<div class="sc-data-panel">
		<div class="sc-table-scroll">
			<table class="sc-data-table sc-connections-table">
				<thead><tr><th>Quality</th><th>Subscriber</th><th>Stream</th><th>Server</th><th>Client</th><th>IP / ISP</th><th>Duration</th><th>Output</th></tr></thead>
				<tbody data-sc-connection-rows><tr><td class="sc-table-state" colspan="8"><span class="sc-spinner" aria-hidden="true"></span> Loading live connections…</td></tr></tbody>
			</table>
		</div>
		<footer class="sc-table-footer">
			<span data-sc-connection-range>Loading…</span>
			<div class="sc-pagination"><button type="button" data-sc-connection-previous><i class="fe-chevron-left" aria-hidden="true"></i> Previous</button><span data-sc-connection-page>Page 1</span><button type="button" data-sc-connection-next>Next <i class="fe-chevron-right" aria-hidden="true"></i></button></div>
		</footer>
	</div>
</section>
