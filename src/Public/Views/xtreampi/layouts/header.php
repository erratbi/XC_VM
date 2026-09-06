<?php

use XcVm\Core\Auth\Authorization;

if (!function_exists('xtreampi_t')) {
	function xtreampi_t(string $key, string $fallback): string {
		global $language;
		static $englishKeys = null;
		if ($englishKeys === null) {
			$englishKeys = parse_ini_file(MAIN_HOME . 'resources/langs/en.ini', false, INI_SCANNER_RAW) ?: [];
		}
		if (!array_key_exists($key, $englishKeys)) {
			return $fallback;
		}
		if (is_string($language) && method_exists($language, 'get')) {
			$value = $language::get($key);
			return $value === $key ? $fallback : $value;
		}
		return $fallback;
	}
}

$xtreampiPage = defined('PAGE_NAME') ? PAGE_NAME : 'dashboard';
$xtreampiUser = $rUserInfo['username'] ?? $rUserInfo['name'] ?? 'Administrator';
$xtreampiServerName = 'XtreamPi';
$xtreampiCanAny = static function (array $permissions): bool {
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
$xtreampiPrimaryNav = [
	[xtreampi_t('dashboard', 'Dashboard'), 'dashboard', 'fe-activity', [], ['dashboard', 'index'], null],
	[xtreampi_t('live_connections', 'Live Connections'), 'live_connections', 'fe-wifi', ['live_connections'], ['live_connections'], null],
	[xtreampi_t('lines', 'Streaming Lines'), '#', 'fe-menu', ['users'], ['lines', 'line', 'line_mass'], 'lines'],
	[xtreampi_t('users', 'Users'), '#', 'fe-users', ['mng_regusers', 'mass_edit_reguser', 'mng_packages', 'mng_groups'], ['users', 'user', 'user_mass', 'packages', 'package', 'groups', 'group'], 'users'],
	[(is_string($language ?? null) && method_exists($language, 'current') && $language::current() === 'fr') ? 'Appareils' : xtreampi_t('devices', 'Devices'), '#', 'fe-monitor', ['manage_mag', 'manage_e2', 'add_hmac'], ['mags', 'mag', 'enigmas', 'enigma', 'hmacs', 'hmac'], 'devices'],
	[xtreampi_t('content', 'Content'), '#', 'fe-play', ['streams', 'movies', 'series', 'radio', 'categories', 'bouquets', 'epg'], ['streams', 'stream', 'movies', 'movie', 'series', 'serie', 'series_mass', 'episodes', 'episode', 'episodes_mass', 'radios', 'radio', 'stream_categories', 'stream_category', 'bouquets', 'bouquet', 'bouquet_order', 'bouquet_sort', 'epgs', 'epg'], 'content'],
	[xtreampi_t('servers', 'Servers'), '#', 'fe-server', ['servers', 'add_server', 'edit_server', 'server_order', 'process_monitor'], ['servers', 'server', 'server_view', 'server_install', 'server_order', 'proxies', 'proxy', 'process_monitor'], 'servers'],
	[xtreampi_t('logs', 'Logs'), '#', 'fe-clock', ['panel_logs', 'client_request_log', 'login_logs'], ['panel_logs', 'client_logs', 'login_logs', 'user_logs', 'stream_errors'], 'logs'],
	[xtreampi_t('system', 'System'), '#', 'fe-settings', ['settings'], ['settings', 'modules', 'backups', 'cache', 'rtmp_monitor', 'stream_rank', 'theft_detection'], 'system'],
	[xtreampi_t('tickets', 'Tickets Support'), 'tickets', 'fe-help-circle', ['tickets'], ['tickets', 'ticket', 'ticket_view'], null],
];
$xtreampiDrillNav = [
	'lines' => [xtreampi_t('lines', 'Streaming Lines'), [
		['', [[xtreampi_t('add_line', 'Create New Line'), 'line', ['add_user']], [xtreampi_t('manage_lines', 'Manage Lines'), 'lines', ['users']], [xtreampi_t('mass_edit_users', 'Mass Edit Lines'), 'line_mass', ['mass_edit_users']]]],
	]],
	'users' => [xtreampi_t('users', 'Users'), [
		['', [[xtreampi_t('users', 'Registered Users'), 'users', ['mng_regusers']], [xtreampi_t('mass_edit_users', 'Mass Edit Users'), 'user_mass', ['mass_edit_reguser']], [xtreampi_t('packages', 'Packages'), 'packages', ['mng_packages']], [xtreampi_t('groups', 'Groups'), 'groups', ['mng_groups']]]],
	]],
	'devices' => [(is_string($language ?? null) && method_exists($language, 'current') && $language::current() === 'fr') ? 'Appareils' : xtreampi_t('devices', 'Devices'), [
		['MAG Devices', [['Add MAG Device', 'mag', ['add_mag']], ['Manage MAG Devices', 'mags', ['manage_mag']]]],
		['Enigma2 Devices', [['Add Enigma2 Device', 'enigma', ['add_e2']], ['Manage Enigma2 Devices', 'enigmas', ['manage_e2']]]],
		['HMAC Devices', [['Manage HMAC Devices', 'hmacs', ['add_hmac']]]],
	]],
	'content' => [xtreampi_t('content', 'Content'), [
		['', [[xtreampi_t('streams', 'Live Streams'), 'streams', ['streams']], [xtreampi_t('movies', 'VOD Movies'), 'movies', ['movies']], [xtreampi_t('series', 'TV Series'), 'series', ['series']], [xtreampi_t('radio', 'Radio'), 'radios', ['radio']], [xtreampi_t('epg', 'EPG files'), 'epgs', ['epg']], [xtreampi_t('categories', 'Streaming Categories'), 'stream_categories', ['categories']], [xtreampi_t('bouquets', 'Bouquets'), 'bouquets', ['bouquets']]]],
	]],
	'servers' => [xtreampi_t('servers', 'Servers'), [
		['', [[(is_string($language ?? null) && method_exists($language, 'current') && $language::current() === 'fr') ? 'Installer le Loadbalancer' : xtreampi_t('install_load_balancer', 'Install Loadbalancer'), 'server_install', ['add_server']], [xtreampi_t('manage_servers', 'Manage Servers'), 'servers', ['servers']], [xtreampi_t('manage_proxies', 'Manage Proxies'), 'proxies', ['servers']], [xtreampi_t('server_order', 'Server Order'), 'server_order', ['server_order']], [xtreampi_t('process_monitor', 'Process Monitor'), 'process_monitor', ['process_monitor']]]],
	]],
	'logs' => [xtreampi_t('logs', 'Logs'), [['', [[xtreampi_t('panel_logs', 'Panel Logs'), 'panel_logs', ['panel_logs']], [xtreampi_t('client_logs', 'Client Logs'), 'client_logs', ['client_request_log']], [xtreampi_t('login_logs', 'Login Logs'), 'login_logs', ['login_logs']], [xtreampi_t('user_logs', 'User Logs'), 'user_logs', ['reg_userlog']]]]]],
	'system' => [xtreampi_t('system', 'System'), [
		['Infrastructure', [['RTMP Management', 'rtmp_monitor', ['rtmp_monitor']], ['Statistics', 'stream_rank', ['streams']]]],
		['Administration', [['Settings', 'settings', ['settings']], ['Cache / Redis', 'cache', ['cache']], ['Modules', 'modules', ['modules']], ['Backups', 'backups', ['backups']], ['Security plug-ins', 'theft_detection', ['theft_detection']]]],
	]],
];
$xtreampiActiveDrill = null;
foreach ($xtreampiPrimaryNav as $xtreampiNavItem) {
	if ($xtreampiNavItem[5] && in_array($xtreampiPage, $xtreampiNavItem[4], true)) {
		$xtreampiActiveDrill = $xtreampiNavItem[5];
		break;
	}
}
$xtreampiIsDashboard = in_array($xtreampiPage, ['dashboard', 'index'], true);
$xtreampiTranslationMap = [];
$xtreampiLanguageCode = is_string($language ?? null) && method_exists($language, 'current') ? $language::current() : 'en';
if ($xtreampiLanguageCode !== 'en') {
	$xtreampiEnglish = parse_ini_file(MAIN_HOME . 'resources/langs/en.ini', false, INI_SCANNER_RAW) ?: [];
	$xtreampiTranslated = parse_ini_file(MAIN_HOME . 'resources/langs/' . $xtreampiLanguageCode . '.ini', false, INI_SCANNER_RAW) ?: [];
	foreach ($xtreampiEnglish as $xtreampiKey => $xtreampiEnglishText) {
		$xtreampiTranslatedText = $xtreampiTranslated[$xtreampiKey] ?? $xtreampiEnglishText;
		if ($xtreampiTranslatedText !== $xtreampiEnglishText && strip_tags($xtreampiEnglishText) === $xtreampiEnglishText && strip_tags($xtreampiTranslatedText) === $xtreampiTranslatedText) {
			$xtreampiTranslationMap[$xtreampiEnglishText] = $xtreampiTranslatedText;
		}
	}
}
$xtreampiCustomTranslations = [
	'fr' => [
		'Account' => 'Compte', 'Profile details' => 'Détails du profil', 'General settings' => 'Paramètres généraux', 'Leave blank to keep your current password' => 'Laissez vide pour conserver votre mot de passe actuel', 'Server default' => 'Valeur par défaut du serveur',
		'Legacy panel appearance' => 'Apparence du panneau classique', 'These options apply if you use the classic admin interface.' => 'Ces options s’appliquent si vous utilisez l’interface d’administration classique.', 'System Theme' => 'Thème du système', 'Topbar Theme' => 'Thème de la barre supérieure', 'Topbar color' => 'Couleur de la barre supérieure',
		'API access' => 'Accès API', 'Use this key to authenticate against the admin API.' => 'Utilisez cette clé pour vous authentifier auprès de l’API d’administration.', 'Generate' => 'Générer',
		'Streaming Lines' => 'Lignes de diffusion', 'Create New Line' => 'Créer une ligne', 'Mass Edit Lines' => 'Modification en masse des lignes', 'Registered Users' => 'Utilisateurs enregistrés',
		'Live Streams' => 'Flux en direct', 'VOD Movies' => 'Films VOD', 'TV Series' => 'Séries TV', 'EPG files' => 'Fichiers EPG', 'Streaming Categories' => 'Catégories de diffusion',
		'Tickets Support' => 'Assistance', 'Use legacy UI' => 'Utiliser l’interface classique', 'Navigation' => 'Navigation', 'Back' => 'Retour', 'Infrastructure' => 'Infrastructure', 'Administration' => 'Administration',
		'Drag servers into priority order. Offline servers are still automatically moved to the end when clients are allocated.' => 'Faites glisser les serveurs dans l’ordre de priorité. Les serveurs hors ligne sont toujours déplacés automatiquement à la fin lors de l’attribution des clients.',
		'Connection allocation priority' => 'Priorité d’attribution des connexions', 'Search username, IP, email, notes, or dates' => 'Rechercher un nom d’utilisateur, une IP, un e-mail, des notes ou des dates',
		'Edit' => 'Modifier', 'Enable' => 'Activer', 'Disable' => 'Désactiver', 'Delete' => 'Supprimer', 'Enabled' => 'Activé', 'Disabled' => 'Désactivé', 'Online' => 'En ligne', 'Offline' => 'Hors ligne',
		'Loading…' => 'Chargement…', 'Loading registered users…' => 'Chargement des utilisateurs enregistrés…', 'Previous' => 'Précédent', 'Next' => 'Suivant', 'Cancel' => 'Annuler', 'Save order' => 'Enregistrer l’ordre',
		'Subscriber access' => 'Accès abonné', 'Add Subscription' => 'Ajouter un abonnement', 'Edit Subscription' => 'Modifier l’abonnement', 'Back to subscriptions' => 'Retour aux abonnements',
		'Account Details' => 'Détails du compte', 'Generated automatically if blank' => 'Généré automatiquement si vide', 'Never expires' => 'N’expire jamais', 'Maximum Connections' => 'Connexions maximales',
		'Admin Notes' => 'Notes administrateur', 'Reseller Notes' => 'Notes revendeur', 'Connection Options' => 'Options de connexion', 'Trial account' => 'Compte d’essai',
		'Lock to ISP' => 'Verrouiller sur le FAI', 'Bypass user-agent restrictions' => 'Contourner les restrictions d’agent utilisateur', 'Forced Connection' => 'Connexion forcée',
		'Forced Country' => 'Pays forcé', 'Off' => 'Désactivé', 'Current ISP' => 'FAI actuel', 'Access Token' => 'Jeton d’accès', 'Output Formats' => 'Formats de sortie',
		'Save subscription' => 'Enregistrer l’abonnement', 'Generate access token' => 'Générer le jeton d’accès', 'Clear access token' => 'Effacer le jeton d’accès',
		'Add' => 'Ajouter', 'Add subscription' => 'Ajouter un abonnement', 'Add Subscription' => 'Ajouter un abonnement', 'Add user' => 'Ajouter un utilisateur', 'Add User' => 'Ajouter un utilisateur',
		'Add stream' => 'Ajouter un flux', 'Add Stream' => 'Ajouter un flux', 'Add movie' => 'Ajouter un film', 'Add Movie' => 'Ajouter un film', 'Add series' => 'Ajouter une série', 'Add Series' => 'Ajouter une série',
		'Add episode' => 'Ajouter un épisode', 'Add Episode' => 'Ajouter un épisode', 'Add station' => 'Ajouter une station', 'Add radio station' => 'Ajouter une station radio',
		'Add category' => 'Ajouter une catégorie', 'Add Category' => 'Ajouter une catégorie', 'Add bouquet' => 'Ajouter un bouquet', 'Add Bouquet' => 'Ajouter un bouquet',
		'Add package' => 'Ajouter un forfait', 'Add Package' => 'Ajouter un forfait', 'Add group' => 'Ajouter un groupe', 'Add Group' => 'Ajouter un groupe',
		'Add device' => 'Ajouter un appareil', 'Add Device' => 'Ajouter un appareil', 'Add MAG Device' => 'Ajouter un appareil MAG', 'Add Enigma2 Device' => 'Ajouter un appareil Enigma2',
		'Add HMAC key' => 'Ajouter une clé HMAC', 'Add HMAC Key' => 'Ajouter une clé HMAC', 'Add EPG' => 'Ajouter un EPG', 'Add source' => 'Ajouter une source', 'Add destination' => 'Ajouter une destination',
		'No packages have been created yet.' => 'Aucun forfait n’a encore été créé.', 'No packages match these filters.' => 'Aucun forfait ne correspond à ces filtres.',
		'No subscriptions match these filters.' => 'Aucun abonnement ne correspond à ces filtres.', 'No registered users match these filters.' => 'Aucun utilisateur enregistré ne correspond à ces filtres.',
		'No live streams match these filters.' => 'Aucun flux en direct ne correspond à ces filtres.', 'No movies match these filters.' => 'Aucun film ne correspond à ces filtres.',
		'No series match these filters.' => 'Aucune série ne correspond à ces filtres.', 'No episodes match these filters.' => 'Aucun épisode ne correspond à ces filtres.',
		'No radio stations match these filters.' => 'Aucune station radio ne correspond à ces filtres.', 'No active connections match these filters.' => 'Aucune connexion active ne correspond à ces filtres.',
		'No proxies have been installed yet.' => 'Aucun proxy n’a encore été installé.', 'No proxies match these filters.' => 'Aucun proxy ne correspond à ces filtres.',
		'No servers match these filters.' => 'Aucun serveur ne correspond à ces filtres.', 'No MAG devices match these filters.' => 'Aucun appareil MAG ne correspond à ces filtres.',
		'No Enigma2 devices match these filters.' => 'Aucun appareil Enigma2 ne correspond à ces filtres.', 'No HMAC keys have been created yet.' => 'Aucune clé HMAC n’a encore été créée.',
		'No HMAC keys match these filters.' => 'Aucune clé HMAC ne correspond à ces filtres.', 'No groups have been created yet.' => 'Aucun groupe n’a encore été créé.',
		'No groups match these filters.' => 'Aucun groupe ne correspond à ces filtres.', 'No bouquets have been created yet.' => 'Aucun bouquet n’a encore été créé.',
		'No bouquets match this search.' => 'Aucun bouquet ne correspond à cette recherche.', 'No categories match your search.' => 'Aucune catégorie ne correspond à votre recherche.',
		'No EPG files have been added yet.' => 'Aucun fichier EPG n’a encore été ajouté.', 'No EPG files match this search.' => 'Aucun fichier EPG ne correspond à cette recherche.',
		'No support tickets have been created yet.' => 'Aucun ticket d’assistance n’a encore été créé.', 'No tickets match these filters.' => 'Aucun ticket ne correspond à ces filtres.',
		'No panel logs recorded yet.' => 'Aucun journal du panneau n’a encore été enregistré.', 'No panel logs match this search.' => 'Aucun journal du panneau ne correspond à cette recherche.',
		'No channels have been indexed yet.' => 'Aucune chaîne n’a encore été indexée.', 'No modules are installed.' => 'Aucun module n’est installé.',
		'No folders found.' => 'Aucun dossier trouvé.', 'No compatible files found.' => 'Aucun fichier compatible trouvé.', 'No permissions match this filter.' => 'Aucune autorisation ne correspond à ce filtre.',
		'No admin notes.' => 'Aucune note administrateur.', 'No reseller notes.' => 'Aucune note revendeur.', 'No additional domains or IPs.' => 'Aucun domaine ou IP supplémentaire.',
		'No HTTP ports configured.' => 'Aucun port HTTP configuré.', 'No HTTPS ports configured.' => 'Aucun port HTTPS configuré.',
		'Account management' => 'Gestion des comptes', 'Subscriber Access' => 'Accès abonné', 'Content management' => 'Gestion du contenu', 'Content Setup' => 'Configuration du contenu', 'Devices' => 'Appareils',
		'Access Control' => 'Contrôle d’accès', 'Device Management' => 'Gestion des appareils', 'Content metadata' => 'Métadonnées du contenu', 'Series management' => 'Gestion des séries',
		'All statuses' => 'Tous les statuts', 'All owners' => 'Tous les propriétaires', 'Per page' => 'Par page', 'Status / group' => 'Statut / groupe',
		'User' => 'Utilisateur', 'Owner' => 'Propriétaire', 'Group' => 'Groupe', 'Status' => 'Statut', 'Credits' => 'Crédits', 'Lines' => 'Lignes', 'Last login' => 'Dernière connexion',
		'Online' => 'En ligne', 'Trial' => 'Essai', 'Active' => 'Actif', 'Connections' => 'Connexions', 'Expiration' => 'Expiration', 'Last Connection' => 'Dernière connexion',
		'Search username, notes, limits, or expiration' => 'Rechercher un nom d’utilisateur, des notes, des limites ou une expiration', 'Mass edit' => 'Modification en masse',
		'Actions' => 'Actions', 'Remove' => 'Supprimer', 'Clear' => 'Effacer', 'Browse' => 'Parcourir', 'Open' => 'Ouvrir', 'Close' => 'Fermer', 'Never' => 'Jamais',
		'Forced Server' => 'Serveur forcé', 'Allowed IP Addresses' => 'Adresses IP autorisées', 'Allowed User Agents' => 'Agents utilisateurs autorisés', 'Any IP address is allowed.' => 'Toute adresse IP est autorisée.', 'Any user agent is allowed.' => 'Tout agent utilisateur est autorisé.',
		'Connection Options' => 'Options de connexion', 'Output Formats' => 'Formats de sortie', 'Save Subscription' => 'Enregistrer l’abonnement', 'Edit Subscription' => 'Modifier l’abonnement',
		'Install Load Balancer' => 'Installer le Loadbalancer', 'Install Loadbalancer' => 'Installer le Loadbalancer',
		'The subscription could not be saved. Please try again.' => 'L’abonnement n’a pas pu être enregistré. Veuillez réessayer.', 'The subscriptions could not be updated.' => 'Les abonnements n’ont pas pu être mis à jour.',
		'The user could not be saved. Please try again.' => 'L’utilisateur n’a pas pu être enregistré. Veuillez réessayer.', 'The selected users could not be updated.' => 'Les utilisateurs sélectionnés n’ont pas pu être mis à jour.',
		'The server could not be saved. Please try again.' => 'Le serveur n’a pas pu être enregistré. Veuillez réessayer.', 'The server order could not be saved.' => 'L’ordre des serveurs n’a pas pu être enregistré.',
		'Provisioning could not be started. Please try again.' => 'Le provisionnement n’a pas pu être démarré. Veuillez réessayer.', 'The series could not be saved.' => 'La série n’a pas pu être enregistrée.',
		'The series could not be deleted.' => 'La série n’a pas pu être supprimée.', 'The movie action could not be completed.' => 'L’action sur le film n’a pas pu être effectuée.',
		'The radio action could not be completed.' => 'L’action sur la station radio n’a pas pu être effectuée.', 'The episode action could not be completed.' => 'L’action sur l’épisode n’a pas pu être effectuée.',
		'The category could not be saved.' => 'La catégorie n’a pas pu être enregistrée.', 'The category could not be deleted.' => 'La catégorie n’a pas pu être supprimée.',
		'The bouquet could not be saved.' => 'Le bouquet n’a pas pu être enregistré.', 'The bouquet could not be deleted.' => 'Le bouquet n’a pas pu être supprimé.',
		'The bouquet order could not be saved.' => 'L’ordre des bouquets n’a pas pu être enregistré.', 'The bouquet content order could not be saved.' => 'L’ordre du contenu du bouquet n’a pas pu être enregistré.',
		'The package could not be saved.' => 'Le forfait n’a pas pu être enregistré.', 'The group could not be saved.' => 'Le groupe n’a pas pu être enregistré.',
		'The EPG could not be saved.' => 'L’EPG n’a pas pu être enregistré.', 'The EPG action could not be completed.' => 'L’action EPG n’a pas pu être effectuée.',
		'The MAG device could not be saved.' => 'L’appareil MAG n’a pas pu être enregistré.', 'The HMAC key could not be saved.' => 'La clé HMAC n’a pas pu être enregistrée.',
		'Movies could not be loaded.' => 'Les films n’ont pas pu être chargés.', 'Series could not be loaded.' => 'Les séries n’ont pas pu être chargées.',
		'Subscriptions could not be loaded.' => 'Les abonnements n’ont pas pu être chargés.', 'Registered users could not be loaded.' => 'Les utilisateurs enregistrés n’ont pas pu être chargés.',
		'Live streams could not be loaded.' => 'Les flux en direct n’ont pas pu être chargés.', 'Live connections could not be loaded.' => 'Les connexions en direct n’ont pas pu être chargées.',
	],
];
foreach ($xtreampiCustomTranslations[$xtreampiLanguageCode] ?? [] as $xtreampiEnglishText => $xtreampiTranslatedText) {
	$xtreampiTranslationMap[$xtreampiEnglishText] = $xtreampiTranslatedText;
}
?>
<!doctype html>
<html lang="<?php echo htmlspecialchars(is_string($language ?? null) && method_exists($language, 'current') ? $language::current() : 'en', ENT_QUOTES, 'UTF-8'); ?>">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex,nofollow">
	<meta name="theme-color" content="#d62f49">
	<meta name="application-name" content="XtreamPi">
	<meta name="apple-mobile-web-app-title" content="XtreamPi">
	<title><?php echo htmlspecialchars($xtreampiServerName, ENT_QUOTES, 'UTF-8'); ?><?php echo isset($_TITLE) ? ' | ' . htmlspecialchars((string) $_TITLE, ENT_QUOTES, 'UTF-8') : ''; ?></title>
	<link rel="icon" href="assets/xtreampi/brand/logo-on-light.svg" type="image/svg+xml" sizes="any" media="(prefers-color-scheme: light)">
	<link rel="icon" href="assets/xtreampi/brand/logo.svg" type="image/svg+xml" sizes="any" media="(prefers-color-scheme: dark)">
	<link rel="icon" href="assets/xtreampi/brand/favicon-32.png" type="image/png" sizes="32x32" media="(prefers-color-scheme: light)">
	<link rel="apple-touch-icon" href="assets/xtreampi/brand/apple-touch-icon.png" sizes="180x180">
	<link rel="manifest" href="assets/xtreampi/manifest.json">
	<link rel="stylesheet" href="assets/css/icons.css">
	<link rel="stylesheet" href="assets/xtreampi/xtreampi.css">
</head>
<body class="xtreampi-ui <?php echo $xtreampiIsDashboard ? 'sc-page-dashboard' : 'sc-page-inner'; ?><?php echo $xtreampiActiveDrill ? ' sc-has-context-nav' : ''; ?>">
	<div class="sc-mobile-scrim" data-sc-sidebar-close></div>
	<div class="sc-app-shell">
		<aside class="sc-sidebar<?php echo $xtreampiActiveDrill ? ' is-drilling' : ''; ?>" id="xtreampi-sidebar" aria-label="Admin navigation">
			<a class="sc-brand" href="dashboard" aria-label="XtreamPi">
				<span class="sc-brand-mark" aria-hidden="true"><img src="assets/xtreampi/brand/logo-on-light.svg" width="25" height="25" alt=""></span>
				<span class="sc-brand-copy"><strong>XtreamPi</strong></span>
			</a>

			<nav class="sc-nav" data-sc-primary-nav>
				<?php foreach ($xtreampiPrimaryNav as [$label, $url, $icon, $permissions, $activePages, $drill]): ?>
					<?php if (!$xtreampiCanAny($permissions)) continue; ?>
					<?php $xtreampiActive = in_array($xtreampiPage, $activePages, true); ?>
					<?php if ($drill): ?>
						<button class="sc-nav-link" type="button" data-sc-nav-open="<?php echo $drill; ?>">
					<?php else: ?><a class="sc-nav-link<?php echo $xtreampiActive ? ' is-active' : ''; ?>" href="<?php echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>"><?php endif; ?>
						<i class="<?php echo htmlspecialchars($icon, ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i>
						<span><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></span>
					<?php echo $drill ? '</button>' : '</a>'; ?>
				<?php endforeach; ?>
			</nav>
			<?php foreach ($xtreampiDrillNav as $xtreampiKey => [$xtreampiTitle, $xtreampiSections]): $xtreampiPanelActive = $xtreampiKey === $xtreampiActiveDrill; ?>
				<aside class="sc-nav-drill<?php echo $xtreampiPanelActive ? ' is-open' : ''; ?>" data-sc-nav-panel="<?php echo $xtreampiKey; ?>" aria-label="<?php echo htmlspecialchars($xtreampiTitle, ENT_QUOTES, 'UTF-8'); ?> navigation"<?php echo $xtreampiPanelActive ? '' : ' hidden'; ?>><header><span>Navigation</span><button type="button" data-sc-nav-close><i class="fe-chevron-left"></i> Back</button></header><?php foreach ($xtreampiSections as [$sectionLabel, $sectionItems]): $xtreampiSectionItems=array_filter($sectionItems, static fn(array $item): bool => $xtreampiCanAny($item[2])); if(!$xtreampiSectionItems) continue; ?><section><?php if ($sectionLabel !== ''): ?><h3><?php echo htmlspecialchars($sectionLabel, ENT_QUOTES, 'UTF-8'); ?></h3><?php endif; ?><?php foreach($xtreampiSectionItems as [$itemLabel,$itemUrl]): $xtreampiItemPage = explode('?', $itemUrl, 2)[0]; ?><a<?php echo $xtreampiItemPage === $xtreampiPage ? ' class="is-active"' : ''; ?> href="<?php echo htmlspecialchars($itemUrl, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($itemLabel, ENT_QUOTES, 'UTF-8'); ?></a><?php endforeach; ?></section><?php endforeach; ?></aside>
			<?php endforeach; ?>
		</aside>

		<div class="sc-workspace">
			<header class="sc-topbar">
				<button class="sc-icon-button sc-menu-toggle" type="button" data-sc-sidebar-toggle aria-controls="xtreampi-sidebar" aria-expanded="false" aria-label="Open navigation">
					<i class="fe-menu" aria-hidden="true"></i>
				</button>
				<div class="sc-topbar-spacer"></div>
				<span class="sc-version">v<?php echo htmlspecialchars((string) XC_VM_VERSION, ENT_QUOTES, 'UTF-8'); ?></span>
				<details class="sc-account">
					<summary aria-label="Open account menu">
						<span class="sc-avatar"><i class="fe-user" aria-hidden="true"></i></span>
						<span class="sc-account-name"><?php echo htmlspecialchars((string) $xtreampiUser, ENT_QUOTES, 'UTF-8'); ?></span>
						<i class="fe-chevron-down" aria-hidden="true"></i>
					</summary>
					<div class="sc-account-menu">
						<a href="edit_profile"><i class="fe-user" aria-hidden="true"></i> <?php echo htmlspecialchars(xtreampi_t('edit_profile', 'Profile'), ENT_QUOTES, 'UTF-8'); ?></a>
						<a href="settings"><i class="fe-settings" aria-hidden="true"></i> <?php echo htmlspecialchars(xtreampi_t('general_settings', 'General settings'), ENT_QUOTES, 'UTF-8'); ?></a>
						<a href="dashboard?admin_ui=legacy"><i class="fe-corner-up-left" aria-hidden="true"></i> <?php echo htmlspecialchars(xtreampi_t('legacy_ui', 'Use legacy UI'), ENT_QUOTES, 'UTF-8'); ?></a>
						<a href="logout"><i class="fe-log-out" aria-hidden="true"></i> <?php echo htmlspecialchars(xtreampi_t('logout', 'Log out'), ENT_QUOTES, 'UTF-8'); ?></a>
					</div>
				</details>
			</header>

			<div class="sc-content-layout<?php echo $xtreampiActiveDrill ? ' has-context-nav' : ''; ?>">
				<?php if ($xtreampiActiveDrill): [$xtreampiContextTitle, $xtreampiContextSections] = $xtreampiDrillNav[$xtreampiActiveDrill]; ?>
					<nav class="sc-context-nav" aria-label="<?php echo htmlspecialchars($xtreampiContextTitle, ENT_QUOTES, 'UTF-8'); ?> navigation">
						<?php foreach ($xtreampiContextSections as [$sectionLabel, $sectionItems]): $xtreampiContextItems = array_filter($sectionItems, static fn(array $item): bool => $xtreampiCanAny($item[2])); if (!$xtreampiContextItems) continue; ?>
							<section><?php if ($sectionLabel !== ''): ?><h2><?php echo htmlspecialchars($sectionLabel, ENT_QUOTES, 'UTF-8'); ?></h2><?php endif; ?><?php foreach ($xtreampiContextItems as [$itemLabel, $itemUrl]): $xtreampiContextPage = explode('?', $itemUrl, 2)[0]; ?><a<?php echo $xtreampiContextPage === $xtreampiPage ? ' class="is-active"' : ''; ?> href="<?php echo htmlspecialchars($itemUrl, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($itemLabel, ENT_QUOTES, 'UTF-8'); ?></a><?php endforeach; ?></section>
						<?php endforeach; ?>
					</nav>
				<?php endif; ?>
				<main class="sc-main" id="main-content">
