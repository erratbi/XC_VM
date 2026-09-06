<?php

require_once __DIR__ . '/_migration_helpers.php';

$xtreampiPageScripts = ['assets/xtreampi/quick-tools.js'];
$xtreampiQuickGroups = [
    'Streams / VOD' => ['restart_all_streams', 'restart_online_streams', 'start_offline_streams', 'stop_online_streams', 'stop_down_streams', 'restart_down_streams', 'symlink_all_movies', 'symlink_all_episodes', 'recreate_channels', 'delete_duplicates', 'replace_movie_years', 'replace_series_years', 'check_compatibility', 'rescan_vod', 'add_tmdb_ids', 'restore_images'],
    'Lines' => ['remove_expired', 'remove_trial', 'remove_expired_trial', 'remove_null_lines', 'enable_isp', 'disable_isp', 'flush_isp'],
    'MAG' => ['remove_expired_mag', 'remove_trial_mag', 'remove_expired_trial_mag', 'flush_isp_mag', 'enable_isp_mag', 'disable_isp_mag', 'enable_mag_lock', 'disable_mag_lock', 'clear_mag_lock', 'purge_unlinked_lines_mag', 'update_ratings'],
    'Enigma2' => ['remove_expired_e2', 'remove_trial_e2', 'remove_expired_trial_e2', 'flush_isp_e2', 'enable_isp_e2', 'disable_isp_e2', 'purge_unlinked_lines_e2'],
    'Logs' => ['clear_activity_logs', 'clear_client_logs', 'clear_credit_logs', 'clear_login_flood', 'clear_login_logs', 'clear_mag_events', 'clear_panel_logs', 'clear_stream_errors', 'clear_stream_logs', 'clear_user_logs', 'clear_watch_logs'],
    'General' => ['block_trial_lines', 'unblock_trial_lines', 'reauthorise_mysql', 'flush_blocked_ips', 'flush_blocked_isps', 'flush_blocked_uas', 'flush_country_lock', 'force_epg_update', 'cleanup_streams', 'force_update_movies', 'force_update_series', 'force_update_episodes'],
    'ASNs' => ['block_all_isps', 'unblock_all_isps', 'block_all_servers', 'unblock_all_servers', 'block_all_education', 'unblock_all_education', 'block_all_businesses', 'unblock_all_businesses', 'flush_blocked_asns'],
];
?>
<section class="sc-quick-tools" data-sc-quick-tools>
    <div class="sc-page-heading"><div><p class="sc-eyebrow">Operations</p><h1>Quick tools</h1><p class="sc-section-copy">Every action is explicit, confirmed, and sent as the clicked legacy submit name. Nothing runs on page load.</p></div></div>
    <form class="sc-form" action="post.php?action=quick_tools" method="post" data-sc-quick-form>
        <?php foreach ($xtreampiQuickGroups as $xtreampiGroup => $xtreampiActions): ?>
            <section class="sc-form-section"><h2><?php echo sc_m_escape($xtreampiGroup); ?></h2><div class="sc-tool-grid">
                <?php foreach ($xtreampiActions as $xtreampiAction): ?>
                    <button type="submit" class="sc-button sc-button-secondary" name="<?php echo sc_m_escape($xtreampiAction); ?>" value="Run"><?php echo sc_m_escape(ucwords(str_replace('_', ' ', $xtreampiAction))); ?></button>
                <?php endforeach; ?>
            </div></section>
        <?php endforeach; ?>
        <div class="sc-form-error" data-sc-quick-error hidden></div><p data-sc-quick-result role="status"></p>
    </form>
</section>
