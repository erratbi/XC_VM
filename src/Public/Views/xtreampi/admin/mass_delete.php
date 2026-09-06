<?php

require_once __DIR__ . '/_migration_helpers.php';

$xtreampiPageScripts = ['assets/xtreampi/mass-delete.js'];
$xtreampiMassDelete = [
    'streams' => ['stream_list', 'mass_delete_streams', 'streams', 'Streams'],
    'movies' => ['movie_list', 'mass_delete_movies', 'movies', 'Movies'],
    'radios' => ['radio_list', 'mass_delete_radios', 'radios', 'Radio stations'],
    'series' => ['series_list', 'mass_delete_series', 'series', 'Series'],
    'episodes' => ['episode_list', 'mass_delete_episodes', 'episodes', 'Episodes'],
    'lines' => ['lines', 'mass_delete_lines', 'lines', 'Lines'],
    'users' => ['reg_users', 'mass_delete_users', 'users', 'Users'],
    'mags' => ['mags', 'mass_delete_mags', 'mags', 'MAG devices'],
    'enigmas' => ['enigmas', 'mass_delete_enigmas', 'enigmas', 'Enigma2 devices'],
];
?>
<section class="sc-mass-delete" data-sc-mass-delete>
    <div class="sc-page-heading"><div><p class="sc-eyebrow">High-impact operations</p><h1>Mass delete</h1><p class="sc-section-copy">Load a filtered table, select exact IDs, then confirm. Empty selections are rejected and never sent.</p></div></div>
    <?php foreach ($xtreampiMassDelete as $xtreampiKey => [$xtreampiTable, $xtreampiAction, $xtreampiField, $xtreampiTitle]): ?>
        <section class="sc-form-section" data-sc-delete-group data-table-id="<?php echo sc_m_escape($xtreampiTable); ?>" data-delete-action="<?php echo sc_m_escape($xtreampiAction); ?>" data-selected-field="<?php echo sc_m_escape($xtreampiField); ?>">
            <h2><?php echo sc_m_escape($xtreampiTitle); ?></h2>
            <div class="sc-toolbar"><label class="sc-search-field"><span class="sc-visually-hidden">Search</span><input type="search" data-sc-delete-search placeholder="Search <?php echo sc_m_escape(strtolower($xtreampiTitle)); ?>"></label><label class="sc-filter-field"><span>Server</span><input type="number" data-sc-delete-filter="server" min="1"></label><label class="sc-filter-field"><span>Category</span><input type="number" data-sc-delete-filter="category" min="1"></label><label class="sc-filter-field"><span>Owner</span><input type="number" data-sc-delete-filter="reseller" min="1"></label><button type="button" class="sc-button sc-button-secondary" data-sc-delete-load>Load records</button><span data-sc-delete-count>0 selected</span></div>
            <form class="sc-form" method="post" action="post.php?action=<?php echo sc_m_escape($xtreampiAction); ?>" data-sc-delete-form>
                <input type="hidden" name="<?php echo sc_m_escape($xtreampiField); ?>" value="[]" data-sc-delete-payload>
                <div class="sc-table-scroll"><table class="sc-data-table"><thead><tr><th>Select</th><th>ID</th><th>Name / identity</th><th>Status</th></tr></thead><tbody data-sc-delete-body><tr><td class="sc-table-state" colspan="4">Press Load records.</td></tr></tbody></table></div>
                <div class="sc-form-actions"><button type="submit" class="sc-button sc-button-danger" name="<?php echo sc_m_escape($xtreampiField === 'radios' ? 'submit_streams' : 'submit_' . $xtreampiField); ?>" value="Delete selected">Delete selected</button></div>
                <div class="sc-form-error" data-sc-delete-error hidden></div>
            </form>
        </section>
    <?php endforeach; ?>
</section>
