<?php

use XcVm\Core\Auth\AuthRepository;
use XcVm\Core\Util\AdminHelpers;
use XcVm\Domain\Server\ServerRepository;

$streamcreedPageScripts = ['assets/streamcreed/edit-profile.js'];
$streamcreedUser = is_array($rUserInfo ?? null) ? $rUserInfo : [];
$streamcreedTimezones = AdminHelpers::TimeZoneList();
$streamcreedThemes = is_array($rThemes ?? null) ? $rThemes : [];
$streamcreedHues = is_array($rHues ?? null) ? $rHues : [];
$streamcreedLanguages = is_array($allowedLangs ?? null) ? $allowedLangs : [];
$streamcreedCanUseApi = false;
foreach (AuthRepository::getAllCodes() as $streamcreedCode) {
    if ((int) ($streamcreedCode['type'] ?? 0) === 3 && in_array((int) ($streamcreedUser['member_group_id'] ?? 0), json_decode((string) ($streamcreedCode['groups'] ?? '[]'), true) ?: [], true)) { $streamcreedCanUseApi = true; break; }
}
$streamcreedApiBase = (string) ((ServerRepository::getAll()[SERVER_ID]['site_url'] ?? '') ?: '');
?>
<section class="sc-profile-editor" data-sc-profile-editor>
    <div class="sc-page-heading"><div><p class="sc-eyebrow"><?php echo htmlspecialchars(streamcreed_t('edit_profile', 'Account'), ENT_QUOTES, 'UTF-8'); ?></p><h1><?php echo htmlspecialchars((string) ($streamcreedUser['username'] ?? streamcreed_t('edit_profile', 'Profile')), ENT_QUOTES, 'UTF-8'); ?></h1></div></div>
    <form class="sc-form" action="post.php?action=edit_profile&amp;referer=edit_profile" method="post">
        <section class="sc-form-section"><h2><?php echo htmlspecialchars(streamcreed_t('details', 'Profile details'), ENT_QUOTES, 'UTF-8'); ?></h2><div class="sc-form-grid">
            <label><?php echo htmlspecialchars(streamcreed_t('change_password', 'Change password'), ENT_QUOTES, 'UTF-8'); ?><input name="password" type="password" autocomplete="new-password" placeholder="Leave blank to keep your current password"></label>
            <label><?php echo htmlspecialchars(streamcreed_t('email_address', 'Email address'), ENT_QUOTES, 'UTF-8'); ?><input name="email" type="email" value="<?php echo htmlspecialchars((string) ($streamcreedUser['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></label>
            <label><?php echo htmlspecialchars(streamcreed_t('timezone', 'Timezone'), ENT_QUOTES, 'UTF-8'); ?><select name="timezone"><option value=""<?php echo empty($streamcreedUser['timezone']) ? ' selected' : ''; ?>>Server default</option><?php foreach ($streamcreedTimezones as $streamcreedTimezone): ?><option value="<?php echo htmlspecialchars((string) $streamcreedTimezone['zone'], ENT_QUOTES, 'UTF-8'); ?>"<?php echo ($streamcreedUser['timezone'] ?? '') === $streamcreedTimezone['zone'] ? ' selected' : ''; ?>><?php echo htmlspecialchars((string) ($streamcreedTimezone['zone'] . ' ' . $streamcreedTimezone['diff_from_GMT']), ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></label>
            <label><?php echo htmlspecialchars(streamcreed_t('language', 'Language'), ENT_QUOTES, 'UTF-8'); ?><select name="lang"><?php foreach ($streamcreedLanguages as $streamcreedLanguage): ?><option value="<?php echo htmlspecialchars((string) $streamcreedLanguage, ENT_QUOTES, 'UTF-8'); ?>"<?php echo ($streamcreedUser['lang'] ?? '') === $streamcreedLanguage ? ' selected' : ''; ?>><?php echo htmlspecialchars((string) $streamcreedLanguage, ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></label>
        </div></section>
        <section class="sc-form-section"><h2>Legacy panel appearance</h2><p class="sc-section-copy">These options apply if you use the classic admin interface.</p><div class="sc-form-grid">
            <label><?php echo htmlspecialchars(streamcreed_t('system_theme', 'System theme'), ENT_QUOTES, 'UTF-8'); ?><select name="theme"><?php foreach ($streamcreedThemes as $streamcreedTheme => $streamcreedThemeData): ?><option value="<?php echo htmlspecialchars((string) $streamcreedTheme, ENT_QUOTES, 'UTF-8'); ?>"<?php echo ($streamcreedUser['theme'] ?? '') === $streamcreedTheme ? ' selected' : ''; ?>><?php echo htmlspecialchars((string) ($streamcreedThemeData['name'] ?? $streamcreedTheme), ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></label>
            <label><?php echo htmlspecialchars(streamcreed_t('topbar_theme', 'Topbar color'), ENT_QUOTES, 'UTF-8'); ?><select name="hue"><?php foreach ($streamcreedHues as $streamcreedHue => $streamcreedHueName): ?><option value="<?php echo htmlspecialchars((string) $streamcreedHue, ENT_QUOTES, 'UTF-8'); ?>"<?php echo ($streamcreedUser['hue'] ?? '') === $streamcreedHue ? ' selected' : ''; ?>><?php echo htmlspecialchars((string) $streamcreedHueName, ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></label>
        </div></section>
        <?php if ($streamcreedCanUseApi): ?><section class="sc-form-section"><h2>API access</h2><div class="sc-form-grid"><label class="sc-form-span">API key<small><?php echo $streamcreedApiBase !== '' ? 'Endpoint: ' . htmlspecialchars($streamcreedApiBase, ENT_QUOTES, 'UTF-8') : 'Use this key to authenticate against the admin API.'; ?></small><div class="sc-inline-input"><input readonly name="api_key" value="<?php echo htmlspecialchars((string) ($streamcreedUser['api_key'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"><button class="sc-button sc-button-secondary" type="button" data-sc-api-generate>Generate</button><button class="sc-button sc-button-secondary" type="button" data-sc-api-clear>Clear</button></div></label></div></section><?php endif; ?>
        <div class="sc-form-error" data-sc-profile-error role="alert" hidden></div><div class="sc-form-actions"><button class="sc-button sc-button-primary" type="submit"><?php echo htmlspecialchars(streamcreed_t('save_profile', 'Save profile'), ENT_QUOTES, 'UTF-8'); ?></button></div>
    </form>
</section>
