<?php

use XcVm\Core\Auth\Authorization;

$xtreampiPageScripts = ['assets/xtreampi/enigmas.js'];
$xtreampiCanAddEnigma = Authorization::check('adv', 'add_e2');
$xtreampiCanEditEnigma = Authorization::check('adv', 'edit_e2');
$xtreampiDefaultEntries = intval($rSettings['default_entries'] ?? 25);
if (!in_array($xtreampiDefaultEntries, [10, 25, 50, 100], true)) $xtreampiDefaultEntries = 25;
?>
<section class="sc-devices" data-sc-enigmas data-endpoint="table" data-default-entries="<?php echo $xtreampiDefaultEntries; ?>" data-can-edit="<?php echo $xtreampiCanEditEnigma ? '1' : '0'; ?>">
	<div class="sc-page-heading"><div><p class="sc-eyebrow">Devices</p><h1>Enigma2 Devices</h1></div><div class="sc-page-actions"><?php if ($xtreampiCanAddEnigma): ?><a class="sc-button sc-button-primary" href="enigma"><i class="fe-plus" aria-hidden="true"></i> Add device</a><?php endif; ?></div></div>
	<div class="sc-toolbar"><label class="sc-search-field"><i class="fe-search" aria-hidden="true"></i><span class="sc-visually-hidden">Search Enigma2 devices</span><input type="search" placeholder="Search user, MAC, IP, or expiration" data-sc-enigma-search></label><label class="sc-filter-field"><span>Status</span><select data-sc-enigma-filter><option value="">All devices</option><option value="1">Active</option><option value="2">Disabled</option><option value="3">Banned</option><option value="4">Expired</option><option value="5">Trial</option></select></label><label class="sc-filter-field sc-entry-field"><span>Per page</span><select data-sc-enigma-entries><?php foreach ([10,25,50,100] as $entries): ?><option value="<?php echo $entries; ?>"<?php echo $entries === $xtreampiDefaultEntries ? ' selected' : ''; ?>><?php echo $entries; ?></option><?php endforeach; ?></select></label></div>
	<div class="sc-data-panel"><div class="sc-table-scroll"><table class="sc-data-table"><thead><tr><th>MAC address</th><th>Public IP</th><th>Owner</th><th>Status</th><th>Online</th><th>Trial</th><th>Expiration</th><th>Last connection</th><th></th></tr></thead><tbody data-sc-enigma-rows><tr><td class="sc-table-state" colspan="9">Loading devices…</td></tr></tbody></table></div><footer class="sc-table-footer"><span data-sc-enigma-range>Loading…</span><div class="sc-pagination"><button type="button" data-sc-enigma-previous>Previous</button><span data-sc-enigma-page>Page 1</span><button type="button" data-sc-enigma-next>Next</button></div></footer></div>
</section>
