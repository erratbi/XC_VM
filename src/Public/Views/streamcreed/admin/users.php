<?php

use XcVm\Core\Auth\Authorization;
use XcVm\Domain\User\GroupService;

$streamcreedPageScripts = ['assets/streamcreed/users.js'];
$streamcreedCanAddUsers = Authorization::check('adv', 'add_reguser');
$streamcreedCanEditUsers = Authorization::check('adv', 'edit_reguser');
$streamcreedCanViewLines = Authorization::check('adv', 'users');
$streamcreedCanViewMags = Authorization::check('adv', 'manage_mag');
$streamcreedCanViewEnigmas = Authorization::check('adv', 'manage_e2');
$streamcreedDefaultEntries = intval($rSettings['default_entries'] ?? 25);
if (!in_array($streamcreedDefaultEntries, [10, 25, 50, 100], true)) $streamcreedDefaultEntries = 25;
?>
<section class="sc-registered-users" data-sc-registered-users data-endpoint="table" data-default-entries="<?php echo $streamcreedDefaultEntries; ?>" data-can-edit="<?php echo $streamcreedCanEditUsers ? '1' : '0'; ?>" data-can-lines="<?php echo $streamcreedCanViewLines ? '1' : '0'; ?>" data-can-mags="<?php echo $streamcreedCanViewMags ? '1' : '0'; ?>" data-can-enigmas="<?php echo $streamcreedCanViewEnigmas ? '1' : '0'; ?>">
	<div class="sc-page-heading">
		<div>
			<p class="sc-eyebrow">Account management</p>
			<h1>Registered Users</h1>
		</div>
		<div class="sc-page-actions">
			<?php if ($streamcreedCanAddUsers): ?>
				<a class="sc-button sc-button-primary" href="user"><i class="fe-plus" aria-hidden="true"></i> Add user</a>
			<?php endif; ?>
		</div>
	</div>

	<div class="sc-toolbar">
		<label class="sc-search-field">
			<i class="fe-search" aria-hidden="true"></i>
			<span class="sc-visually-hidden">Search registered users</span>
			<input type="search" placeholder="Search username, IP, email, notes, or dates" data-sc-user-search>
		</label>
		<label class="sc-filter-field">
			<span>Status / group</span>
			<select data-sc-user-filter>
				<option value="">All users</option>
				<option value="-1">Active</option>
				<option value="-2">Disabled</option>
				<?php foreach (GroupService::getAll() as $streamcreedGroup): ?>
					<option value="<?php echo intval($streamcreedGroup['group_id']); ?>"><?php echo htmlspecialchars((string) $streamcreedGroup['group_name'], ENT_QUOTES, 'UTF-8'); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
		<label class="sc-filter-field sc-entry-field">
			<span>Per page</span>
			<select data-sc-user-entries>
				<?php foreach ([10, 25, 50, 100] as $streamcreedEntries): ?>
					<option value="<?php echo $streamcreedEntries; ?>"<?php echo $streamcreedDefaultEntries === $streamcreedEntries ? ' selected' : ''; ?>><?php echo $streamcreedEntries; ?></option>
				<?php endforeach; ?>
			</select>
		</label>
	</div>
	<?php if ($streamcreedCanEditUsers): ?>
		<div class="sc-user-bulk" data-sc-user-bulk hidden>
			<strong><span data-sc-user-selected-count>0</span> selected</strong>
			<div>
				<button type="button" data-sc-user-bulk-action="enable">Enable</button>
				<button type="button" data-sc-user-bulk-action="disable">Disable</button>
				<button type="button" class="is-danger" data-sc-user-bulk-action="delete">Delete</button>
				<button type="button" data-sc-user-selection-clear>Clear</button>
			</div>
		</div>
	<?php endif; ?>

	<div class="sc-data-panel">
		<div class="sc-table-scroll">
			<table class="sc-data-table">
				<thead><tr><?php if ($streamcreedCanEditUsers): ?><th class="sc-select-column"><input type="checkbox" data-sc-user-select-all aria-label="Select all users on this page"></th><?php endif; ?><th>User</th><th>Owner</th><th>Group</th><th>Status</th><th>Credits</th><th>Lines</th><th>MAGs</th><th>Enigma2</th><th>Last login</th><th><span class="sc-visually-hidden">Actions</span></th></tr></thead>
				<tbody data-sc-user-rows><tr><td class="sc-table-state" colspan="<?php echo $streamcreedCanEditUsers ? 11 : 10; ?>"><span class="sc-spinner" aria-hidden="true"></span> Loading registered users…</td></tr></tbody>
			</table>
		</div>
		<footer class="sc-table-footer">
			<span data-sc-user-range>Loading…</span>
			<div class="sc-pagination"><button type="button" data-sc-user-previous><i class="fe-chevron-left" aria-hidden="true"></i> Previous</button><span data-sc-user-page>Page 1</span><button type="button" data-sc-user-next>Next <i class="fe-chevron-right" aria-hidden="true"></i></button></div>
		</footer>
	</div>
</section>
