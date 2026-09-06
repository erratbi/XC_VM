<?php

use XcVm\Core\Auth\Authorization;

$xtreampiPageScripts = ['assets/xtreampi/groups.js'];
$xtreampiGroups = is_array($groups ?? null) ? array_values($groups) : [];
$xtreampiCanAddGroup = Authorization::check('adv', 'add_group');
$xtreampiCanEditGroup = Authorization::check('adv', 'edit_group');
?>
<section class="sc-groups" data-sc-groups>
	<div class="sc-page-heading">
		<div>
			<p class="sc-eyebrow">Access control</p>
			<h1>User Groups</h1>
		</div>
		<div class="sc-page-actions">
			<?php if ($xtreampiCanAddGroup): ?><a class="sc-button sc-button-primary" href="group"><i class="fe-plus" aria-hidden="true"></i> Add group</a><?php endif; ?>
		</div>
	</div>

	<div class="sc-toolbar">
		<label class="sc-search-field"><i class="fe-search" aria-hidden="true"></i><span class="sc-visually-hidden">Search groups</span><input type="search" placeholder="Search group name or ID" data-sc-group-search></label>
		<label class="sc-filter-field"><span>Role</span><select data-sc-group-filter><option value="all">All roles</option><option value="admin">Administrators</option><option value="reseller">Resellers</option><option value="subreseller">Can create subresellers</option></select></label>
	</div>

	<div class="sc-data-panel">
		<div class="sc-table-scroll">
			<table class="sc-data-table">
				<thead><tr><th>Group</th><th>Administrator</th><th>Reseller</th><th>Can create subresellers</th><th>Capabilities</th><th><span class="sc-visually-hidden">Actions</span></th></tr></thead>
				<tbody data-sc-group-rows>
					<?php foreach ($xtreampiGroups as $xtreampiGroup): ?>
						<?php
						$xtreampiID = intval($xtreampiGroup['group_id'] ?? 0);
						$xtreampiName = (string) ($xtreampiGroup['group_name'] ?? 'Untitled group');
						$xtreampiAdmin = !empty($xtreampiGroup['is_admin']);
						$xtreampiReseller = !empty($xtreampiGroup['is_reseller']);
						$xtreampiSubreseller = !empty($xtreampiGroup['create_sub_resellers']);
						$xtreampiCanDeleteGroup = $xtreampiCanEditGroup && !empty($xtreampiGroup['can_delete']);
						$xtreampiRole = $xtreampiAdmin ? 'admin' : ($xtreampiReseller ? 'reseller' : ($xtreampiSubreseller ? 'subreseller' : 'standard'));
						?>
						<tr data-sc-group-row data-group-id="<?php echo $xtreampiID; ?>" data-role="<?php echo $xtreampiRole; ?>" data-search="<?php echo htmlspecialchars(strtolower($xtreampiID . ' ' . $xtreampiName), ENT_QUOTES, 'UTF-8'); ?>">
							<td><div class="sc-table-identity"><?php if ($xtreampiCanEditGroup): ?><a href="group?id=<?php echo $xtreampiID; ?>"><?php echo htmlspecialchars($xtreampiName, ENT_QUOTES, 'UTF-8'); ?></a><?php else: ?><strong><?php echo htmlspecialchars($xtreampiName, ENT_QUOTES, 'UTF-8'); ?></strong><?php endif; ?><small>#<?php echo $xtreampiID; ?></small></div></td>
							<td><span class="sc-row-status <?php echo $xtreampiAdmin ? 'is-active' : 'is-disabled'; ?>"><?php echo $xtreampiAdmin ? 'Yes' : 'No'; ?></span></td>
							<td><span class="sc-row-status <?php echo $xtreampiReseller ? 'is-active' : 'is-disabled'; ?>"><?php echo $xtreampiReseller ? 'Yes' : 'No'; ?></span></td>
							<td><span class="sc-row-status <?php echo $xtreampiSubreseller ? 'is-active' : 'is-disabled'; ?>"><?php echo $xtreampiSubreseller ? 'Yes' : 'No'; ?></span></td>
							<td><div class="sc-tag-list"><?php if ($xtreampiAdmin): ?><span class="sc-tag">Admin</span><?php endif; ?><?php if ($xtreampiReseller): ?><span class="sc-tag is-trial">Reseller</span><?php endif; ?><?php if ($xtreampiSubreseller): ?><span class="sc-tag">Subresellers</span><?php endif; ?><?php if (!$xtreampiAdmin && !$xtreampiReseller && !$xtreampiSubreseller): ?><span class="sc-table-muted">Standard access</span><?php endif; ?></div></td>
							<td class="sc-table-actions"><?php if ($xtreampiCanEditGroup): ?><a class="sc-row-action" href="group?id=<?php echo $xtreampiID; ?>">Edit</a><?php if ($xtreampiCanDeleteGroup): ?><button class="sc-row-action is-danger" type="button" data-sc-group-delete>Delete</button><?php endif; ?><?php endif; ?></td>
						</tr>
					<?php endforeach; ?>
					<tr data-sc-group-empty<?php echo count($xtreampiGroups) ? ' hidden' : ''; ?>><td class="sc-table-state" colspan="6">No groups have been created yet.</td></tr>
				</tbody>
			</table>
		</div>
	</div>
</section>
