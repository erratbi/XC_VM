<?php

use XcVm\Core\Http\RequestManager;
use XcVm\Domain\Bouquet\BouquetService;
use XcVm\Domain\Server\ServerRepository;

$streamcreedPageScripts = ['assets/streamcreed/stream.js', 'assets/streamcreed/movie.js', 'assets/streamcreed/movie-browser.js'];
$streamcreedMovie = is_array($rMovie ?? null) ? $rMovie : [];
$streamcreedMovieProperties = is_array($streamcreedMovie['properties'] ?? null) ? $streamcreedMovie['properties'] : [];
$streamcreedMovieEditing = intval($streamcreedMovie['id'] ?? 0) > 0;
$streamcreedMovieImporting = isset(RequestManager::getAll()['import']);
$streamcreedMovieCategories = array_map('intval', json_decode((string) ($streamcreedMovie['category_id'] ?? '[]'), true) ?: []);
$streamcreedMovieBouquets = BouquetService::getAllSimple();
$streamcreedMovieServers = ServerRepository::getStreamingSimple($rPermissions, 'all');
$streamcreedMovieSelectedBouquets = [];
foreach ($streamcreedMovieBouquets as $streamcreedMovieBouquet) {
    if ($streamcreedMovieEditing && in_array(intval($streamcreedMovie['id']), array_map('intval', json_decode((string) ($streamcreedMovieBouquet['bouquet_movies'] ?? '[]'), true) ?: []), true)) {
        $streamcreedMovieSelectedBouquets[] = intval($streamcreedMovieBouquet['id']);
    }
}
$streamcreedMovieParents = [];
foreach ($rServerTree as $streamcreedMovieTreeNode) {
    if (ctype_digit((string) ($streamcreedMovieTreeNode['id'] ?? ''))) {
        $streamcreedMovieParents[(int) $streamcreedMovieTreeNode['id']] = (string) ($streamcreedMovieTreeNode['parent'] ?? 'offline');
    }
}
$streamcreedMovieValue = static function (string $key, string $default = '') use ($streamcreedMovie): string {
    return htmlspecialchars((string) ($streamcreedMovie[$key] ?? $default), ENT_QUOTES, 'UTF-8');
};
$streamcreedMovieProperty = static function (string $key, string $default = '') use ($streamcreedMovieProperties): string {
    return htmlspecialchars((string) ($streamcreedMovieProperties[$key] ?? $default), ENT_QUOTES, 'UTF-8');
};
$streamcreedMovieRenderPicker = static function (string $label, string $name, string $createField, array $items, array $selected): void {
    $streamcreedMoviePickerMap = [];
    foreach ($items as $streamcreedMoviePickerItem) {
        $streamcreedMoviePickerMap[(int) $streamcreedMoviePickerItem['id']] = (string) $streamcreedMoviePickerItem['text'];
    }
    ?>
    <div class="sc-static-picker" data-static-picker data-name="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>" data-create-field="<?php echo htmlspecialchars($createField, ENT_QUOTES, 'UTF-8'); ?>" data-create-label="<?php echo htmlspecialchars(rtrim($label, 's'), ENT_QUOTES, 'UTF-8'); ?>">
        <script type="application/json" data-static-items><?php echo json_encode($items, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>
        <label><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?><input type="search" placeholder="Search or create <?php echo strtolower(htmlspecialchars(rtrim($label, 's'), ENT_QUOTES, 'UTF-8')); ?>" autocomplete="off" data-static-search></label>
        <div class="sc-owner-options" data-static-options hidden></div>
        <div class="sc-token-list" data-static-values><?php foreach ($selected as $streamcreedMoviePickerId): if (!isset($streamcreedMoviePickerMap[(int) $streamcreedMoviePickerId])) continue; ?><span><?php echo htmlspecialchars($streamcreedMoviePickerMap[(int) $streamcreedMoviePickerId], ENT_QUOTES, 'UTF-8'); ?><input type="hidden" name="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo intval($streamcreedMoviePickerId); ?>"><button type="button" aria-label="Remove">×</button></span><?php endforeach; ?></div>
        <p data-static-empty<?php echo $selected ? ' hidden' : ''; ?>>Nothing selected.</p>
    </div>
    <?php
};
$streamcreedMovieCategoryOptions = array_map(static fn(array $item): array => ['id' => intval($item['id']), 'text' => (string) $item['category_name']], $rCategories);
$streamcreedMovieBouquetOptions = array_map(static fn(array $item): array => ['id' => intval($item['id']), 'text' => (string) $item['bouquet_name']], $streamcreedMovieBouquets);
$streamcreedMovieSource = $streamcreedMovieImporting ? '' : (string) ($rPathSources ?? '');
$streamcreedMovieSubtitle = '';
if (!empty($streamcreedMovie['movie_subtitles'])) {
    $streamcreedMovieSubtitleData = json_decode((string) $streamcreedMovie['movie_subtitles'], true);
    if (!empty($streamcreedMovieSubtitleData['location']) && !empty($streamcreedMovieSubtitleData['files'][0])) {
        $streamcreedMovieSubtitle = 's:' . $streamcreedMovieSubtitleData['location'] . ':' . $streamcreedMovieSubtitleData['files'][0];
    }
}
$streamcreedHasTmdb = !$streamcreedMovieImporting && !empty($rSettings['tmdb_api_key']);
?>
<section class="sc-movie-editor" data-sc-stream-editor data-sc-movie-editor>
    <div class="sc-page-heading">
        <div><p class="sc-eyebrow">Content management</p><h1><?php echo $streamcreedMovieImporting ? 'Import movies' : ($streamcreedMovieEditing ? 'Edit movie' : 'Add movie'); ?></h1></div>
        <div class="sc-page-actions"><a class="sc-button sc-button-secondary" href="movies"><i class="fe-chevron-left" aria-hidden="true"></i> Back to movies</a></div>
    </div>

    <form class="sc-form sc-line-form" action="post.php?action=movie&amp;referer=movies" method="post" enctype="multipart/form-data">
        <?php if ($streamcreedMovieEditing): ?><input type="hidden" name="edit" value="<?php echo intval($streamcreedMovie['id']); ?>"><?php endif; ?>
        <input type="hidden" name="tmdb_id" value="<?php echo htmlspecialchars((string) ($streamcreedMovie['tmdb_id'] ?? $streamcreedMovieProperties['tmdb_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="server_tree_data" value="">
        <input type="hidden" name="external_push" value="{}">
        <input type="hidden" name="category_create_list" value="[]">
        <input type="hidden" name="bouquet_create_list" value="[]">

        <?php if ($streamcreedMovieImporting): ?>
            <section class="sc-form-section" data-sc-movie-import><h2>Import source</h2><p class="sc-section-copy">Import an M3U playlist or scan a server folder. Categories and bouquets below are used when Watch Folder does not supply them.</p><div class="sc-form-grid">
                <div class="sc-import-source-choice sc-form-span"><label><input type="radio" name="movie_import_mode" value="m3u" checked data-sc-import-mode> M3U playlist</label><label><input type="radio" name="movie_import_mode" value="folder" data-sc-import-mode> Server folder</label></div>
                <label class="sc-form-span" data-sc-import-m3u>M3U playlist<input type="file" name="m3u_file" accept=".m3u"></label>
                <label class="sc-form-span" data-sc-import-folder hidden>Folder path<span class="sc-input-action"><input type="text" id="import_folder" name="import_folder" value="<?php echo htmlspecialchars($streamcreedMovieSource, ENT_QUOTES, 'UTF-8'); ?>" placeholder="s:server_id:/path/to/movies"><button class="sc-button sc-button-secondary" type="button" data-sc-file-browser-open data-sc-file-target="import_folder" data-sc-file-filter="video" data-sc-file-mode="directory"><i class="fe-folder" aria-hidden="true"></i> Browse</button></span></label>
                <label class="sc-selection-card" data-sc-import-folder hidden><input type="checkbox" name="scan_recursive"><span><strong>Scan recursively</strong><small>Include movies in subfolders.</small></span></label>
                <label class="sc-selection-card"><input type="checkbox" name="disable_tmdb"><span><strong>Disable TMDB matching</strong><small>Keep imported titles and metadata unchanged.</small></span></label>
                <label class="sc-selection-card"><input type="checkbox" name="ignore_no_match"><span><strong>Keep unmatched movies</strong><small>Add entries even when metadata cannot be matched.</small></span></label>
            </div><div class="sc-form-grid sc-import-fallbacks">
                <?php $streamcreedMovieRenderPicker('Fallback categories', 'category_id[]', 'category_create_list', $streamcreedMovieCategoryOptions, []); ?>
                <?php $streamcreedMovieRenderPicker('Fallback bouquets', 'bouquets[]', 'bouquet_create_list', $streamcreedMovieBouquetOptions, []); ?>
            </div></section>
        <?php else: ?>
            <section class="sc-form-section"><h2>Movie details</h2><div class="sc-form-grid">
                <label>Movie name<input type="text" id="stream_display_name" name="stream_display_name" required value="<?php echo $streamcreedMovieValue('stream_display_name', (string) (RequestManager::getAll()['title'] ?? '')); ?>"></label>
                <label>Year<input type="text" id="year" name="year" inputmode="numeric" value="<?php echo $streamcreedMovieValue('year'); ?>"></label>
                <label class="sc-form-span">Movie path or URL<span class="sc-input-action"><input type="text" id="stream_source" name="stream_source" required value="<?php echo htmlspecialchars($streamcreedMovieSource ?: (string) (RequestManager::getAll()['path'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="https://example.com/movie.mkv or /mnt/movies/movie.mkv"><button class="sc-button sc-button-secondary" type="button" data-sc-file-browser-open data-sc-file-target="stream_source" data-sc-file-filter="video"><i class="fe-folder" aria-hidden="true"></i> Browse</button></span></label>
                <?php $streamcreedMovieRenderPicker('Categories', 'category_id[]', 'category_create_list', $streamcreedMovieCategoryOptions, $streamcreedMovieCategories); ?>
                <?php $streamcreedMovieRenderPicker('Bouquets', 'bouquets[]', 'bouquet_create_list', $streamcreedMovieBouquetOptions, $streamcreedMovieSelectedBouquets); ?>
            </div></section>

            <section class="sc-form-section"><h2>Metadata</h2>
                <?php if ($streamcreedHasTmdb): ?>
                    <div class="sc-tmdb-lookup" data-sc-tmdb data-sc-tmdb-language="<?php echo htmlspecialchars((string) ($rSettings['tmdb_language'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="sc-section-heading"><div><h2>Search TMDB</h2><p class="sc-section-copy">Uses the default TMDB language configured in Settings.</p></div></div>
                        <label>Movie title<input type="search" data-sc-tmdb-query placeholder="Search by movie title" autocomplete="off"></label>
                        <div class="sc-tmdb-results" data-sc-tmdb-results hidden></div>
                    </div>
                <?php endif; ?>
                <div class="sc-form-grid" style="margin-top: 24px;">
                <label>Poster URL<input type="url" name="movie_image" value="<?php echo $streamcreedMovieProperty('movie_image'); ?>"></label>
                <label>Backdrop URL<input type="url" name="backdrop_path" value="<?php echo htmlspecialchars((string) ($streamcreedMovieProperties['backdrop_path'][0] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></label>
                <label class="sc-form-span">Plot<textarea name="plot" rows="5"><?php echo $streamcreedMovieProperty('plot'); ?></textarea></label>
                <label>Cast<input type="text" name="cast" value="<?php echo $streamcreedMovieProperty('cast'); ?>"></label>
                <label>Director<input type="text" name="director" value="<?php echo $streamcreedMovieProperty('director'); ?>"></label>
                <label>Genres<input type="text" name="genre" value="<?php echo $streamcreedMovieProperty('genre'); ?>"></label>
                <label>Country<input type="text" name="country" value="<?php echo $streamcreedMovieProperty('country'); ?>"></label>
                <label>Release date<input type="text" name="release_date" value="<?php echo $streamcreedMovieProperty('release_date'); ?>"></label>
                <label>Runtime<input type="text" name="episode_run_time" value="<?php echo $streamcreedMovieProperty('episode_run_time'); ?>"></label>
                <label>YouTube trailer<input type="text" name="youtube_trailer" value="<?php echo $streamcreedMovieProperty('youtube_trailer'); ?>"></label>
                <label>Rating<input type="number" name="rating" min="0" max="10" step="0.1" value="<?php echo $streamcreedMovieProperty('rating'); ?>"></label>
            </div></section>
        <?php endif; ?>

        <section class="sc-form-section"><h2>Processing</h2><div class="sc-form-grid">
            <label>Transcode profile<select id="transcode_profile_id" name="transcode_profile_id"><option value="0">Disabled</option><?php foreach ($rTranscodeProfiles as $streamcreedMovieProfile): ?><option value="<?php echo intval($streamcreedMovieProfile['profile_id']); ?>"<?php echo intval($streamcreedMovie['transcode_profile_id'] ?? 0) === intval($streamcreedMovieProfile['profile_id']) ? ' selected' : ''; ?>><?php echo htmlspecialchars((string) $streamcreedMovieProfile['profile_name'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></label>
            <label>Target container<select id="target_container" name="target_container"><?php foreach (['mp4', 'mkv', 'avi', 'mpg', 'flv', '3gp', 'm4v', 'wmv', 'mov', 'ts'] as $streamcreedMovieContainer): ?><option value="<?php echo $streamcreedMovieContainer; ?>"<?php echo ($streamcreedMovie['target_container'] ?? 'mp4') === $streamcreedMovieContainer ? ' selected' : ''; ?>><?php echo $streamcreedMovieContainer; ?></option><?php endforeach; ?></select></label>
            <?php if (!$streamcreedMovieImporting): ?><label class="sc-form-span">Subtitle location<span class="sc-input-action"><input type="text" id="movie_subtitles" name="movie_subtitles" value="<?php echo htmlspecialchars($streamcreedMovieSubtitle, ENT_QUOTES, 'UTF-8'); ?>" placeholder="s:server_id:subtitle.srt"><button class="sc-button sc-button-secondary" type="button" data-sc-file-browser-open data-sc-file-target="movie_subtitles" data-sc-file-filter="subs"><i class="fe-folder" aria-hidden="true"></i> Browse</button></span></label><?php endif; ?>
        </div><div class="sc-package-options sc-line-switches">
            <?php foreach ([['direct_source', 'Direct source'], ['direct_proxy', 'Direct stream'], ['read_native', 'Native frames'], ['movie_symlink', 'Create symlink'], ['remove_subtitles', 'Remove existing subtitles']] as [$streamcreedMovieSwitch, $streamcreedMovieSwitchLabel]): ?><label class="sc-selection-card"><input id="<?php echo $streamcreedMovieSwitch; ?>" type="checkbox" name="<?php echo $streamcreedMovieSwitch; ?>"<?php echo !empty($streamcreedMovie[$streamcreedMovieSwitch]) ? ' checked' : ''; ?>><span><strong><?php echo $streamcreedMovieSwitchLabel; ?></strong></span></label><?php endforeach; ?>
        </div></section>

        <section class="sc-form-section"><h2>Server placement</h2><p class="sc-section-copy">Select the server that processes the source, then optionally use it as the upstream source for other servers.</p><div class="sc-server-placement" data-server-placement>
            <?php foreach (ServerRepository::getStreamingSimple($rPermissions, 'all') as $streamcreedMovieServer): $streamcreedMovieServerId = intval($streamcreedMovieServer['id']); $streamcreedMovieParent = $streamcreedMovieParents[$streamcreedMovieServerId] ?? 'offline'; ?>
                <div class="sc-server-placement-row" data-server-id="<?php echo $streamcreedMovieServerId; ?>"><strong><?php echo htmlspecialchars((string) $streamcreedMovieServer['server_name'], ENT_QUOTES, 'UTF-8'); ?></strong><label>Upstream<select data-server-parent><option value="offline"<?php echo $streamcreedMovieParent === 'offline' ? ' selected' : ''; ?>>Disabled</option><option value="source"<?php echo $streamcreedMovieParent === 'source' ? ' selected' : ''; ?>>Process source</option><?php foreach (ServerRepository::getStreamingSimple($rPermissions, 'all') as $streamcreedMovieUpstream): if (intval($streamcreedMovieUpstream['id']) === $streamcreedMovieServerId) continue; ?><option value="<?php echo intval($streamcreedMovieUpstream['id']); ?>"<?php echo (string) $streamcreedMovieParent === (string) intval($streamcreedMovieUpstream['id']) ? ' selected' : ''; ?>><?php echo htmlspecialchars((string) $streamcreedMovieUpstream['server_name'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></label></div>
            <?php endforeach; ?>
        </div><label class="sc-selection-card" style="margin-top: 16px;"><input type="checkbox" name="restart_on_edit"><span><strong><?php echo $streamcreedMovieEditing ? 'Reprocess after saving' : 'Process movie now'; ?></strong><small>Queue the selected movie on its assigned servers.</small></span></label></section>

        <div class="sc-form-error" data-stream-error role="alert" hidden></div>
        <div class="sc-form-actions"><button class="sc-button sc-button-primary" type="submit" name="submit_movie" value="<?php echo $streamcreedMovieEditing ? 'Edit' : 'Add'; ?>"><?php echo $streamcreedMovieImporting ? 'Import movies' : ($streamcreedMovieEditing ? 'Save movie' : 'Add movie'); ?></button><a class="sc-button sc-button-secondary" href="movies">Cancel</a></div>
    </form>
</section>

<dialog class="sc-playlist-dialog sc-file-browser-dialog" data-sc-file-browser-dialog>
    <div class="sc-dialog-heading"><div><p class="sc-eyebrow">Server files</p><h2 data-sc-file-browser-title>Browse movie files</h2></div><button class="sc-dialog-close" type="button" data-sc-file-browser-close aria-label="Close"><i class="fe-x" aria-hidden="true"></i></button></div>
    <div class="sc-dialog-body">
        <div class="sc-form-grid"><label>Server<select data-sc-file-browser-server><?php foreach ($streamcreedMovieServers as $streamcreedMovieServer): ?><option value="<?php echo intval($streamcreedMovieServer['id']); ?>"><?php echo htmlspecialchars((string) $streamcreedMovieServer['server_name'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></label><label>Directory<span class="sc-input-action"><input type="text" value="/" data-sc-file-browser-path><button class="sc-button sc-button-secondary" type="button" data-sc-file-browser-load>Open</button></span></label></div>
        <div class="sc-file-browser-columns"><section><h3>Folders</h3><div data-sc-file-browser-dirs class="sc-file-browser-list"></div></section><section><h3 data-sc-file-browser-files-heading>Compatible files</h3><div data-sc-file-browser-files class="sc-file-browser-list"></div></section></div>
    </div>
</dialog>
