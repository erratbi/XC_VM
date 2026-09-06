<?php

use XcVm\Core\Auth\Authorization;
use XcVm\Domain\Server\ServerRepository;
use XcVm\Domain\Stream\CategoryService;

$xtreampiPageScripts = ['assets/xtreampi/radios.js'];
$xtreampiCanAdd = Authorization::check('adv', 'add_radio');
$xtreampiCanEdit = Authorization::check('adv', 'edit_radio');
$xtreampiCanMassEdit = Authorization::check('adv', 'mass_edit_radio');
$xtreampiCanConnections = Authorization::check('adv', 'live_connections');
$xtreampiDefaultEntries = (int) ($rSettings['default_entries'] ?? 25);
if (!in_array($xtreampiDefaultEntries, [10, 25, 50, 100], true)) $xtreampiDefaultEntries = 25;
?>
<section class="sc-radios" data-sc-radios data-endpoint="table" data-can-edit="<?php echo $xtreampiCanEdit ? '1' : '0'; ?>" data-can-connections="<?php echo $xtreampiCanConnections ? '1' : '0'; ?>" data-show-images="<?php echo !empty($rSettings['show_images']) ? '1' : '0'; ?>">
    <div class="sc-page-heading"><div><p class="sc-eyebrow">Content management</p><h1>Radio</h1></div><div class="sc-page-actions"><?php if ($xtreampiCanMassEdit): ?><a class="sc-button sc-button-secondary" href="radio_mass"><i class="fe-edit-3" aria-hidden="true"></i> Mass edit</a><?php endif; ?><?php if ($xtreampiCanAdd): ?><a class="sc-button sc-button-primary" href="radio"><i class="fe-plus" aria-hidden="true"></i> Add station</a><?php endif; ?></div></div>
    <div class="sc-toolbar">
        <label class="sc-search-field"><i class="fe-search" aria-hidden="true"></i><span class="sc-visually-hidden">Search radio stations</span><input type="search" placeholder="Search station name, ID, or source" data-sc-radio-search></label>
        <label class="sc-filter-field"><span>Server</span><select data-sc-radio-server><option value="">All servers</option><option value="-1">No server</option><?php foreach (ServerRepository::getStreamingSimple($rPermissions) as $xtreampiServer): ?><option value="<?php echo (int) $xtreampiServer['id']; ?>"><?php echo htmlspecialchars((string) $xtreampiServer['server_name'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></label>
        <label class="sc-filter-field"><span>Category</span><select data-sc-radio-category><option value="">All categories</option><option value="-1">Uncategorised</option><?php foreach (CategoryService::getAllByType('radio') as $xtreampiCategory): ?><option value="<?php echo (int) $xtreampiCategory['id']; ?>"><?php echo htmlspecialchars((string) $xtreampiCategory['category_name'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></label>
        <label class="sc-filter-field"><span>Status</span><select data-sc-radio-status><option value="">All statuses</option><option value="1">Online</option><option value="2">Starting</option><option value="3">Stopped</option><option value="5">On demand</option><option value="6">Direct source</option></select></label>
        <label class="sc-filter-field sc-entry-field"><span>Per page</span><select data-sc-radio-entries><?php foreach ([10, 25, 50, 100] as $xtreampiEntries): ?><option value="<?php echo $xtreampiEntries; ?>"<?php echo $xtreampiEntries === $xtreampiDefaultEntries ? ' selected' : ''; ?>><?php echo $xtreampiEntries; ?></option><?php endforeach; ?></select></label>
    </div>
    <div class="sc-data-panel"><div class="sc-table-scroll"><table class="sc-data-table sc-radios-table"><thead><tr><th>Station</th><th>Server</th><th>Status</th><th>Clients</th><th>Audio</th><th class="sc-col-actions"><span class="sc-visually-hidden">Actions</span></th></tr></thead><tbody data-sc-radio-rows><tr><td class="sc-table-state" colspan="6">Loading radio stations…</td></tr></tbody></table></div><footer class="sc-table-footer"><span data-sc-radio-range>Loading…</span><div class="sc-pagination"><button type="button" data-sc-radio-previous>Previous</button><span data-sc-radio-page>Page 1</span><button type="button" data-sc-radio-next>Next</button></div></footer></div>
</section>
