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
$streamcreedNav = [
	['Dashboard', 'dashboard', 'fe-activity', [], ['dashboard', 'index']],
	['Live Connections', 'live_connections', 'fe-wifi', ['live_connections'], ['live_connections']],
	['Subscriptions', 'lines', 'fe-shopping-cart', ['users'], ['lines', 'line', 'line_mass']],
	['Live / VOD / Radio', 'streams', 'fe-play', ['streams', 'movies', 'radio'], ['streams', 'stream', 'movies', 'movie', 'series', 'serie', 'radios', 'radio']],
	['RTMP Management', 'rtmp_monitor', 'fe-radio', ['rtmp_monitor'], ['rtmp_monitor', 'rtmp_ips', 'rtmp_ip']],
	['Streaming Servers', 'servers', 'fe-server', ['servers'], ['servers', 'server', 'server_view', 'server_install']],
	['Bouquets', 'bouquets', 'fe-gift', ['bouquets'], ['bouquets', 'bouquet', 'bouquet_order', 'bouquet_sort']],
	['Registered Users', 'users', 'fe-users', ['mng_regusers'], ['users', 'user', 'user_mass']],
	['Reseller Packages', 'packages', 'fe-package', ['packages'], ['packages', 'package']],
	['Statistics', 'stream_rank', 'fe-bar-chart-2', ['streams'], ['stream_rank']],
	['Security plug-ins', 'theft_detection', 'fe-shield', ['theft_detection', 'settings'], ['theft_detection', 'ips', 'isps', 'hmacs', 'asns']],
	['Logs', 'panel_logs', 'fe-clock', ['panel_logs'], ['panel_logs', 'client_logs', 'login_logs', 'user_logs', 'stream_errors']],
	['System', 'settings', 'fe-settings', ['settings'], ['settings', 'modules', 'backups', 'cache', 'process_monitor']],
	['Tickets Support', 'tickets', 'fe-help-circle', ['tickets'], ['tickets', 'ticket', 'ticket_view']],
];
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
<body class="streamcreed-ui">
	<div class="sc-mobile-scrim" data-sc-sidebar-close></div>
	<div class="sc-app-shell">
		<aside class="sc-sidebar" id="streamcreed-sidebar" aria-label="Admin navigation">
			<a class="sc-brand" href="dashboard" aria-label="<?php echo htmlspecialchars($streamcreedServerName, ENT_QUOTES, 'UTF-8'); ?> dashboard">
				<span class="sc-brand-mark" aria-hidden="true"><i class="fe-zap"></i></span>
				<span class="sc-brand-copy"><strong>XC_VM</strong><small>STREAM</small></span>
			</a>

			<nav class="sc-nav">
				<?php foreach ($streamcreedNav as [$label, $url, $icon, $permissions, $activePages]): ?>
					<?php if (!$streamcreedCanAny($permissions)) continue; ?>
					<a class="sc-nav-link<?php echo in_array($streamcreedPage, $activePages, true) ? ' is-active' : ''; ?>" href="<?php echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>">
						<i class="<?php echo htmlspecialchars($icon, ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i>
						<span><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></span>
					</a>
				<?php endforeach; ?>
			</nav>
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

			<main class="sc-main" id="main-content">
