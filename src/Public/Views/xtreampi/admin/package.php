<?php
use XcVm\Domain\Bouquet\BouquetService;
use XcVm\Domain\Line\LineRepository;
use XcVm\Domain\Server\ServerRepository;
use XcVm\Domain\User\GroupService;

$xtreampiPageScripts = ['assets/xtreampi/package.js'];
$xtreampiPackage = is_array($rPackage ?? null) ? $rPackage : [];
$xtreampiEditing = intval($xtreampiPackage['id'] ?? 0) > 0;
$xtreampiSelectedGroups = array_map('intval', json_decode((string) ($xtreampiPackage['groups'] ?? '[]'), true) ?: []);
$xtreampiSelectedBouquets = array_map('intval', json_decode((string) ($xtreampiPackage['bouquets'] ?? '[]'), true) ?: []);
$xtreampiSelectedOutputs = array_map('intval', json_decode((string) ($xtreampiPackage['output_formats'] ?? '[]'), true) ?: []);
$xtreampiGroups = array_filter(GroupService::getAll(), static fn(array $group): bool => !empty($group['is_reseller']));
$xtreampiBouquets = BouquetService::getAllSimple();
$xtreampiOutputs = LineRepository::getOutputFormats();
$xtreampiServers = ServerRepository::getAllSimple();
$xtreampiChecked = static fn(string $key, bool $default = false): string => (!empty($xtreampiPackage) ? !empty($xtreampiPackage[$key]) : $default) ? ' checked' : '';
$xtreampiValue = static fn(string $key, string $default = ''): string => htmlspecialchars((string) ($xtreampiPackage[$key] ?? $default), ENT_QUOTES, 'UTF-8');
?>
<section class="sc-package-editor">
    <div class="sc-page-heading"><div><p class="sc-eyebrow">Account Management</p><h1><?php echo $xtreampiEditing ? 'Edit Package' : 'Add Package'; ?></h1></div><div class="sc-page-actions"><a class="sc-button sc-button-secondary" href="packages"><i class="fe-chevron-left" aria-hidden="true"></i> Back to packages</a></div></div>
    <form class="sc-form sc-package-form" action="post.php?action=package&amp;referer=packages" method="post" enctype="multipart/form-data" data-status-invalid-name="<?php echo intval(STATUS_INVALID_NAME); ?>">
        <?php if ($xtreampiEditing): ?><input type="hidden" name="edit" value="<?php echo intval($xtreampiPackage['id']); ?>"><?php endif; ?>
        <input type="hidden" name="bouquets_selected" value="[]"><input type="hidden" name="groups_selected" value="[]">
        <section class="sc-form-section"><h2>Details</h2><div class="sc-form-grid">
            <label class="sc-form-span">Package Name<input type="text" name="package_name" required value="<?php echo $xtreampiValue('package_name'); ?>"></label>
            <fieldset class="sc-package-block"><legend>Trial Package</legend><label class="sc-check"><input type="checkbox" name="is_trial"<?php echo $xtreampiChecked('is_trial'); ?>><span>Enabled</span></label><label>Credit Cost<input type="number" name="trial_credits" min="0" value="<?php echo $xtreampiValue('trial_credits', '0'); ?>"></label><div class="sc-inline-fields"><label>Duration<input type="number" name="trial_duration" min="0" value="<?php echo $xtreampiValue('trial_duration', '0'); ?>"></label><label>Unit<select name="trial_duration_in"><option value="hours"<?php echo ($xtreampiPackage['trial_duration_in'] ?? 'hours') === 'hours' ? ' selected' : ''; ?>>Hours</option><option value="days"<?php echo ($xtreampiPackage['trial_duration_in'] ?? '') === 'days' ? ' selected' : ''; ?>>Days</option></select></label></div></fieldset>
            <fieldset class="sc-package-block"><legend>Standard Package</legend><label class="sc-check"><input type="checkbox" name="is_official"<?php echo $xtreampiChecked('is_official'); ?>><span>Enabled</span></label><label>Credit Cost<input type="number" name="official_credits" min="0" value="<?php echo $xtreampiValue('official_credits', '0'); ?>"></label><div class="sc-inline-fields"><label>Duration<input type="number" name="official_duration" min="0" value="<?php echo $xtreampiValue('official_duration', '0'); ?>"></label><label>Unit<select name="official_duration_in"><?php foreach (['hours'=>'Hours','days'=>'Days','months'=>'Months','years'=>'Years'] as $value=>$label): ?><option value="<?php echo $value; ?>"<?php echo ($xtreampiPackage['official_duration_in'] ?? 'hours') === $value ? ' selected' : ''; ?>><?php echo $label; ?></option><?php endforeach; ?></select></label></div></fieldset>
        </div></section>
        <section class="sc-form-section"><h2>Options</h2><div class="sc-package-options">
            <?php foreach ([['is_mag','MAG devices',false],['is_e2','Enigma2 devices',false],['is_line','Standard lines',true],['is_isplock','Lock to ISP',false],['is_restreamer','Restreamer',false],['check_compatible','Verify compatibility',true]] as [$key,$label,$default]): ?><label class="sc-check"><input type="checkbox" name="<?php echo $key; ?>"<?php echo $xtreampiChecked($key, $default); ?>><span><?php echo $label; ?></span></label><?php endforeach; ?>
        </div><div class="sc-form-grid">
            <label>Forced Connection<select name="force_server_id"><option value="0">Disabled</option><?php foreach ($xtreampiServers as $server): ?><option value="<?php echo intval($server['id']); ?>"<?php echo intval($xtreampiPackage['force_server_id'] ?? 0) === intval($server['id']) ? ' selected' : ''; ?>><?php echo htmlspecialchars((string) $server['server_name'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></label>
            <label>Maximum Connections<input type="number" name="max_connections" min="0" value="<?php echo $xtreampiValue('max_connections', '1'); ?>"></label>
            <label>Forced Country<select name="forced_country"><?php foreach (($rCountries ?? []) as $country): ?><option value="<?php echo htmlspecialchars((string) $country['id'], ENT_QUOTES, 'UTF-8'); ?>"<?php echo (string) ($xtreampiPackage['forced_country'] ?? '') === (string) $country['id'] ? ' selected' : ''; ?>><?php echo htmlspecialchars((string) $country['name'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></label>
            <fieldset class="sc-package-output"><legend>Output Formats</legend><?php foreach ($xtreampiOutputs as $output): ?><label class="sc-check"><input type="checkbox" name="output_formats[]" value="<?php echo intval($output['access_output_id']); ?>"<?php echo in_array(intval($output['access_output_id']), $xtreampiSelectedOutputs, true) ? ' checked' : ''; ?>><span><?php echo htmlspecialchars((string) $output['output_name'], ENT_QUOTES, 'UTF-8'); ?></span></label><?php endforeach; ?></fieldset>
        </div></section>
        <section class="sc-form-section"><div class="sc-section-heading"><h2>Reseller Groups</h2><button class="sc-button sc-button-secondary" type="button" data-sc-toggle-selection="groups">Toggle all</button></div><div class="sc-selection-grid" data-sc-selection="groups"><?php foreach ($xtreampiGroups as $group): ?><label class="sc-selection-card"><input type="checkbox" value="<?php echo intval($group['group_id']); ?>"<?php echo in_array(intval($group['group_id']), $xtreampiSelectedGroups, true) ? ' checked' : ''; ?>><span><?php echo htmlspecialchars((string) $group['group_name'], ENT_QUOTES, 'UTF-8'); ?></span></label><?php endforeach; ?></div></section>
        <section class="sc-form-section"><div class="sc-section-heading"><h2>Bouquets</h2><button class="sc-button sc-button-secondary" type="button" data-sc-toggle-selection="bouquets">Toggle all</button></div><div class="sc-selection-grid" data-sc-selection="bouquets"><?php foreach ($xtreampiBouquets as $bouquet): ?><label class="sc-selection-card"><input type="checkbox" value="<?php echo intval($bouquet['id']); ?>"<?php echo in_array(intval($bouquet['id']), $xtreampiSelectedBouquets, true) ? ' checked' : ''; ?>><span><strong><?php echo htmlspecialchars((string) $bouquet['bouquet_name'], ENT_QUOTES, 'UTF-8'); ?></strong><small><?php echo count(json_decode((string) $bouquet['bouquet_channels'], true) ?: []); ?> live · <?php echo count(json_decode((string) $bouquet['bouquet_movies'], true) ?: []); ?> movies · <?php echo count(json_decode((string) $bouquet['bouquet_series'], true) ?: []); ?> series · <?php echo count(json_decode((string) $bouquet['bouquet_radios'], true) ?: []); ?> radio</small></span></label><?php endforeach; ?></div></section>
        <div class="sc-form-error" data-sc-package-error role="alert" hidden></div>
        <div class="sc-form-actions"><button class="sc-button sc-button-primary" type="submit" name="submit_package" value="<?php echo $xtreampiEditing ? 'Edit' : 'Add'; ?>"><?php echo $xtreampiEditing ? 'Save Package' : 'Add Package'; ?></button><a class="sc-button sc-button-secondary" href="packages">Cancel</a></div>
    </form>
</section>
