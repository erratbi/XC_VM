<?php

use XcVm\Core\Auth\Authorization;

$streamcreedPageScripts = ['assets/streamcreed/lines.js'];
$streamcreedCanAddLines = Authorization::check('adv', 'add_user');
$streamcreedCanEditLines = Authorization::check('adv', 'edit_user');
$streamcreedCanMassEditLines = Authorization::check('adv', 'mass_edit_users');
$streamcreedCanViewConnections = Authorization::check('adv', 'live_connections');
$streamcreedDefaultEntries = intval($rSettings['default_entries'] ?? 25);
if (!in_array($streamcreedDefaultEntries, [10, 25, 50, 100], true)) $streamcreedDefaultEntries = 25;
?>
<section class="sc-subscriptions" data-sc-subscriptions data-endpoint="table" data-default-entries="<?php echo $streamcreedDefaultEntries; ?>" data-can-edit="<?php echo $streamcreedCanEditLines ? '1' : '0'; ?>" data-can-view-connections="<?php echo $streamcreedCanViewConnections ? '1' : '0'; ?>">
	<div class="sc-page-heading">
		<div>
			<p class="sc-eyebrow">Subscriber access</p>
			<h1>Subscriptions</h1>
		</div>
		<div class="sc-page-actions">
			<?php if ($streamcreedCanMassEditLines): ?>
				<a class="sc-button sc-button-secondary" href="line_mass"><i class="fe-edit-3" aria-hidden="true"></i> Mass edit</a>
			<?php endif; ?>
			<?php if ($streamcreedCanAddLines): ?>
				<a class="sc-button sc-button-primary" href="line"><i class="fe-plus" aria-hidden="true"></i> Add subscription</a>
			<?php endif; ?>
		</div>
	</div>

	<div class="sc-toolbar">
		<label class="sc-search-field">
			<i class="fe-search" aria-hidden="true"></i>
			<span class="sc-visually-hidden">Search subscriptions</span>
			<input type="search" placeholder="Search username, notes, limits, or expiration" data-sc-subscription-search>
		</label>
		<label class="sc-filter-field">
			<span>Status</span>
			<select data-sc-subscription-filter>
				<option value="">All statuses</option>
				<option value="1">Active</option>
				<option value="2">Disabled</option>
				<option value="3">Banned</option>
				<option value="4">Expired</option>
				<option value="5">Trial</option>
				<option value="6">Restreamer</option>
				<option value="7">Ministra</option>
				<option value="8">Expiring soon</option>
			</select>
		</label>
		<label class="sc-filter-field sc-entry-field">
			<span>Per page</span>
			<select data-sc-subscription-entries>
				<?php foreach ([10, 25, 50, 100] as $streamcreedEntries): ?>
					<option value="<?php echo $streamcreedEntries; ?>"<?php echo $streamcreedDefaultEntries === $streamcreedEntries ? ' selected' : ''; ?>><?php echo $streamcreedEntries; ?></option>
				<?php endforeach; ?>
			</select>
		</label>
	</div>

	<div class="sc-data-panel">
		<div class="sc-table-scroll">
			<table class="sc-data-table">
				<thead>
					<tr>
						<th>Subscription</th>
						<th>Owner</th>
						<th>Status</th>
						<th>Connections</th>
						<th>Type</th>
						<th>Expiration</th>
						<th>Last activity</th>
						<th><span class="sc-visually-hidden">Actions</span></th>
					</tr>
				</thead>
				<tbody data-sc-subscription-rows>
					<tr><td class="sc-table-state" colspan="8"><span class="sc-spinner" aria-hidden="true"></span> Loading subscriptions…</td></tr>
				</tbody>
			</table>
		</div>
		<footer class="sc-table-footer">
			<span data-sc-subscription-range>Loading…</span>
			<div class="sc-pagination">
				<button type="button" data-sc-subscription-previous><i class="fe-chevron-left" aria-hidden="true"></i> Previous</button>
				<span data-sc-subscription-page>Page 1</span>
				<button type="button" data-sc-subscription-next>Next <i class="fe-chevron-right" aria-hidden="true"></i></button>
			</div>
		</footer>
	</div>
</section>
