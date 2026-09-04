<?php

use XcVm\Core\Auth\Authorization;

$streamcreedPage = defined('PAGE_NAME') ? PAGE_NAME : 'dashboard';
$streamcreedUser = $rUserInfo['username'] ?? $rUserInfo['name'] ?? 'Administrator';
$streamcreedServerName = $rSettings['server_name'] ?? 'XC_VM';
$streamcreedCanAny = static function (array $permissions): bool {
	if ($permissions === []) {
		return true;
	}
	foreach ($permissions as $permission) {
		if (Authorization::check('adv', $permission)) {
			return true;
		}
	}
	return false;
};
$streamcreedPrimaryNav = [
	['Dashboard', 'dashboard', 'fe-activity', [], ['dashboard', 'index'], null],
	['Live Connections', 'live_connections', 'fe-wifi', ['live_connections'], ['live_connections'], null],
	['Streaming Lines', '#', 'fe-menu', ['users'], ['lines', 'line', 'line_mass'], 'lines'],
	['Users', '#', 'fe-users', ['mng_regusers', 'mass_edit_reguser', 'mng_packages', 'mng_groups'], ['users', 'user', 'user_mass', 'packages', 'package', 'groups', 'group'], 'users'],
	['Devices', '#', 'fe-monitor', ['manage_mag', 'manage_e2', 'add_hmac'], ['mags', 'mag', 'enigmas', 'enigma', 'hmacs', 'hmac'], 'devices'],
	['Content', '#', 'fe-play', ['streams', 'movies', 'series', 'radio', 'categories', 'bouquets'], ['streams', 'stream', 'movies', 'movie', 'series', 'serie', 'series_mass', 'episodes', 'episode', 'episodes_mass', 'radios', 'radio', 'stream_categories', 'stream_category', 'bouquets', 'bouquet', 'bouquet_order', 'bouquet_sort'], 'content'],
	['Servers', '#', 'fe-server', ['servers', 'add_server', 'edit_server', 'server_order', 'process_monitor'], ['servers', 'server', 'server_view', 'server_install', 'server_order', 'proxies', 'proxy', 'process_monitor'], 'servers'],
	['Logs', '#', 'fe-clock', ['panel_logs', 'client_request_log', 'login_logs'], ['panel_logs', 'client_logs', 'login_logs', 'user_logs', 'stream_errors'], 'logs'],
	['System', '#', 'fe-settings', ['settings'], ['settings', 'modules', 'backups', 'cache', 'rtmp_monitor', 'stream_rank', 'theft_detection'], 'system'],
	['Tickets Support', 'tickets', 'fe-help-circle', ['tickets'], ['tickets', 'ticket', 'ticket_view'], null],
];
$streamcreedDrillNav = [
	'lines' => ['Streaming Lines', [
		['', [['Create New Line', 'line', ['add_user']], ['Manage Lines', 'lines', ['users']], ['Mass Edit Lines', 'line_mass', ['mass_edit_users']]]],
	]],
	'users' => ['Users', [
		['', [['Registered Users', 'users', ['mng_regusers']], ['Mass Edit Users', 'user_mass', ['mass_edit_reguser']], ['Packages', 'packages', ['mng_packages']], ['Groups', 'groups', ['mng_groups']]]],
	]],
	'devices' => ['Devices', [
		['MAG Devices', [['Add MAG Device', 'mag', ['add_mag']], ['Manage MAG Devices', 'mags', ['manage_mag']]]],
		['Enigma2 Devices', [['Add Enigma2 Device', 'enigma', ['add_e2']], ['Manage Enigma2 Devices', 'enigmas', ['manage_e2']]]],
		['HMAC Devices', [['Manage HMAC Devices', 'hmacs', ['add_hmac']]]],
	]],
	'content' => ['Content', [
		['', [['Live Streams', 'streams', ['streams']], ['VOD Movies', 'movies', ['movies']], ['TV Series', 'series', ['series']], ['Radio', 'radios', ['radio']], ['Streaming Categories', 'stream_categories', ['categories']], ['Bouquets', 'bouquets', ['bouquets']]]],
	]],
	'servers' => ['Servers', [
		['', [['Install Load Balancer', 'server_install', ['add_server']], ['Manage Servers', 'servers', ['servers']], ['Manage Proxies', 'proxies', ['servers']], ['Server Order', 'server_order', ['server_order']], ['Process Monitor', 'process_monitor', ['process_monitor']]]],
	]],
	'logs' => ['Logs', [['', [['Panel Logs', 'panel_logs', ['panel_logs']], ['Client Logs', 'client_logs', ['client_request_log']], ['Login Logs', 'login_logs', ['login_logs']], ['User Logs', 'user_logs', ['reg_userlog']]]]]],
	'system' => ['System', [
		['Infrastructure', [['RTMP Management', 'rtmp_monitor', ['rtmp_monitor']], ['Statistics', 'stream_rank', ['streams']]]],
		['Administration', [['Settings', 'settings', ['settings']], ['Cache / Redis', 'cache', ['cache']], ['Modules', 'modules', ['modules']], ['Backups', 'backups', ['backups']], ['Security plug-ins', 'theft_detection', ['theft_detection']]]],
	]],
];
$streamcreedActiveDrill = null;
foreach ($streamcreedPrimaryNav as $streamcreedNavItem) {
	if ($streamcreedNavItem[5] && in_array($streamcreedPage, $streamcreedNavItem[4], true)) {
		$streamcreedActiveDrill = $streamcreedNavItem[5];
		break;
	}
}
$streamcreedIsDashboard = in_array($streamcreedPage, ['dashboard', 'index'], true);
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex,nofollow">
	<title><?php echo htmlspecialchars($streamcreedServerName, ENT_QUOTES, 'UTF-8'); ?><?php echo isset($_TITLE) ? ' | ' . htmlspecialchars((string) $_TITLE, ENT_QUOTES, 'UTF-8') : ''; ?></title>
	<link rel="shortcut icon" href="assets/images/favicon.ico">
	<link rel="stylesheet" href="assets/css/icons.css">
	<link rel="stylesheet" href="assets/streamcreed/streamcreed.css">
</head>
<body class="streamcreed-ui <?php echo $streamcreedIsDashboard ? 'sc-page-dashboard' : 'sc-page-inner'; ?><?php echo $streamcreedActiveDrill ? ' sc-has-context-nav' : ''; ?>">
	<div class="sc-mobile-scrim" data-sc-sidebar-close></div>
	<div class="sc-app-shell">
		<aside class="sc-sidebar<?php echo $streamcreedActiveDrill ? ' is-drilling' : ''; ?>" id="streamcreed-sidebar" aria-label="Admin navigation">
			<a class="sc-brand" href="dashboard" aria-label="<?php echo htmlspecialchars($streamcreedServerName, ENT_QUOTES, 'UTF-8'); ?> dashboard">
				<span class="sc-brand-mark" aria-hidden="true"><i class="fe-zap"></i></span>
				<span class="sc-brand-copy"><strong>XC_VM</strong><small>STREAM</small></span>
			</a>

			<nav class="sc-nav" data-sc-primary-nav>
				<?php foreach ($streamcreedPrimaryNav as [$label, $url, $icon, $permissions, $activePages, $drill]): ?>
					<?php if (!$streamcreedCanAny($permissions)) continue; ?>
					<?php $streamcreedActive = in_array($streamcreedPage, $activePages, true); ?>
					<?php if ($drill): ?>
						<button class="sc-nav-link" type="button" data-sc-nav-open="<?php echo $drill; ?>">
					<?php else: ?><a class="sc-nav-link<?php echo $streamcreedActive ? ' is-active' : ''; ?>" href="<?php echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>"><?php endif; ?>
						<i class="<?php echo htmlspecialchars($icon, ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i>
						<span><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></span>
					<?php echo $drill ? '</button>' : '</a>'; ?>
				<?php endforeach; ?>
			</nav>
			<?php foreach ($streamcreedDrillNav as $streamcreedKey => [$streamcreedTitle, $streamcreedSections]): $streamcreedPanelActive = $streamcreedKey === $streamcreedActiveDrill; ?>
				<aside class="sc-nav-drill<?php echo $streamcreedPanelActive ? ' is-open' : ''; ?>" data-sc-nav-panel="<?php echo $streamcreedKey; ?>" aria-label="<?php echo htmlspecialchars($streamcreedTitle, ENT_QUOTES, 'UTF-8'); ?> navigation"<?php echo $streamcreedPanelActive ? '' : ' hidden'; ?>><header><span>Navigation</span><button type="button" data-sc-nav-close><i class="fe-chevron-left"></i> Back</button></header><?php foreach ($streamcreedSections as [$sectionLabel, $sectionItems]): $streamcreedSectionItems=array_filter($sectionItems, static fn(array $item): bool => $streamcreedCanAny($item[2])); if(!$streamcreedSectionItems) continue; ?><section><?php if ($sectionLabel !== ''): ?><h3><?php echo htmlspecialchars($sectionLabel, ENT_QUOTES, 'UTF-8'); ?></h3><?php endif; ?><?php foreach($streamcreedSectionItems as [$itemLabel,$itemUrl]): $streamcreedItemPage = explode('?', $itemUrl, 2)[0]; ?><a<?php echo $streamcreedItemPage === $streamcreedPage ? ' class="is-active"' : ''; ?> href="<?php echo htmlspecialchars($itemUrl, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($itemLabel, ENT_QUOTES, 'UTF-8'); ?></a><?php endforeach; ?></section><?php endforeach; ?></aside>
			<?php endforeach; ?>
		</aside>

		<div class="sc-workspace">
			<header class="sc-topbar">
				<button class="sc-icon-button sc-menu-toggle" type="button" data-sc-sidebar-toggle aria-controls="streamcreed-sidebar" aria-expanded="false" aria-label="Open navigation">
					<i class="fe-menu" aria-hidden="true"></i>
				</button>
				<div class="sc-topbar-spacer"></div>
				<span class="sc-version">v<?php echo htmlspecialchars((string) XC_VM_VERSION, ENT_QUOTES, 'UTF-8'); ?></span>
				<details class="sc-account">
					<summary aria-label="Open account menu">
						<span class="sc-avatar"><i class="fe-user" aria-hidden="true"></i></span>
						<span class="sc-account-name"><?php echo htmlspecialchars((string) $streamcreedUser, ENT_QUOTES, 'UTF-8'); ?></span>
						<i class="fe-chevron-down" aria-hidden="true"></i>
					</summary>
					<div class="sc-account-menu">
						<a href="edit_profile"><i class="fe-user" aria-hidden="true"></i> Profile</a>
						<a href="dashboard?admin_ui=legacy"><i class="fe-corner-up-left" aria-hidden="true"></i> Use legacy UI</a>
						<a href="logout"><i class="fe-log-out" aria-hidden="true"></i> Log out</a>
					</div>
				</details>
			</header>

			<div class="sc-content-layout<?php echo $streamcreedActiveDrill ? ' has-context-nav' : ''; ?>">
				<?php if ($streamcreedActiveDrill): [$streamcreedContextTitle, $streamcreedContextSections] = $streamcreedDrillNav[$streamcreedActiveDrill]; ?>
					<nav class="sc-context-nav" aria-label="<?php echo htmlspecialchars($streamcreedContextTitle, ENT_QUOTES, 'UTF-8'); ?> navigation">
						<?php foreach ($streamcreedContextSections as [$sectionLabel, $sectionItems]): $streamcreedContextItems = array_filter($sectionItems, static fn(array $item): bool => $streamcreedCanAny($item[2])); if (!$streamcreedContextItems) continue; ?>
							<section><?php if ($sectionLabel !== ''): ?><h2><?php echo htmlspecialchars($sectionLabel, ENT_QUOTES, 'UTF-8'); ?></h2><?php endif; ?><?php foreach ($streamcreedContextItems as [$itemLabel, $itemUrl]): $streamcreedContextPage = explode('?', $itemUrl, 2)[0]; ?><a<?php echo $streamcreedContextPage === $streamcreedPage ? ' class="is-active"' : ''; ?> href="<?php echo htmlspecialchars($itemUrl, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($itemLabel, ENT_QUOTES, 'UTF-8'); ?></a><?php endforeach; ?></section>
						<?php endforeach; ?>
					</nav>
				<?php endif; ?>
				<main class="sc-main" id="main-content">
