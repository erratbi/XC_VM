<?php

use XcVm\Core\Auth\Authorization;

$streamcreedPageScripts = ['assets/streamcreed/hmacs.js'];
$streamcreedHmacs = is_array($hmacs ?? null) ? array_values($hmacs) : [];
$streamcreedCanManageHmac = Authorization::check('adv', 'add_hmac');
?>
<section class="sc-hmacs" data-sc-hmacs>
	<div class="sc-page-heading">
		<div><p class="sc-eyebrow">Devices</p><h1>HMAC Keys</h1></div>
		<div class="sc-page-actions"><?php if ($streamcreedCanManageHmac): ?><a class="sc-button sc-button-primary" href="hmac"><i class="fe-plus" aria-hidden="true"></i> Add HMAC key</a><?php endif; ?></div>
	</div>
	<div class="sc-toolbar">
		<label class="sc-search-field"><i class="fe-search" aria-hidden="true"></i><span class="sc-visually-hidden">Search HMAC keys</span><input type="search" placeholder="Search description or ID" data-sc-hmac-search></label>
		<label class="sc-filter-field"><span>Status</span><select data-sc-hmac-filter><option value="all">All keys</option><option value="enabled">Enabled</option><option value="disabled">Disabled</option></select></label>
	</div>
	<div class="sc-data-panel"><div class="sc-table-scroll"><table class="sc-data-table"><thead><tr><th>HMAC key</th><th>Status</th><th><span class="sc-visually-hidden">Actions</span></th></tr></thead><tbody data-sc-hmac-rows>
		<?php foreach ($streamcreedHmacs as $streamcreedHmac): ?><?php $id = intval($streamcreedHmac['id'] ?? 0); $notes = trim((string) ($streamcreedHmac['notes'] ?? '')); $enabled = !empty($streamcreedHmac['enabled']); ?>
		<tr data-sc-hmac-row data-status="<?php echo $enabled ? 'enabled' : 'disabled'; ?>" data-search="<?php echo htmlspecialchars(strtolower($id . ' ' . $notes), ENT_QUOTES, 'UTF-8'); ?>"><td><div class="sc-table-identity"><?php if ($streamcreedCanManageHmac): ?><a href="hmac?id=<?php echo $id; ?>"><?php echo htmlspecialchars($notes !== '' ? $notes : 'Untitled HMAC key', ENT_QUOTES, 'UTF-8'); ?></a><?php else: ?><strong><?php echo htmlspecialchars($notes !== '' ? $notes : 'Untitled HMAC key', ENT_QUOTES, 'UTF-8'); ?></strong><?php endif; ?><small>#<?php echo $id; ?></small></div></td><td><span class="sc-row-status is-<?php echo $enabled ? 'active' : 'disabled'; ?>"><?php echo $enabled ? 'Enabled' : 'Disabled'; ?></span></td><td class="sc-table-actions"><?php if ($streamcreedCanManageHmac): ?><a class="sc-row-action" href="hmac?id=<?php echo $id; ?>">Edit</a><?php endif; ?></td></tr>
		<?php endforeach; ?>
		<tr data-sc-hmac-empty<?php echo $streamcreedHmacs ? ' hidden' : ''; ?>><td class="sc-table-state" colspan="3">No HMAC keys have been created yet.</td></tr>
	</tbody></table></div></div>
</section>
