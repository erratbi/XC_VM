<?php
$xtreampiUserRecord = is_array($rUser ?? null) ? $rUser : [];
$xtreampiUserId = intval($xtreampiUserRecord['id'] ?? 0);
$xtreampiEditing = $xtreampiUserId > 0;
$xtreampiRandom = static function (): string { $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'; $value = ''; for ($i = 0; $i < 10; $i++) $value .= $chars[random_int(0, strlen($chars) - 1)]; return $value; };
$xtreampiUsername = (string) ($xtreampiUserRecord['username'] ?? ($xtreampiEditing ? '' : $xtreampiRandom()));
$xtreampiPassword = $xtreampiEditing ? '' : $xtreampiRandom();
$xtreampiGroupService = 'XcVm' . chr(92) . 'Domain' . chr(92) . 'User' . chr(92) . 'GroupService';
$xtreampiGroups = $xtreampiGroupService::getAll();
?>
<section class="sc-user-editor">
    <div class="sc-page-heading"><div><p class="sc-eyebrow">Account Management</p><h1><?php echo $xtreampiEditing ? 'Edit User' : 'Add User'; ?></h1></div><div class="sc-page-actions"><a class="sc-button sc-button-secondary" href="users"><i class="fe-chevron-left" aria-hidden="true"></i> Back to users</a></div></div>
    <form class="sc-form" method="post" enctype="multipart/form-data" action="post.php?action=user&amp;referer=users%3Forder%3D0%26dir%3Ddesc" data-status-invalid-input="<?php echo intval(STATUS_INVALID_INPUT); ?>" data-status-invalid-group="<?php echo intval(STATUS_INVALID_GROUP); ?>" data-status-existing-username="<?php echo intval(STATUS_EXISTS_USERNAME); ?>">
        <input type="hidden" name="submit_user" value="<?php echo $xtreampiEditing ? 'Edit User' : 'Add User'; ?>">
        <?php if ($xtreampiEditing): ?><input type="hidden" name="edit" value="<?php echo $xtreampiUserId; ?>"><?php endif; ?>
        <div class="sc-form-grid">
            <label>Username<input type="text" name="username" required value="<?php echo htmlspecialchars($xtreampiUsername, ENT_QUOTES, 'UTF-8'); ?>"></label>
            <label>Password<input type="text" name="password" value="<?php echo htmlspecialchars($xtreampiPassword, ENT_QUOTES, 'UTF-8'); ?>" autocomplete="new-password" placeholder="<?php echo $xtreampiEditing ? 'Leave blank to keep current password' : ''; ?>"<?php echo $xtreampiEditing ? '' : ' required'; ?>></label>
            <label>Member Group<select name="member_group_id" required><?php foreach ($xtreampiGroups as $xtreampiGroup): ?><option value="<?php echo intval($xtreampiGroup['group_id']); ?>"<?php echo intval($xtreampiGroup['group_id']) === intval($xtreampiUserRecord['member_group_id'] ?? 0) ? ' selected' : ''; ?>><?php echo htmlspecialchars((string) ($xtreampiGroup['group_name'] ?? 'Group'), ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></label>
            <label>Owner ID<input type="number" name="owner_id" value="<?php echo htmlspecialchars((string) ($xtreampiUserRecord['owner_id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>"></label>
            <label>Credits<input type="number" name="credits" min="0" step="1" value="<?php echo htmlspecialchars((string) ($xtreampiUserRecord['credits'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>"></label>
            <label>Reason for Adjustment<input type="text" name="credits_reason" value=""></label>
            <label>Email Address<input type="email" name="email" value="<?php echo htmlspecialchars((string) ($xtreampiUserRecord['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></label>
            <label>Reseller DNS<input type="text" name="reseller_dns" value="<?php echo htmlspecialchars((string) ($xtreampiUserRecord['reseller_dns'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></label>
        </div>
        <label class="sc-form-wide">Notes<textarea name="notes" rows="4"><?php echo htmlspecialchars((string) ($xtreampiUserRecord['notes'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea></label>
        <div class="sc-form-error" data-sc-user-form-error role="alert" hidden></div>
        <p class="sc-settings-note"><i class="fe-info" aria-hidden="true"></i> Saving uses the existing legacy validation and account mutation handler.</p>
        <div class="sc-form-actions"><button class="sc-button sc-button-primary" type="submit"><?php echo $xtreampiEditing ? 'Save User' : 'Add User'; ?></button><a class="sc-button sc-button-secondary" href="users">Cancel</a></div>
    </form>
</section>
