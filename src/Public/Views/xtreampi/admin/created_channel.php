<?php
use XcVm\Domain\Bouquet\BouquetService;
use XcVm\Domain\Vod\SeriesService;

require_once __DIR__ . '/_migration_helpers.php';
$editing = is_array($rChannel ?? null);
$row = $editing ? $rChannel : [];
$categories = (array) ($rCategories ?? []);
$profiles = (array) ($rTranscodeProfiles ?? []);
$servers = (array) ($rServerTree ?? []);
$properties = json_decode((string) ($row['movie_properties'] ?? '{}'), true);
$properties = is_array($properties) ? $properties : [];
$channelType = (int) ($properties['type'] ?? 0);
$selectedCategories = json_decode((string) ($row['category_id'] ?? '[]'), true);
$selectedCategories = is_array($selectedCategories) ? array_map('intval', $selectedCategories) : [];
$videoFiles = json_decode((string) ($row['stream_source'] ?? '[]'), true);
$videoFiles = is_array($videoFiles) ? array_map('strval', $videoFiles) : [];
$selectedBouquets = [];
if ($editing) {
    foreach ((array) BouquetService::getAllSimple() as $bouquet) {
        $channels = json_decode((string) ($bouquet['bouquet_channels'] ?? '[]'), true);
        if (is_array($channels) && in_array((int) ($row['id'] ?? 0), array_map('intval', $channels), true)) {
            $selectedBouquets[] = (int) ($bouquet['id'] ?? 0);
        }
    }
}
$series = (array) SeriesService::getAll();
$systemRows = is_array($rChannelSys ?? null) ? $rChannelSys : [];
?>
<section class="sc-migration-editor" data-sc-created-channel>
    <div class="sc-page-heading"><div><p class="sc-eyebrow">Content management</p><h1><?php echo $editing ? 'Edit created channel' : 'Add created channel'; ?></h1></div><div class="sc-page-actions"><a class="sc-button sc-button-secondary" href="created_channels">Back to channels</a></div></div>
    <form class="sc-form" action="post.php?action=created_channel&amp;referer=created_channels" method="post">
        <input type="hidden" name="server_tree_data" value="<?php echo sc_m_escape(json_encode($servers)); ?>" data-sc-server-tree>
        <input type="hidden" name="video_files" value="<?php echo sc_m_escape(json_encode($videoFiles)); ?>" data-sc-video-files>
        <input type="hidden" name="category_id[]" value="">
        <input type="hidden" name="bouquets[]" value="">
        <input type="hidden" name="category_create_list" value="[]">
        <input type="hidden" name="bouquet_create_list" value="[]">
        <?php if ($editing): ?><input type="hidden" name="edit" value="<?php echo (int) ($row['id'] ?? 0); ?>"><?php endif; ?>
        <section class="sc-form-section"><h2>Channel details</h2><div class="sc-form-grid">
            <label>Channel name<input required name="stream_display_name" value="<?php echo sc_m_escape($row['stream_display_name'] ?? ''); ?>"></label>
            <label>Icon URL<input type="url" name="stream_icon" value="<?php echo sc_m_escape($row['stream_icon'] ?? ''); ?>"></label>
            <label>Selection type<select name="channel_type"><option value="0"<?php echo $channelType === 0 ? ' selected' : ''; ?>>Series</option><option value="1"<?php echo $channelType === 1 ? ' selected' : ''; ?>>File Browser</option><option value="2"<?php echo $channelType === 2 ? ' selected' : ''; ?>>VOD selection</option></select></label>
            <label>24/7 Series<select name="series_no"><option value="0">Select a series</option><?php foreach ($series as $item): ?><option value="<?php echo (int) ($item['id'] ?? 0); ?>"<?php echo (int) ($row['series_no'] ?? 0) === (int) ($item['id'] ?? 0) ? ' selected' : ''; ?>><?php echo sc_m_escape($item['title'] ?? ''); ?></option><?php endforeach; ?></select></label>
            <label>Transcode profile<select name="transcode_profile_id"><option value="0"<?php echo (int) ($row['transcode_profile_id'] ?? 0) === 0 ? ' selected' : ''; ?>>Copy codecs</option><option value="-1"<?php echo (int) ($row['transcode_profile_id'] ?? 0) === -1 ? ' selected' : ''; ?>>Symlink files</option><?php foreach ($profiles as $profile): $id = $profile['profile_id'] ?? ($profile['id'] ?? 0); ?><option value="<?php echo (int) $id; ?>"<?php echo (int) ($row['transcode_profile_id'] ?? 0) === (int) $id ? ' selected' : ''; ?>><?php echo sc_m_escape($profile['profile_name'] ?? ($profile['name'] ?? '')); ?></option><?php endforeach; ?></select></label>
            <label>Custom channel SID<input name="custom_sid" value="<?php echo sc_m_escape($row['custom_sid'] ?? ''); ?>"></label>
            <label class="sc-form-span">Notes<textarea name="notes" rows="4"><?php echo sc_m_escape($row['notes'] ?? ''); ?></textarea></label>
        </div></section>
        <section class="sc-form-section"><h2>Source entries</h2><p class="sc-section-copy">For File Browser or VOD selection, enter one legacy source value per line (for example <code>s:1:/media/movie.mp4</code>). Series channels use the selected 24/7 series.</p><label>Video files / VOD sources<textarea rows="5" data-sc-video-files-input><?php echo sc_m_escape(implode("\n", $videoFiles)); ?></textarea></label></section>
        <section class="sc-form-section"><h2>Categories and bouquets</h2><div class="sc-selection-grid">
            <?php foreach ($categories as $category): ?><label class="sc-selection-card"><input type="checkbox" name="category_id[]" value="<?php echo (int) ($category['id'] ?? 0); ?>"<?php echo in_array((int) ($category['id'] ?? 0), $selectedCategories, true) ? ' checked' : ''; ?>><span><?php echo sc_m_escape($category['category_name'] ?? ''); ?></span></label><?php endforeach; ?>
            <?php foreach ((array) BouquetService::getAllSimple() as $bouquet): ?><label class="sc-selection-card"><input type="checkbox" name="bouquets[]" value="<?php echo (int) ($bouquet['id'] ?? 0); ?>"<?php echo in_array((int) ($bouquet['id'] ?? 0), $selectedBouquets, true) ? ' checked' : ''; ?>><span><?php echo sc_m_escape($bouquet['bouquet_name'] ?? ''); ?></span></label><?php endforeach; ?>
        </div></section>
        <section class="sc-form-section"><h2>Source servers</h2><p class="sc-section-copy">Select online source servers. A selected server can also be marked on-demand.</p><div class="sc-selection-grid" data-sc-server-selection><?php foreach ($servers as $server): if (($server['parent'] ?? '#') === '#') continue; $serverId = (string) ($server['id'] ?? 0); $serverParent = (string) ($server['parent'] ?? 'offline'); $selected = $editing && $serverParent !== 'offline'; $onDemand = !empty($systemRows[(int) $serverId]['on_demand']); ?><label class="sc-selection-card"><input type="checkbox" value="<?php echo sc_m_escape($serverId); ?>" data-parent="<?php echo sc_m_escape($editing ? $serverParent : 'source'); ?>" data-sc-server<?php echo $selected ? ' checked' : ''; ?>><span><?php echo sc_m_escape(strip_tags((string) ($server['text'] ?? ''))); ?></span><small><span class="sc-inline-control"><input type="checkbox" name="on_demand[]" value="<?php echo sc_m_escape($serverId); ?>" data-sc-on-demand<?php echo $onDemand ? ' checked' : ''; ?>> on-demand</span></small></label><?php endforeach; ?></div></section>
        <section class="sc-form-section"><div class="sc-form-grid"><label class="sc-check"><input type="checkbox" name="rtmp_output" value="1"<?php echo !empty($row['rtmp_output']) ? ' checked' : ''; ?>><span>RTMP output</span></label><label class="sc-check"><input type="checkbox" name="allow_record" value="1"<?php echo (!$editing || !empty($row['allow_record'])) ? ' checked' : ''; ?>><span>Allow recording</span></label><label class="sc-check"><input type="checkbox" name="reencode_on_edit" value="1"><span>Re-encode on save</span></label><label class="sc-check"><input type="checkbox" name="restart_on_edit" value="1"><span>Restart on save</span></label></div></section>
        <div class="sc-form-error" data-sc-form-error hidden></div><div class="sc-form-actions"><button class="sc-button sc-button-primary" type="submit" name="submit_stream" value="<?php echo $editing ? 'Edit Channel' : 'Add Channel'; ?>">Save channel</button><a class="sc-button sc-button-secondary" href="created_channels">Cancel</a></div>
    </form>
</section>
<?php $xtreampiPageScripts = ['assets/xtreampi/created-channel.js', 'assets/xtreampi/migration.js']; ?>
