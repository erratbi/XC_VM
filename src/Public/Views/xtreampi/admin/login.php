<?php

use XcVm\Core\Http\RequestManager;

$currentLanguage = $language::current();
$loginTranslations = require __DIR__ . '/login_i18n.php';
$loginCopy = $loginTranslations[$currentLanguage] ?? $loginTranslations['en'];
$copy = static fn(string $key): string => htmlspecialchars($loginCopy[$key] ?? $key);
$statusMessages = [
	STATUS_FAILURE => 'login_message_1',
	STATUS_INVALID_CODE => 'login_message_2',
	STATUS_NOT_ADMIN => 'login_message_3',
	STATUS_DISABLED => 'login_message_4',
	STATUS_INVALID_CAPTCHA => 'login_message_5',
];
$statusMessage = isset($_STATUS, $statusMessages[$_STATUS])
	? $language::get($statusMessages[$_STATUS])
	: null;
$recaptchaEnabled = (bool) ($rSettings['recaptcha_enable'] ?? false);
?>
<!doctype html>
<html lang="<?= htmlspecialchars($currentLanguage) ?>">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="theme-color" content="#d62f49">
	<meta name="application-name" content="XtreamPi">
	<meta name="apple-mobile-web-app-title" content="XtreamPi">
	<title data-id="login">XtreamPi | <?= $language::get('login') ?></title>
	<link rel="icon" href="assets/xtreampi/brand/logo-on-light.svg" type="image/svg+xml" sizes="any">
	<link rel="icon" href="assets/xtreampi/brand/favicon-32.png" type="image/png" sizes="32x32">
	<link rel="apple-touch-icon" href="assets/xtreampi/brand/apple-touch-icon.png" sizes="180x180">
	<link rel="manifest" href="assets/xtreampi/manifest.json">
	<link href="assets/css/icons.css" rel="stylesheet">
	<link href="assets/css/bootstrap.css" rel="stylesheet">
	<link href="assets/xtreampi/login.css" rel="stylesheet">
</head>

<body class="sc-auth">
	<div class="sc-auth-grid" aria-hidden="true"></div>
	<div class="sc-auth-orb sc-auth-orb-primary" aria-hidden="true"></div>
	<div class="sc-auth-orb sc-auth-orb-secondary" aria-hidden="true"></div>

	<header class="sc-auth-header">
		<a class="sc-auth-brand" href="./login?admin_ui=xtreampi"
			aria-label="XtreamPi <?= htmlspecialchars($language::get('login')) ?>">
			<span class="sc-auth-brand-mark" aria-hidden="true"><img src="assets/xtreampi/brand/logo-on-light.svg"
					width="31" height="31" alt=""></span>
			<span class="sc-auth-brand-copy">
				<strong>XtreamPi</strong>
				<small><?= $copy('control_plane') ?></small>
			</span>
		</a>
	</header>

	<main class="sc-auth-stage">
		<section class="sc-auth-intro" aria-labelledby="sc-auth-heading">
			<p class="sc-auth-kicker"><?= $copy('broadcast_operations') ?></p>
			<h1 id="sc-auth-heading"><?= $copy('hero_lead') ?><br><em><?= $copy('hero_emphasis') ?></em></h1>
			<p class="sc-auth-summary"><?= $copy('hero_summary') ?></p>

			<div class="sc-auth-capabilities" aria-label="<?= $copy('platform_capabilities') ?>">
				<span><b>01</b> <?= $copy('live_operations') ?></span>
				<span><b>02</b> <?= $copy('subscriber_control') ?></span>
				<span><b>03</b> <?= $copy('infrastructure_health') ?></span>
			</div>
		</section>

		<section class="sc-auth-panel" aria-labelledby="sc-login-title">
			<div class="sc-auth-panel-body">
				<p class="sc-auth-step"><?= $copy('identity_check') ?></p>
				<h2 id="sc-login-title"><?= $copy('sign_in_continue') ?></h2>
				<p class="sc-auth-help"><?= $copy('credentials_help') ?></p>

				<?php if ($statusMessage !== null): ?>
					<div class="sc-auth-alert" role="alert">
						<span aria-hidden="true">!</span>
						<p><?= htmlspecialchars($statusMessage) ?></p>
					</div>
				<?php endif; ?>

				<form action="./login" method="POST" data-parsley-validate class="sc-auth-form">
					<input type="hidden" name="referrer"
						value="<?= htmlspecialchars(RequestManager::getAll()['referrer'] ?? '') ?>">

					<label class="sc-auth-field" for="username">
						<span><?= $language::get('username') ?></span>
						<span class="sc-auth-input-wrap">
							<i class="mdi mdi-account-outline" aria-hidden="true"></i>
							<input autocomplete="username" type="text" id="username" name="username" required
								data-parsley-trigger="change"
								placeholder="<?= $language::get('enter_your_username') ?>">
						</span>
					</label>

					<label class="sc-auth-field" for="password">
						<span><?= $language::get('password') ?></span>
						<span class="sc-auth-input-wrap">
							<i class="mdi mdi-lock-outline" aria-hidden="true"></i>
							<input autocomplete="current-password" type="password" id="password" name="password"
								required data-parsley-trigger="change"
								placeholder="<?= $language::get('enter_your_password') ?>">
						</span>
					</label>

					<?php if ($recaptchaEnabled): ?>
						<div class="sc-auth-captcha">
							<div class="g-recaptcha" data-callback="recaptchaCallback"
								data-expired-callback="recaptchaExpired" id="verification"
								data-sitekey="<?= htmlspecialchars($rSettings['recaptcha_v2_site_key'] ?? '') ?>"></div>
						</div>
					<?php endif; ?>

					<button class="sc-auth-submit" type="submit" id="login_button" name="login" <?= $recaptchaEnabled ? 'disabled' : '' ?>>
						<span><?= $language::get('login') ?></span>
						<i class="mdi mdi-arrow-right" aria-hidden="true"></i>
					</button>
				</form>
			</div>
		</section>
	</main>

	<footer class="sc-auth-footer">
		<span></span>
		<span>&copy; <?= date('Y') ?> XtreamPi</span>
		<span></span>
	</footer>

	<script src="assets/js/vendor.min.js"></script>
	<script src="assets/libs/parsleyjs/parsley.min.js"></script>
	<?php if ($recaptchaEnabled): ?>
		<script src="https://www.google.com/recaptcha/api.js" async defer></script>
		<script>
			function recaptchaCallback() {
				var button = document.getElementById('login_button');
				if (button) button.disabled = false;
			}
			function recaptchaExpired() {
				var button = document.getElementById('login_button');
				if (button) button.disabled = true;
			}
		</script>
	<?php endif; ?>
</body>

</html>