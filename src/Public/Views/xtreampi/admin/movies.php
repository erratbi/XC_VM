<?php

use XcVm\Core\Auth\Authorization;
use XcVm\Domain\Server\ServerRepository;
use XcVm\Domain\Stream\CategoryService;

$xtreampiPageScripts = ['assets/xtreampi/movies.js'];
$xtreampiCanAddMovie = Authorization::check('adv', 'add_movie');
$xtreampiCanEditMovie = Authorization::check('adv', 'edit_movie');
$xtreampiCanMassEditMovies = Authorization::check('adv', 'mass_sedits_vod');
$xtreampiCanPlayMovie = Authorization::check('adv', 'player');
$xtreampiCanViewMovieConnections = Authorization::check('adv', 'live_connections');
$xtreampiMovieCategories = CategoryService::getAllByType('movie');
$xtreampiMovieServers = ServerRepository::getStreamingSimple($rPermissions);
$xtreampiMovieDefaultEntries = intval($rSettings['default_entries'] ?? 25);
if (!in_array($xtreampiMovieDefaultEntries, [10, 25, 50, 100], true)) {
    $xtreampiMovieDefaultEntries = 25;
}
?>
<section class="sc-movies" data-sc-movies
    data-endpoint="table"
    data-default-entries="<?php echo $xtreampiMovieDefaultEntries; ?>"
    data-can-edit="<?php echo $xtreampiCanEditMovie ? '1' : '0'; ?>"
    data-can-play="<?php echo $xtreampiCanPlayMovie ? '1' : '0'; ?>"
    data-can-view-connections="<?php echo $xtreampiCanViewMovieConnections ? '1' : '0'; ?>"
    data-show-images="<?php echo !empty($rSettings['show_images']) ? '1' : '0'; ?>">

    <div class="sc-page-heading">
        <div>
            <p class="sc-eyebrow">Content management</p>
            <h1>VOD Movies</h1>
        </div>
        <div class="sc-page-actions">
            <?php if ($xtreampiCanMassEditMovies): ?>
                <a class="sc-button sc-button-secondary" href="movie_mass"><i class="fe-edit-3" aria-hidden="true"></i> Mass edit</a>
            <?php endif; ?>
            <?php if ($xtreampiCanAddMovie): ?>
                <a class="sc-button sc-button-primary" href="movie"><i class="fe-plus" aria-hidden="true"></i> Add movie</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="sc-toolbar">
        <label class="sc-search-field">
            <i class="fe-search" aria-hidden="true"></i>
            <span class="sc-visually-hidden">Search movies</span>
            <input type="search" placeholder="Search movie name, ID, or source host" data-sc-movie-search>
        </label>
        <label class="sc-filter-field">
            <span>Server</span>
            <select data-sc-movie-server>
                <option value="">All servers</option>
                <option value="-1">No server</option>
                <?php foreach ($xtreampiMovieServers as $xtreampiMovieServer): ?>
                    <option value="<?php echo intval($xtreampiMovieServer['id']); ?>"><?php echo htmlspecialchars((string) $xtreampiMovieServer['server_name'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="sc-filter-field">
            <span>Category</span>
            <select data-sc-movie-category>
                <option value="">All categories</option>
                <option value="-1">Uncategorised</option>
                <?php foreach ($xtreampiMovieCategories as $xtreampiMovieCategory): ?>
                    <option value="<?php echo intval($xtreampiMovieCategory['id']); ?>"><?php echo htmlspecialchars((string) $xtreampiMovieCategory['category_name'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="sc-filter-field">
            <span>Status</span>
            <select data-sc-movie-status>
                <option value="">All statuses</option>
                <option value="1">Encoded</option>
                <option value="2">Encoding</option>
                <option value="3">Down</option>
                <option value="4">Ready</option>
                <option value="5">Direct</option>
                <option value="6">No TMDB match</option>
                <option value="7">Duplicate</option>
                <option value="8">Transcoding</option>
            </select>
        </label>
        <label class="sc-filter-field">
            <span>Video</span>
            <select data-sc-movie-video>
                <option value="">Any video</option>
                <option value="-1">No video data</option>
                <?php foreach ($rVideoCodecs as $xtreampiMovieCodec): ?>
                    <option value="<?php echo htmlspecialchars((string) $xtreampiMovieCodec, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) $xtreampiMovieCodec, ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="sc-filter-field">
            <span>Audio</span>
            <select data-sc-movie-audio>
                <option value="">Any audio</option>
                <option value="-1">No audio data</option>
                <?php foreach ($rAudioCodecs as $xtreampiMovieCodec): ?>
                    <option value="<?php echo htmlspecialchars((string) $xtreampiMovieCodec, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) $xtreampiMovieCodec, ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="sc-filter-field">
            <span>Quality</span>
            <select data-sc-movie-resolution>
                <option value="">Any quality</option>
                <?php foreach ([240, 360, 480, 576, 720, 1080, 1440, 2160] as $xtreampiMovieResolution): ?>
                    <option value="<?php echo $xtreampiMovieResolution; ?>"><?php echo $xtreampiMovieResolution; ?>p</option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="sc-filter-field sc-entry-field">
            <span>Per page</span>
            <select data-sc-movie-entries>
                <?php foreach ([10, 25, 50, 100] as $xtreampiMovieEntries): ?>
                    <option value="<?php echo $xtreampiMovieEntries; ?>"<?php echo $xtreampiMovieDefaultEntries === $xtreampiMovieEntries ? ' selected' : ''; ?>><?php echo $xtreampiMovieEntries; ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    </div>

    <div class="sc-data-panel">
        <div class="sc-table-scroll">
            <table class="sc-data-table sc-movies-table">
                <thead><tr>
                    <th class="sc-col-movie">Movie</th><th class="sc-col-server">Server</th><th class="sc-col-status">Status</th><th class="sc-col-conn">Clients</th><th class="sc-col-info">Media info</th><th class="sc-col-actions"><span class="sc-visually-hidden">Actions</span></th>
                </tr></thead>
                <tbody data-sc-movie-rows><tr><td class="sc-table-state" colspan="6"><span class="sc-spinner" aria-hidden="true"></span> Loading movies…</td></tr></tbody>
            </table>
        </div>
        <footer class="sc-table-footer">
            <span data-sc-movie-range>Loading…</span>
            <div class="sc-pagination"><button type="button" data-sc-movie-previous><i class="fe-chevron-left" aria-hidden="true"></i> Previous</button><span data-sc-movie-page>Page 1</span><button type="button" data-sc-movie-next>Next <i class="fe-chevron-right" aria-hidden="true"></i></button></div>
        </footer>
    </div>
</section>

<dialog class="sc-player-dialog" data-sc-movie-player-dialog aria-label="Movie player">
    <div class="sc-player-scaler" data-sc-movie-player-scaler>
        <button class="sc-player-close" type="button" data-sc-movie-player-close title="Close (Esc)" aria-label="Close (Esc)">&#215;</button>
        <iframe class="sc-player-frame" data-sc-movie-player-frame allow="autoplay; fullscreen" frameborder="0"></iframe>
    </div>
</dialog>
