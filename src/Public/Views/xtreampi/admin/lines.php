<?php

use XcVm\Core\Auth\Authorization;
use XcVm\Domain\Line\LineRepository;
use XcVm\Domain\Server\ServerRepository;

$xtreampiPageScripts = ['assets/xtreampi/lines.js'];
$xtreampiCanAddLines = Authorization::check('adv', 'add_user');
$xtreampiCanEditLines = Authorization::check('adv', 'edit_user');
$xtreampiCanMassEditLines = Authorization::check('adv', 'mass_edit_users');
$xtreampiCanViewConnections = Authorization::check('adv', 'live_connections');
$xtreampiCanFingerprint = Authorization::check('adv', 'fingerprint');
$xtreampiDefaultEntries = intval($rSettings['default_entries'] ?? 25);
if (!in_array($xtreampiDefaultEntries, [10, 25, 50, 100], true)) $xtreampiDefaultEntries = 25;
$xtreampiServers = ServerRepository::getAll();
$xtreampiPlaylistBase = rtrim((string) ($xtreampiServers[SERVER_ID]['site_url'] ?? ''), '/');
$xtreampiOutputDevices = LineRepository::getOutputDevices();
?>
<section class="sc-subscriptions" data-sc-subscriptions data-endpoint="table" data-default-entries="<?php echo $xtreampiDefaultEntries; ?>" data-can-edit="<?php echo $xtreampiCanEditLines ? '1' : '0'; ?>" data-can-view-connections="<?php echo $xtreampiCanViewConnections ? '1' : '0'; ?>" data-can-fingerprint="<?php echo $xtreampiCanFingerprint ? '1' : '0'; ?>">
	<div class="sc-page-heading">
		<div>
			<p class="sc-eyebrow">Subscriber access</p>
			<h1>Subscriptions</h1>
		</div>
		<div class="sc-page-actions">
			<?php if ($xtreampiCanMassEditLines): ?>
				<a class="sc-button sc-button-secondary" href="line_mass"><i class="fe-edit-3" aria-hidden="true"></i> Mass edit</a>
			<?php endif; ?>
			<?php if ($xtreampiCanAddLines): ?>
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
		<label class="sc-filter-field sc-owner-filter" data-sc-owner-filter>
			<span>Owner</span>
			<input type="search" placeholder="All owners" autocomplete="off" data-sc-owner-search>
			<input type="hidden" data-sc-owner-id>
			<div class="sc-owner-options" data-sc-owner-options hidden></div>
		</label>
		<label class="sc-filter-field sc-entry-field">
			<span>Per page</span>
			<select data-sc-subscription-entries>
				<?php foreach ([10, 25, 50, 100] as $xtreampiEntries): ?>
					<option value="<?php echo $xtreampiEntries; ?>"<?php echo $xtreampiDefaultEntries === $xtreampiEntries ? ' selected' : ''; ?>><?php echo $xtreampiEntries; ?></option>
				<?php endforeach; ?>
			</select>
		</label>
	</div>

	<div class="sc-data-panel">
		<div class="sc-table-scroll">
			<table class="sc-data-table">
				<thead>
					<tr>
						<th>Username</th>
						<th>Password</th>
						<th>Owner</th>
						<th>Status</th>
						<th>Online</th>
						<th>Trial</th>
						<th>Restreamer</th>
						<th>Active</th>
						<th>Connections</th>
						<th>Expiration</th>
						<th>Last connection</th>
						<th><span class="sc-visually-hidden">Actions</span></th>
					</tr>
				</thead>
				<tbody data-sc-subscription-rows>
					<tr><td class="sc-table-state" colspan="12"><span class="sc-spinner" aria-hidden="true"></span> Loading subscriptions…</td></tr>
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

<dialog class="sc-playlist-dialog" data-sc-playlist-dialog data-playlist-base="<?php echo htmlspecialchars($xtreampiPlaylistBase, ENT_QUOTES, 'UTF-8'); ?>">
	<form method="dialog">
		<div class="sc-dialog-heading"><div><p class="sc-eyebrow">Subscription</p><h2>Download Playlist</h2></div><button class="sc-dialog-close" value="cancel" aria-label="Close"><i class="fe-x" aria-hidden="true"></i></button></div>
		<div class="sc-dialog-body">
			<label>Format<select data-sc-playlist-format><option value="">Choose a format</option><?php foreach ($xtreampiOutputDevices as $xtreampiDevice): ?><optgroup label="<?php echo htmlspecialchars((string) $xtreampiDevice['device_name'], ENT_QUOTES, 'UTF-8'); ?>"><?php foreach ([['?output=hls', 'HLS'], ['', 'MPEGTS'], ['?output=rtmp', 'RTMP']] as [$xtreampiSuffix, $xtreampiLabel]): ?><option value="<?php echo htmlspecialchars((string) $xtreampiDevice['device_key'] . $xtreampiSuffix, ENT_QUOTES, 'UTF-8'); ?>" data-copy-text="<?php echo htmlspecialchars((string) ($xtreampiDevice['copy_text'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) $xtreampiDevice['device_name'] . ' - ' . $xtreampiLabel, ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></optgroup><?php endforeach; ?></select></label>
			<fieldset><legend>Limit output</legend><div class="sc-playlist-types"><?php foreach ([['live','Live streams'],['movie','Movies'],['created_live','Created channels'],['radio_streams','Radio stations'],['series','TV series']] as [$xtreampiKey,$xtreampiLabel]): ?><label class="sc-check"><input type="checkbox" value="<?php echo $xtreampiKey; ?>" data-sc-playlist-output><span><?php echo $xtreampiLabel; ?></span></label><?php endforeach; ?></div></fieldset>
			<label>Playlist URL or command<div class="sc-input-action"><input type="text" readonly data-sc-playlist-url><button type="button" data-sc-playlist-copy><i class="fe-copy" aria-hidden="true"></i> Copy</button><button type="button" data-sc-playlist-open><i class="fe-download" aria-hidden="true"></i> Download</button></div></label>
		</div>
	</form>
</dialog>

<dialog class="sc-playlist-dialog sc-notes-dialog" data-sc-notes-dialog><form method="dialog"><div class="sc-dialog-heading"><div><p class="sc-eyebrow">Subscription</p><h2>Notes</h2></div><button class="sc-dialog-close" value="cancel" aria-label="Close"><i class="fe-x" aria-hidden="true"></i></button></div><div class="sc-dialog-body"><section><h3>Admin notes</h3><p data-sc-admin-notes></p></section><section><h3>Reseller notes</h3><p data-sc-reseller-notes></p></section></div></form></dialog>

<dialog class="sc-playlist-dialog sc-whatsapp-dialog" data-sc-whatsapp-dialog><form method="dialog"><div class="sc-dialog-heading"><div><p class="sc-eyebrow">Renewal</p><h2>WhatsApp Reminder</h2></div><button class="sc-dialog-close" value="cancel" aria-label="Close"><i class="fe-x" aria-hidden="true"></i></button></div><div class="sc-dialog-body"><label>Language<select data-sc-whatsapp-language><option value="en">English</option><option value="de">Deutsch</option><option value="tr">Türkçe</option></select></label><label>Message<textarea rows="7" readonly data-sc-whatsapp-message></textarea></label><a class="sc-button sc-button-primary" target="_blank" rel="noopener" data-sc-whatsapp-send><i class="fe-send" aria-hidden="true"></i> Send via WhatsApp</a></div></form></dialog>

<dialog class="sc-playlist-dialog sc-fingerprint-dialog" data-sc-fingerprint-dialog><form method="dialog"><div class="sc-dialog-heading"><div><p class="sc-eyebrow">Active connection</p><h2>Fingerprint</h2></div><button class="sc-dialog-close" value="cancel" aria-label="Close"><i class="fe-x" aria-hidden="true"></i></button></div><div class="sc-dialog-body"><div class="sc-fingerprint-grid"><label>Type<select data-sc-fingerprint-type><option value="1">Activity ID</option><option value="2">Username</option><option value="3">Message</option></select></label><label>Size<input type="number" min="1" value="36" data-sc-fingerprint-size></label><label>Colour<input type="color" value="#ffffff" data-sc-fingerprint-color></label><label>Position X<input type="number" min="0" value="10" data-sc-fingerprint-x></label><label>Position Y<input type="number" min="0" value="10" data-sc-fingerprint-y></label></div><label data-sc-fingerprint-message-wrap hidden>Custom message<input type="text" data-sc-fingerprint-message></label><button class="sc-button sc-button-primary" type="button" data-sc-fingerprint-send><i class="fe-crosshair" aria-hidden="true"></i> Send fingerprint</button></div></form></dialog>
