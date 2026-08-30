<?php
use XcVm\Domain\Line\PackageService;
use XcVm\Domain\User\GroupService;

$streamcreedPageScripts = ['assets/streamcreed/group.js'];
$streamcreedGroup = is_array($rGroup ?? null) ? $rGroup : [];
$streamcreedEditing = intval($streamcreedGroup['group_id'] ?? 0) > 0;
$streamcreedCanChangeRole = !$streamcreedEditing || !empty($streamcreedGroup['can_delete']);
$streamcreedAllowedPages = json_decode((string) ($streamcreedGroup['allowed_pages'] ?? '[]'), true) ?: [];
$streamcreedPackageIDs = array_map('intval', is_array($rPackageIDs ?? null) ? $rPackageIDs : []);
$streamcreedGroupIDs = array_map('intval', is_array($rGroupIDs ?? null) ? $rGroupIDs : []);
$streamcreedPackages = PackageService::getAll();
$streamcreedSubgroups = array_filter(GroupService::getAll(), static fn(array $group): bool => !empty($group['is_reseller']) && intval($group['group_id']) !== intval($streamcreedGroup['group_id'] ?? 0));
$streamcreedPermissions = is_array($rAdvPermissions ?? null) ? $rAdvPermissions : [];
$streamcreedValue = static fn(string $key, string $default = ''): string => htmlspecialchars((string) ($streamcreedGroup[$key] ?? $default), ENT_QUOTES, 'UTF-8');
$streamcreedChecked = static fn(string $key, bool $default = false): string => (!empty($streamcreedGroup) ? !empty($streamcreedGroup[$key]) : $default) ? ' checked' : '';
?>
<section class="sc-group-editor">
    <div class="sc-page-heading"><div><p class="sc-eyebrow">Access Control</p><h1><?php echo $streamcreedEditing ? 'Edit Group' : 'Add Group'; ?></h1></div><div class="sc-page-actions"><a class="sc-button sc-button-secondary" href="groups"><i class="fe-chevron-left"></i> Back to groups</a></div></div>
    <form class="sc-form sc-group-form" action="post.php?action=group&amp;referer=groups" method="post" enctype="multipart/form-data" data-status-invalid-name="<?php echo intval(STATUS_INVALID_NAME); ?>" data-status-invalid-input="<?php echo intval(STATUS_INVALID_INPUT); ?>">
        <?php if ($streamcreedEditing): ?><input type="hidden" name="edit" value="<?php echo intval($streamcreedGroup['group_id']); ?>"><?php endif; ?>
        <input type="hidden" name="permissions_selected" value="[]"><input type="hidden" name="packages_selected" value="[]"><input type="hidden" name="groups_selected" value="[]"><input type="hidden" name="notice_html" value="">
        <section class="sc-form-section"><h2>Details</h2><div class="sc-form-grid">
            <label class="sc-form-span">Group Name<input type="text" name="group_name" required value="<?php echo $streamcreedValue('group_name'); ?>"></label>
            <label class="sc-check"><input type="checkbox" name="is_admin" data-sc-group-role<?php echo $streamcreedChecked('is_admin'); ?><?php echo $streamcreedCanChangeRole ? '' : ' disabled'; ?>><span>Administrator group</span></label>
            <label class="sc-check"><input type="checkbox" name="is_reseller" data-sc-group-role<?php echo $streamcreedChecked('is_reseller'); ?><?php echo $streamcreedCanChangeRole ? '' : ' disabled'; ?>><span>Reseller group</span></label>
        </div></section>
        <section class="sc-form-section" data-sc-reseller-section><h2>Reseller Rules</h2><div class="sc-form-grid">
            <label>Allowed Trials<input type="number" min="0" name="total_allowed_gen_trials" value="<?php echo $streamcreedValue('total_allowed_gen_trials', '0'); ?>"></label>
            <label>Allowed Trials In<select name="total_allowed_gen_in"><option value="day"<?php echo ($streamcreedGroup['total_allowed_gen_in'] ?? 'day') === 'day' ? ' selected' : ''; ?>>Day</option><option value="month"<?php echo ($streamcreedGroup['total_allowed_gen_in'] ?? '') === 'month' ? ' selected' : ''; ?>>Month</option></select></label>
            <label>Minimum Credits for Trials<input type="number" min="0" name="minimum_trial_credits" value="<?php echo $streamcreedValue('minimum_trial_credits', '0'); ?>"></label>
            <label>Sub-reseller Price<input type="number" min="0" name="create_sub_resellers_price" value="<?php echo $streamcreedValue('create_sub_resellers_price', '0'); ?>"></label>
            <label>Minimum Username Length<input type="number" min="0" name="minimum_username_length" value="<?php echo $streamcreedValue('minimum_username_length', '8'); ?>"></label>
            <label>Minimum Password Length<input type="number" min="0" name="minimum_password_length" value="<?php echo $streamcreedValue('minimum_password_length', '8'); ?>"></label>
        </div><div class="sc-package-options sc-group-switches">
            <?php foreach ([['allow_restrictions','Allow line restrictions',true],['allow_change_bouquets','Allow bouquet editing',false],['delete_users','Can delete users',true],['allow_download','Show M3U download',true],['can_view_vod','Can view VOD streams',true],['reseller_client_connection_logs','Can view live connections',true],['allow_change_username','Can change usernames',true],['allow_change_password','Can change passwords',true]] as [$key,$label,$default]): ?><label class="sc-check"><input type="checkbox" name="<?php echo $key; ?>"<?php echo $streamcreedChecked($key, $default); ?>><span><?php echo $label; ?></span></label><?php endforeach; ?>
        </div></section>
        <section class="sc-form-section" data-sc-reseller-section><div class="sc-section-heading"><h2>Packages</h2><button type="button" class="sc-button sc-button-secondary" data-sc-toggle-selection="packages">Toggle all</button></div><div class="sc-selection-grid" data-sc-selection="packages"><?php foreach ($streamcreedPackages as $package): ?><label class="sc-selection-card"><input type="checkbox" value="<?php echo intval($package['id']); ?>"<?php echo in_array(intval($package['id']), $streamcreedPackageIDs, true) ? ' checked' : ''; ?>><span><strong><?php echo htmlspecialchars((string) $package['package_name'], ENT_QUOTES, 'UTF-8'); ?></strong><small><?php echo !empty($package['is_trial']) ? 'Trial' : ''; ?><?php echo !empty($package['is_trial']) && !empty($package['is_official']) ? ' · ' : ''; ?><?php echo !empty($package['is_official']) ? 'Standard' : ''; ?></small></span></label><?php endforeach; ?></div></section>
        <section class="sc-form-section"><div class="sc-section-heading"><h2>Sub-resellers</h2><label class="sc-check"><input type="checkbox" name="create_sub_resellers" data-sc-subresellers-enabled<?php echo $streamcreedChecked('create_sub_resellers'); ?>><span>Allow sub-reseller creation</span></label></div><div class="sc-selection-grid" data-sc-selection="groups"><?php foreach ($streamcreedSubgroups as $group): ?><label class="sc-selection-card"><input type="checkbox" value="<?php echo intval($group['group_id']); ?>"<?php echo in_array(intval($group['group_id']), $streamcreedGroupIDs, true) ? ' checked' : ''; ?>><span><strong><?php echo htmlspecialchars((string) $group['group_name'], ENT_QUOTES, 'UTF-8'); ?></strong><small><?php echo !empty($group['create_sub_resellers']) ? 'Can create sub-resellers' : 'Reseller'; ?></small></span></label><?php endforeach; ?></div></section>
        <section class="sc-form-section" data-sc-reseller-section><h2>Dashboard Notice</h2><label>HTML shown to this group<textarea data-sc-notice rows="8"><?php echo htmlspecialchars((string) ($rNotice ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea></label></section>
        <?php if ($streamcreedCanChangeRole): ?><section class="sc-form-section" data-sc-admin-section><div class="sc-section-heading"><h2>Administrator Permissions</h2><div><button type="button" class="sc-button sc-button-secondary" data-sc-permissions="all">Select all</button> <button type="button" class="sc-button sc-button-secondary" data-sc-permissions="none">Clear</button></div></div><div class="sc-permission-grid" data-sc-selection="permissions"><?php foreach ($streamcreedPermissions as $permission): ?><label class="sc-selection-card"><input type="checkbox" value="<?php echo htmlspecialchars((string) $permission[0], ENT_QUOTES, 'UTF-8'); ?>"<?php echo in_array($permission[0], $streamcreedAllowedPages, true) ? ' checked' : ''; ?>><span><strong><?php echo htmlspecialchars((string) ($permission[1] ?: $permission[0]), ENT_QUOTES, 'UTF-8'); ?></strong><?php if (!empty($permission[2])): ?><small><?php echo htmlspecialchars((string) $permission[2], ENT_QUOTES, 'UTF-8'); ?></small><?php endif; ?></span></label><?php endforeach; ?></div></section><?php endif; ?>
        <div class="sc-form-error" data-sc-group-error role="alert" hidden></div>
        <div class="sc-form-actions"><button class="sc-button sc-button-primary" type="submit" name="submit_group" value="<?php echo $streamcreedEditing ? 'Edit' : 'Add'; ?>"><?php echo $streamcreedEditing ? 'Save Group' : 'Add Group'; ?></button><a class="sc-button sc-button-secondary" href="groups">Cancel</a></div>
    </form>
</section>
