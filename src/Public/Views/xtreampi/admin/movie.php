<?php

use XcVm\Core\Http\RequestManager;
use XcVm\Domain\Bouquet\BouquetService;
use XcVm\Domain\Server\ServerRepository;

$xtreampiPageScripts = ['assets/xtreampi/stream.js', 'assets/xtreampi/movie.js', 'assets/xtreampi/movie-browser.js'];
$xtreampiMovie = is_array($rMovie ?? null) ? $rMovie : [];
$xtreampiMovieProperties = is_array($xtreampiMovie['properties'] ?? null) ? $xtreampiMovie['properties'] : [];
$xtreampiMovieEditing = intval($xtreampiMovie['id'] ?? 0) > 0;
$xtreampiMovieImporting = isset(RequestManager::getAll()['import']);
$xtreampiMovieCategories = array_map('intval', json_decode((string) ($xtreampiMovie['category_id'] ?? '[]'), true) ?: []);
$xtreampiMovieBouquets = BouquetService::getAllSimple();
$xtreampiMovieServers = ServerRepository::getStreamingSimple($rPermissions, 'all');
$xtreampiMovieSelectedBouquets = [];
foreach ($xtreampiMovieBouquets as $xtreampiMovieBouquet) {
    if ($xtreampiMovieEditing && in_array(intval($xtreampiMovie['id']), array_map('intval', json_decode((string) ($xtreampiMovieBouquet['bouquet_movies'] ?? '[]'), true) ?: []), true)) {
        $xtreampiMovieSelectedBouquets[] = intval($xtreampiMovieBouquet['id']);
    }
}
$xtreampiMovieParents = [];
foreach ($rServerTree as $xtreampiMovieTreeNode) {
    if (ctype_digit((string) ($xtreampiMovieTreeNode['id'] ?? ''))) {
        $xtreampiMovieParents[(int) $xtreampiMovieTreeNode['id']] = (string) ($xtreampiMovieTreeNode['parent'] ?? 'offline');
    }
}
$xtreampiMovieValue = static function (string $key, string $default = '') use ($xtreampiMovie): string {
    return htmlspecialchars((string) ($xtreampiMovie[$key] ?? $default), ENT_QUOTES, 'UTF-8');
};
$xtreampiMovieProperty = static function (string $key, string $default = '') use ($xtreampiMovieProperties): string {
    return htmlspecialchars((string) ($xtreampiMovieProperties[$key] ?? $default), ENT_QUOTES, 'UTF-8');
};
$xtreampiMovieRenderPicker = static function (string $label, string $name, string $createField, array $items, array $selected): void {
    $xtreampiMoviePickerMap = [];
    foreach ($items as $xtreampiMoviePickerItem) {
        $xtreampiMoviePickerMap[(int) $xtreampiMoviePickerItem['id']] = (string) $xtreampiMoviePickerItem['text'];
    }
    ?>
    <div class="sc-static-picker" data-static-picker data-name="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>" data-create-field="<?php echo htmlspecialchars($createField, ENT_QUOTES, 'UTF-8'); ?>" data-create-label="<?php echo htmlspecialchars(rtrim($label, 's'), ENT_QUOTES, 'UTF-8'); ?>">
        <script type="application/json" data-static-items><?php echo json_encode($items, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>
        <label><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?><input type="search" placeholder="Search or create <?php echo strtolower(htmlspecialchars(rtrim($label, 's'), ENT_QUOTES, 'UTF-8')); ?>" autocomplete="off" data-static-search></label>
        <div class="sc-owner-options" data-static-options hidden></div>
        <div class="sc-token-list" data-static-values><?php foreach ($selected as $xtreampiMoviePickerId): if (!isset($xtreampiMoviePickerMap[(int) $xtreampiMoviePickerId])) continue; ?><span><?php echo htmlspecialchars($xtreampiMoviePickerMap[(int) $xtreampiMoviePickerId], ENT_QUOTES, 'UTF-8'); ?><input type="hidden" name="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo intval($xtreampiMoviePickerId); ?>"><button type="button" aria-label="Remove">×</button></span><?php endforeach; ?></div>
        <p data-static-empty<?php echo $selected ? ' hidden' : ''; ?>>Nothing selected.</p>
    </div>
    <?php
};
$xtreampiMovieCategoryOptions = array_map(static fn(array $item): array => ['id' => intval($item['id']), 'text' => (string) $item['category_name']], $rCategories);
$xtreampiMovieBouquetOptions = array_map(static fn(array $item): array => ['id' => intval($item['id']), 'text' => (string) $item['bouquet_name']], $xtreampiMovieBouquets);
$xtreampiMovieSource = $xtreampiMovieImporting ? '' : (string) ($rPathSources ?? '');
$xtreampiMovieSubtitle = '';
if (!empty($xtreampiMovie['movie_subtitles'])) {
    $xtreampiMovieSubtitleData = json_decode((string) $xtreampiMovie['movie_subtitles'], true);
    if (!empty($xtreampiMovieSubtitleData['location']) && !empty($xtreampiMovieSubtitleData['files'][0])) {
        $xtreampiMovieSubtitle = 's:' . $xtreampiMovieSubtitleData['location'] . ':' . $xtreampiMovieSubtitleData['files'][0];
    }
}
$xtreampiHasTmdb = !$xtreampiMovieImporting && !empty($rSettings['tmdb_api_key']);
?>
<section class="sc-movie-editor" data-sc-stream-editor data-sc-movie-editor>
    <div class="sc-page-heading">
        <div><p class="sc-eyebrow">Content management</p><h1><?php echo $xtreampiMovieImporting ? 'Import movies' : ($xtreampiMovieEditing ? 'Edit movie' : 'Add movie'); ?></h1></div>
        <div class="sc-page-actions"><a class="sc-button sc-button-secondary" href="movies"><i class="fe-chevron-left" aria-hidden="true"></i> Back to movies</a></div>
    </div>

    <form class="sc-form sc-line-form" action="post.php?action=movie&amp;referer=movies" method="post" enctype="multipart/form-data">
        <?php if ($xtreampiMovieEditing): ?><input type="hidden" name="edit" value="<?php echo intval($xtreampiMovie['id']); ?>"><?php endif; ?>
        <input type="hidden" name="tmdb_id" value="<?php echo htmlspecialchars((string) ($xtreampiMovie['tmdb_id'] ?? $xtreampiMovieProperties['tmdb_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="server_tree_data" value="">
        <input type="hidden" name="external_push" value="{}">
        <input type="hidden" name="category_create_list" value="[]">
        <input type="hidden" name="bouquet_create_list" value="[]">

        <?php if ($xtreampiMovieImporting): ?>
            <section class="sc-form-section" data-sc-movie-import><h2>Import source</h2><p class="sc-section-copy">Import an M3U playlist or scan a server folder. Categories and bouquets below are used when Watch Folder does not supply them.</p><div class="sc-form-grid">
                <div class="sc-import-source-choice sc-form-span"><label><input type="radio" name="movie_import_mode" value="m3u" checked data-sc-import-mode> M3U playlist</label><label><input type="radio" name="movie_import_mode" value="folder" data-sc-import-mode> Server folder</label></div>
                <label class="sc-form-span" data-sc-import-m3u>M3U playlist<input type="file" name="m3u_file" accept=".m3u"></label>
                <label class="sc-form-span" data-sc-import-folder hidden>Folder path<span class="sc-input-action"><input type="text" id="import_folder" name="import_folder" value="<?php echo htmlspecialchars($xtreampiMovieSource, ENT_QUOTES, 'UTF-8'); ?>" placeholder="s:server_id:/path/to/movies"><button class="sc-button sc-button-secondary" type="button" data-sc-file-browser-open data-sc-file-target="import_folder" data-sc-file-filter="video" data-sc-file-mode="directory"><i class="fe-folder" aria-hidden="true"></i> Browse</button></span></label>
                <label class="sc-selection-card" data-sc-import-folder hidden><input type="checkbox" name="scan_recursive"><span><strong>Scan recursively</strong><small>Include movies in subfolders.</small></span></label>
                <label class="sc-selection-card"><input type="checkbox" name="disable_tmdb"><span><strong>Disable TMDB matching</strong><small>Keep imported titles and metadata unchanged.</small></span></label>
                <label class="sc-selection-card"><input type="checkbox" name="ignore_no_match"><span><strong>Keep unmatched movies</strong><small>Add entries even when metadata cannot be matched.</small></span></label>
            </div><div class="sc-form-grid sc-import-fallbacks">
                <?php $xtreampiMovieRenderPicker('Fallback categories', 'category_id[]', 'category_create_list', $xtreampiMovieCategoryOptions, []); ?>
                <?php $xtreampiMovieRenderPicker('Fallback bouquets', 'bouquets[]', 'bouquet_create_list', $xtreampiMovieBouquetOptions, []); ?>
            </div></section>
        <?php else: ?>
            <section class="sc-form-section"><h2>Movie details</h2><div class="sc-form-grid">
                <?php if ($xtreampiHasTmdb): ?>
                    <div class="sc-tmdb-autocomplete" data-sc-tmdb data-sc-tmdb-language="<?php echo htmlspecialchars((string) ($rSettings['tmdb_language'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        <label>Movie name<span class="sc-tmdb-input-wrap"><input type="text" id="stream_display_name" name="stream_display_name" required autocomplete="off" data-sc-tmdb-query value="<?php echo $xtreampiMovieValue('stream_display_name', (string) (RequestManager::getAll()['title'] ?? '')); ?>"><button class="sc-tmdb-result-count" type="button" data-sc-tmdb-toggle hidden><span data-sc-tmdb-count></span><i class="fe-chevron-down" aria-hidden="true"></i></button></span></label>
                        <div class="sc-tmdb-results" data-sc-tmdb-results hidden></div>
                    </div>
                <?php else: ?>
                    <label>Movie name<input type="text" id="stream_display_name" name="stream_display_name" required value="<?php echo $xtreampiMovieValue('stream_display_name', (string) (RequestManager::getAll()['title'] ?? '')); ?>"></label>
                <?php endif; ?>
                <label>Year<input type="text" id="year" name="year" inputmode="numeric" value="<?php echo $xtreampiMovieValue('year'); ?>"></label>
                <label class="sc-form-span">Movie path or URL<span class="sc-input-action"><input type="text" id="stream_source" name="stream_source" required value="<?php echo htmlspecialchars($xtreampiMovieSource ?: (string) (RequestManager::getAll()['path'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="https://example.com/movie.mkv or /mnt/movies/movie.mkv"><button class="sc-button sc-button-secondary" type="button" data-sc-file-browser-open data-sc-file-target="stream_source" data-sc-file-filter="video"><i class="fe-folder" aria-hidden="true"></i> Browse</button></span></label>
                <?php $xtreampiMovieRenderPicker('Categories', 'category_id[]', 'category_create_list', $xtreampiMovieCategoryOptions, $xtreampiMovieCategories); ?>
                <?php $xtreampiMovieRenderPicker('Bouquets', 'bouquets[]', 'bouquet_create_list', $xtreampiMovieBouquetOptions, $xtreampiMovieSelectedBouquets); ?>
            </div></section>

            <section class="sc-form-section"><h2>Metadata</h2>
                <div class="sc-form-grid">
                <label>Poster URL<input type="url" name="movie_image" value="<?php echo $xtreampiMovieProperty('movie_image'); ?>"></label>
                <label>Backdrop URL<input type="url" name="backdrop_path" value="<?php echo htmlspecialchars((string) ($xtreampiMovieProperties['backdrop_path'][0] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></label>
                <label class="sc-form-span">Plot<textarea name="plot" rows="5"><?php echo $xtreampiMovieProperty('plot'); ?></textarea></label>
                <label>Cast<input type="text" name="cast" value="<?php echo $xtreampiMovieProperty('cast'); ?>"></label>
                <label>Director<input type="text" name="director" value="<?php echo $xtreampiMovieProperty('director'); ?>"></label>
                <label>Genres<input type="text" name="genre" value="<?php echo $xtreampiMovieProperty('genre'); ?>"></label>
                <label>Country<input type="text" name="country" value="<?php echo $xtreampiMovieProperty('country'); ?>"></label>
                <label>Release date<input type="text" name="release_date" value="<?php echo $xtreampiMovieProperty('release_date'); ?>"></label>
                <label>Runtime<input type="text" name="episode_run_time" value="<?php echo $xtreampiMovieProperty('episode_run_time'); ?>"></label>
                <label>YouTube trailer<input type="text" name="youtube_trailer" value="<?php echo $xtreampiMovieProperty('youtube_trailer'); ?>"></label>
                <label>Rating<input type="number" name="rating" min="0" max="10" step="0.1" value="<?php echo $xtreampiMovieProperty('rating'); ?>"></label>
            </div></section>
        <?php endif; ?>

        <section class="sc-form-section"><h2>Processing</h2><div class="sc-form-grid">
            <label>Transcode profile<select id="transcode_profile_id" name="transcode_profile_id"><option value="0">Disabled</option><?php foreach ($rTranscodeProfiles as $xtreampiMovieProfile): ?><option value="<?php echo intval($xtreampiMovieProfile['profile_id']); ?>"<?php echo intval($xtreampiMovie['transcode_profile_id'] ?? 0) === intval($xtreampiMovieProfile['profile_id']) ? ' selected' : ''; ?>><?php echo htmlspecialchars((string) $xtreampiMovieProfile['profile_name'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></label>
            <label>Target container<select id="target_container" name="target_container"><?php foreach (['mp4', 'mkv', 'avi', 'mpg', 'flv', '3gp', 'm4v', 'wmv', 'mov', 'ts'] as $xtreampiMovieContainer): ?><option value="<?php echo $xtreampiMovieContainer; ?>"<?php echo ($xtreampiMovie['target_container'] ?? 'mp4') === $xtreampiMovieContainer ? ' selected' : ''; ?>><?php echo $xtreampiMovieContainer; ?></option><?php endforeach; ?></select></label>
            <?php if (!$xtreampiMovieImporting): ?><label class="sc-form-span">Subtitle location<span class="sc-input-action"><input type="text" id="movie_subtitles" name="movie_subtitles" value="<?php echo htmlspecialchars($xtreampiMovieSubtitle, ENT_QUOTES, 'UTF-8'); ?>" placeholder="s:server_id:subtitle.srt"><button class="sc-button sc-button-secondary" type="button" data-sc-file-browser-open data-sc-file-target="movie_subtitles" data-sc-file-filter="subs"><i class="fe-folder" aria-hidden="true"></i> Browse</button></span></label><?php endif; ?>
        </div><div class="sc-package-options sc-line-switches">
            <?php foreach ([['direct_source', 'Direct source'], ['direct_proxy', 'Direct stream'], ['read_native', 'Native frames'], ['movie_symlink', 'Create symlink'], ['remove_subtitles', 'Remove existing subtitles']] as [$xtreampiMovieSwitch, $xtreampiMovieSwitchLabel]): ?><label class="sc-selection-card"><input id="<?php echo $xtreampiMovieSwitch; ?>" type="checkbox" name="<?php echo $xtreampiMovieSwitch; ?>"<?php echo !empty($xtreampiMovie[$xtreampiMovieSwitch]) ? ' checked' : ''; ?>><span><strong><?php echo $xtreampiMovieSwitchLabel; ?></strong></span></label><?php endforeach; ?>
        </div></section>

        <section class="sc-form-section"><h2>Server placement</h2><p class="sc-section-copy">Select the server that processes the source, then optionally use it as the upstream source for other servers.</p><div class="sc-server-placement" data-server-placement>
            <?php foreach (ServerRepository::getStreamingSimple($rPermissions, 'all') as $xtreampiMovieServer): $xtreampiMovieServerId = intval($xtreampiMovieServer['id']); $xtreampiMovieParent = $xtreampiMovieParents[$xtreampiMovieServerId] ?? 'offline'; ?>
                <div class="sc-server-placement-row" data-server-id="<?php echo $xtreampiMovieServerId; ?>"><strong><?php echo htmlspecialchars((string) $xtreampiMovieServer['server_name'], ENT_QUOTES, 'UTF-8'); ?></strong><label>Upstream<select data-server-parent><option value="offline"<?php echo $xtreampiMovieParent === 'offline' ? ' selected' : ''; ?>>Disabled</option><option value="source"<?php echo $xtreampiMovieParent === 'source' ? ' selected' : ''; ?>>Process source</option><?php foreach (ServerRepository::getStreamingSimple($rPermissions, 'all') as $xtreampiMovieUpstream): if (intval($xtreampiMovieUpstream['id']) === $xtreampiMovieServerId) continue; ?><option value="<?php echo intval($xtreampiMovieUpstream['id']); ?>"<?php echo (string) $xtreampiMovieParent === (string) intval($xtreampiMovieUpstream['id']) ? ' selected' : ''; ?>><?php echo htmlspecialchars((string) $xtreampiMovieUpstream['server_name'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></label></div>
            <?php endforeach; ?>
        </div><label class="sc-selection-card" style="margin-top: 16px;"><input type="checkbox" name="restart_on_edit"><span><strong><?php echo $xtreampiMovieEditing ? 'Reprocess after saving' : 'Process movie now'; ?></strong><small>Queue the selected movie on its assigned servers.</small></span></label></section>

        <div class="sc-form-error" data-stream-error role="alert" hidden></div>
        <div class="sc-form-actions"><button class="sc-button sc-button-primary" type="submit" name="submit_movie" value="<?php echo $xtreampiMovieEditing ? 'Edit' : 'Add'; ?>"><?php echo $xtreampiMovieImporting ? 'Import movies' : ($xtreampiMovieEditing ? 'Save movie' : 'Add movie'); ?></button><a class="sc-button sc-button-secondary" href="movies">Cancel</a></div>
    </form>
</section>

<dialog class="sc-playlist-dialog sc-file-browser-dialog" data-sc-file-browser-dialog>
    <div class="sc-dialog-heading"><div><p class="sc-eyebrow">Server files</p><h2 data-sc-file-browser-title>Browse movie files</h2></div><button class="sc-dialog-close" type="button" data-sc-file-browser-close aria-label="Close"><i class="fe-x" aria-hidden="true"></i></button></div>
    <div class="sc-dialog-body">
        <div class="sc-form-grid"><label>Server<select data-sc-file-browser-server><?php foreach ($xtreampiMovieServers as $xtreampiMovieServer): ?><option value="<?php echo intval($xtreampiMovieServer['id']); ?>"><?php echo htmlspecialchars((string) $xtreampiMovieServer['server_name'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></label><label>Directory<span class="sc-input-action"><input type="text" value="/" data-sc-file-browser-path><button class="sc-button sc-button-secondary" type="button" data-sc-file-browser-load>Open</button></span></label></div>
        <div class="sc-file-browser-columns"><section><h3>Folders</h3><div data-sc-file-browser-dirs class="sc-file-browser-list"></div></section><section><h3 data-sc-file-browser-files-heading>Compatible files</h3><div data-sc-file-browser-files class="sc-file-browser-list"></div></section></div>
    </div>
</dialog>
