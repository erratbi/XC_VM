<?php

use XcVm\Core\Auth\AuthRepository;
use XcVm\Core\Ui\AdminUiTheme;
use XcVm\Core\Util\AdminHelpers;
use XcVm\Domain\Server\ServerRepository;

$xtreampiPageScripts = ['assets/xtreampi/edit-profile.js'];
$xtreampiUser = is_array($rUserInfo ?? null) ? $rUserInfo : [];
$xtreampiTimezones = AdminHelpers::TimeZoneList();
$xtreampiThemes = is_array($rThemes ?? null) ? $rThemes : [];
$xtreampiHues = is_array($rHues ?? null) ? $rHues : [];
$xtreampiLanguages = is_array($allowedLangs ?? null) ? $allowedLangs : [];
$xtreampiAdminUi = AdminUiTheme::resolve($_GET, $_COOKIE);
$xtreampiCanUseApi = false;
foreach (AuthRepository::getAllCodes() as $xtreampiCode) {
    if ((int) ($xtreampiCode['type'] ?? 0) === 3 && in_array((int) ($xtreampiUser['member_group_id'] ?? 0), json_decode((string) ($xtreampiCode['groups'] ?? '[]'), true) ?: [], true)) { $xtreampiCanUseApi = true; break; }
}
$xtreampiApiBase = (string) ((ServerRepository::getAll()[SERVER_ID]['site_url'] ?? '') ?: '');
?>
<section class="sc-profile-editor" data-sc-profile-editor>
    <div class="sc-page-heading"><div><p class="sc-eyebrow"><?php echo htmlspecialchars(xtreampi_t('edit_profile', 'Account'), ENT_QUOTES, 'UTF-8'); ?></p><h1><?php echo htmlspecialchars((string) ($xtreampiUser['username'] ?? xtreampi_t('edit_profile', 'Profile')), ENT_QUOTES, 'UTF-8'); ?></h1></div></div>
    <form class="sc-form" action="post.php?action=edit_profile&amp;referer=edit_profile" method="post">
        <section class="sc-form-section"><h2><?php echo htmlspecialchars(xtreampi_t('details', 'Profile details'), ENT_QUOTES, 'UTF-8'); ?></h2><div class="sc-form-grid">
            <label><?php echo htmlspecialchars(xtreampi_t('change_password', 'Change password'), ENT_QUOTES, 'UTF-8'); ?><input name="password" type="password" autocomplete="new-password" placeholder="Leave blank to keep your current password"></label>
            <label><?php echo htmlspecialchars(xtreampi_t('email_address', 'Email address'), ENT_QUOTES, 'UTF-8'); ?><input name="email" type="email" value="<?php echo htmlspecialchars((string) ($xtreampiUser['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></label>
            <label><?php echo htmlspecialchars(xtreampi_t('timezone', 'Timezone'), ENT_QUOTES, 'UTF-8'); ?><select name="timezone"><option value=""<?php echo empty($xtreampiUser['timezone']) ? ' selected' : ''; ?>>Server default</option><?php foreach ($xtreampiTimezones as $xtreampiTimezone): ?><option value="<?php echo htmlspecialchars((string) $xtreampiTimezone['zone'], ENT_QUOTES, 'UTF-8'); ?>"<?php echo ($xtreampiUser['timezone'] ?? '') === $xtreampiTimezone['zone'] ? ' selected' : ''; ?>><?php echo htmlspecialchars((string) ($xtreampiTimezone['zone'] . ' ' . $xtreampiTimezone['diff_from_GMT']), ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></label>
            <label><?php echo htmlspecialchars(xtreampi_t('language', 'Language'), ENT_QUOTES, 'UTF-8'); ?><select name="lang"><?php foreach ($xtreampiLanguages as $xtreampiLanguage): ?><option value="<?php echo htmlspecialchars((string) $xtreampiLanguage, ENT_QUOTES, 'UTF-8'); ?>"<?php echo ($xtreampiUser['lang'] ?? '') === $xtreampiLanguage ? ' selected' : ''; ?>><?php echo htmlspecialchars((string) $xtreampiLanguage, ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></label>
        </div></section>
        <section class="sc-form-section"><h2>Admin interface</h2><p class="sc-section-copy">Choose the interface used for future admin pages.</p><div class="sc-form-grid"><label>Interface<select name="admin_ui" data-sc-admin-ui><option value="<?php echo AdminUiTheme::XTREAMPI; ?>"<?php echo $xtreampiAdminUi === AdminUiTheme::XTREAMPI ? ' selected' : ''; ?>>XtreamPi</option><option value="<?php echo AdminUiTheme::LEGACY; ?>"<?php echo $xtreampiAdminUi === AdminUiTheme::LEGACY ? ' selected' : ''; ?>>Legacy</option></select></label></div></section>
        <section class="sc-form-section" data-sc-legacy-appearance><h2>Legacy panel appearance</h2><p class="sc-section-copy">These options apply if you use the classic admin interface.</p><p class="sc-section-copy sc-legacy-appearance-note" data-sc-legacy-appearance-note hidden>These settings are unavailable while XtreamPi is selected. Your current legacy appearance is kept for when you switch back.</p><div class="sc-form-grid">
            <label><?php echo htmlspecialchars(xtreampi_t('system_theme', 'System theme'), ENT_QUOTES, 'UTF-8'); ?><input type="hidden" name="theme" value="<?php echo htmlspecialchars((string) ($xtreampiUser['theme'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-sc-legacy-value="theme"><select name="theme" data-sc-legacy-appearance-control><?php foreach ($xtreampiThemes as $xtreampiTheme => $xtreampiThemeData): ?><option value="<?php echo htmlspecialchars((string) $xtreampiTheme, ENT_QUOTES, 'UTF-8'); ?>"<?php echo ($xtreampiUser['theme'] ?? '') === $xtreampiTheme ? ' selected' : ''; ?>><?php echo htmlspecialchars((string) ($xtreampiThemeData['name'] ?? $xtreampiTheme), ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></label>
            <label><?php echo htmlspecialchars(xtreampi_t('topbar_theme', 'Topbar color'), ENT_QUOTES, 'UTF-8'); ?><input type="hidden" name="hue" value="<?php echo htmlspecialchars((string) ($xtreampiUser['hue'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-sc-legacy-value="hue"><select name="hue" data-sc-legacy-appearance-control><?php foreach ($xtreampiHues as $xtreampiHue => $xtreampiHueName): ?><option value="<?php echo htmlspecialchars((string) $xtreampiHue, ENT_QUOTES, 'UTF-8'); ?>"<?php echo ($xtreampiUser['hue'] ?? '') === $xtreampiHue ? ' selected' : ''; ?>><?php echo htmlspecialchars((string) $xtreampiHueName, ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></label>
        </div></section>
        <?php if ($xtreampiCanUseApi): ?><section class="sc-form-section"><h2>API access</h2><div class="sc-form-grid"><label class="sc-form-span">API key<small><?php echo $xtreampiApiBase !== '' ? 'Endpoint: ' . htmlspecialchars($xtreampiApiBase, ENT_QUOTES, 'UTF-8') : 'Use this key to authenticate against the admin API.'; ?></small><div class="sc-inline-input"><input readonly name="api_key" value="<?php echo htmlspecialchars((string) ($xtreampiUser['api_key'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"><button class="sc-button sc-button-secondary" type="button" data-sc-api-generate>Generate</button><button class="sc-button sc-button-secondary" type="button" data-sc-api-clear>Clear</button></div></label></div></section><?php endif; ?>
        <div class="sc-form-error" data-sc-profile-error role="alert" hidden></div><div class="sc-form-actions"><button class="sc-button sc-button-primary" type="submit"><?php echo htmlspecialchars(xtreampi_t('save_profile', 'Save profile'), ENT_QUOTES, 'UTF-8'); ?></button></div>
    </form>
</section>
