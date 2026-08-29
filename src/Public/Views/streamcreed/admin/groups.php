<?php

use XcVm\Core\Auth\Authorization;

$streamcreedPageScripts = ['assets/streamcreed/groups.js'];
$streamcreedGroups = is_array($groups ?? null) ? array_values($groups) : [];
$streamcreedCanAddGroup = Authorization::check('adv', 'add_group');
$streamcreedCanEditGroup = Authorization::check('adv', 'edit_group');
?>
<section class="sc-groups" data-sc-groups>
	<div class="sc-page-heading">
		<div>
			<p class="sc-eyebrow">Access control</p>
			<h1>User Groups</h1>
		</div>
		<div class="sc-page-actions">
			<?php if ($streamcreedCanAddGroup): ?><a class="sc-button sc-button-primary" href="group"><i class="fe-plus" aria-hidden="true"></i> Add group</a><?php endif; ?>
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
					<?php foreach ($streamcreedGroups as $streamcreedGroup): ?>
						<?php
						$streamcreedID = intval($streamcreedGroup['group_id'] ?? 0);
						$streamcreedName = (string) ($streamcreedGroup['group_name'] ?? 'Untitled group');
						$streamcreedAdmin = !empty($streamcreedGroup['is_admin']);
						$streamcreedReseller = !empty($streamcreedGroup['is_reseller']);
						$streamcreedSubreseller = !empty($streamcreedGroup['create_sub_resellers']);
						$streamcreedRole = $streamcreedAdmin ? 'admin' : ($streamcreedReseller ? 'reseller' : ($streamcreedSubreseller ? 'subreseller' : 'standard'));
						?>
						<tr data-sc-group-row data-role="<?php echo $streamcreedRole; ?>" data-search="<?php echo htmlspecialchars(strtolower($streamcreedID . ' ' . $streamcreedName), ENT_QUOTES, 'UTF-8'); ?>">
							<td><div class="sc-table-identity"><?php if ($streamcreedCanEditGroup): ?><a href="group?id=<?php echo $streamcreedID; ?>"><?php echo htmlspecialchars($streamcreedName, ENT_QUOTES, 'UTF-8'); ?></a><?php else: ?><strong><?php echo htmlspecialchars($streamcreedName, ENT_QUOTES, 'UTF-8'); ?></strong><?php endif; ?><small>#<?php echo $streamcreedID; ?></small></div></td>
							<td><span class="sc-row-status <?php echo $streamcreedAdmin ? 'is-active' : 'is-disabled'; ?>"><?php echo $streamcreedAdmin ? 'Yes' : 'No'; ?></span></td>
							<td><span class="sc-row-status <?php echo $streamcreedReseller ? 'is-active' : 'is-disabled'; ?>"><?php echo $streamcreedReseller ? 'Yes' : 'No'; ?></span></td>
							<td><span class="sc-row-status <?php echo $streamcreedSubreseller ? 'is-active' : 'is-disabled'; ?>"><?php echo $streamcreedSubreseller ? 'Yes' : 'No'; ?></span></td>
							<td><div class="sc-tag-list"><?php if ($streamcreedAdmin): ?><span class="sc-tag">Admin</span><?php endif; ?><?php if ($streamcreedReseller): ?><span class="sc-tag is-trial">Reseller</span><?php endif; ?><?php if ($streamcreedSubreseller): ?><span class="sc-tag">Subresellers</span><?php endif; ?><?php if (!$streamcreedAdmin && !$streamcreedReseller && !$streamcreedSubreseller): ?><span class="sc-table-muted">Standard access</span><?php endif; ?></div></td>
							<td class="sc-table-actions"><?php if ($streamcreedCanEditGroup): ?><a class="sc-row-action" href="group?id=<?php echo $streamcreedID; ?>">Edit in legacy</a><?php endif; ?></td>
						</tr>
					<?php endforeach; ?>
					<tr data-sc-group-empty<?php echo count($streamcreedGroups) ? ' hidden' : ''; ?>><td class="sc-table-state" colspan="6">No groups have been created yet.</td></tr>
				</tbody>
			</table>
		</div>
	</div>
</section>
