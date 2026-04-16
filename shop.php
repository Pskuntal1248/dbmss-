<?php
/**
 * shop.php — Browse / Search Songs
 * Online Music Shop — SwarBazaar
 */

define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
define('ROOT_URL', '');

require_once 'config/helpers.php';
require_once 'config/db.php';

$pdo = getPDO();

// Search & filter inputs (sanitized)
$q   = trim($_GET['q']   ?? '');
$cat = trim($_GET['cat'] ?? '');

// Build query dynamically
$params = [];
$where  = [];

if ($q !== '') {
    $where[] = "(vsd.title LIKE :q1 OR vsd.movie_name LIKE :q2
                  OR vsd.singers LIKE :q3 OR vsd.composers LIKE :q4
                  OR vsd.company_name LIKE :q5)";
    $like = '%' . $q . '%';
    $params[':q1'] = $like;
    $params[':q2'] = $like;
    $params[':q3'] = $like;
    $params[':q4'] = $like;
    $params[':q5'] = $like;
}
if ($cat !== '') {
    $where[] = "vsd.category = :cat";
    $params[':cat'] = $cat;
}

$sql = "SELECT * FROM v_song_details vsd";
if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= " ORDER BY vsd.title ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$songs = $stmt->fetchAll();

// Categories for pills
$categories = ['Romantic','Sad','Party','Sufi','Folk','Patriotic','Classical','Devotional','Item','Other'];

// Category badge helper (needed here too)
function catBadge(string $c): string {
    $map = ['Romantic'=>'badge-romantic','Sad'=>'badge-sad','Party'=>'badge-party',
            'Sufi'=>'badge-sufi','Folk'=>'badge-folk','Patriotic'=>'badge-patriotic',
            'Classical'=>'badge-classical','Devotional'=>'badge-devotional','Item'=>'badge-item','Other'=>'badge-other'];
    return "<span class='badge " . ($map[$c] ?? 'badge-other') . "'>" . htmlspecialchars($c) . "</span>";
}

$pageTitle = $q ? "Search: $q" : ($cat ?: 'Browse Songs');
include 'includes/header.php';
?>
<main class="main-content">
<div class="container py-4">

    <!-- Page Heading -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
        <div>
            <p class="section-label mb-1">🎵 All Songs</p>
            <h1 class="section-title mb-0">
                <?php if ($q): ?>
                    Results for "<em><?= h($q) ?></em>"
                <?php elseif ($cat): ?>
                    <?= h($cat) ?> Songs
                <?php else: ?>
                    Browse the Collection
                <?php endif; ?>
            </h1>
            <p class="text-muted small mt-1"><?= count($songs) ?> song(s) found</p>
        </div>
        <!-- Search bar -->
        <form action="shop.php" method="GET" class="d-flex gap-2" style="min-width:280px">
            <?php if ($cat): ?>
                <input type="hidden" name="cat" value="<?= h($cat) ?>">
            <?php endif; ?>
            <div class="position-relative flex-grow-1">
                <i class="bi bi-search search-icon" style="left:.9rem;top:50%;transform:translateY(-50%);position:absolute;color:var(--text-muted)"></i>
                <input type="text" name="q" class="form-control"
                       style="padding-left:2.4rem;background:var(--surface-3);color:var(--text);border-color:var(--border)"
                       placeholder="Search songs…" value="<?= h($q) ?>">
            </div>
            <button type="submit" class="btn btn-primary" id="searchSubmitBtn">Go</button>
        </form>
    </div>

    <!-- Category Pills -->
    <div class="category-pills mb-4">
        <a href="shop.php" class="pill-btn <?= ($cat === '') ? 'active' : '' ?>">All</a>
        <?php foreach ($categories as $c): ?>
            <a href="shop.php?cat=<?= urlencode($c) ?><?= $q ? '&q=' . urlencode($q) : '' ?>"
               class="pill-btn <?= ($cat === $c) ? 'active' : '' ?>"><?= h($c) ?></a>
        <?php endforeach; ?>
    </div>

    <!-- Song Grid -->
    <?php if (empty($songs)): ?>
        <div class="empty-state">
            <i class="bi bi-music-note-list"></i>
            <h4>No songs found</h4>
            <p>Try a different search term or category.</p>
            <a href="shop.php" class="btn btn-primary mt-2">Clear Filters</a>
        </div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-4 g-4" id="songGrid">
            <?php foreach ($songs as $song): ?>
            <div class="col song-card-wrapper" data-cat="<?= h($song['category']) ?>">
                <div class="song-card">
                    <div class="song-card-img">
                        <i class="bi bi-music-note-beamed music-icon"></i>
                        <span class="category-badge"><?= catBadge($song['category']) ?></span>
                        <span class="format-badge"><?= h($song['available_as']) ?></span>
                    </div>
                    <div class="song-card-body">
                        <div class="song-title" title="<?= h($song['title']) ?>"><?= h($song['title']) ?></div>
                        <div class="song-movie"><i class="bi bi-film"></i> <?= h($song['movie_name']) ?></div>
                        <div class="song-singer"><i class="bi bi-mic"></i> <?= h($song['singers'] ?? '—') ?></div>
                        <div class="song-singer mt-1" style="color:var(--text-muted)">
                            <i class="bi bi-music-player"></i> <?= h($song['composers'] ?? '—') ?>
                        </div>
                    </div>
                    <div class="song-card-footer">
                        <span class="price-tag"><?= formatPrice((float)$song['price']) ?></span>
                        <span class="duration-tag"><i class="bi bi-clock"></i> <?= formatDuration($song['duration']) ?></span>
                        <a href="song.php?id=<?= $song['song_id'] ?>" class="btn btn-buy btn-sm">
                            <i class="bi bi-arrow-right-circle me-1"></i>View
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
</main>

<?php include 'includes/footer.php'; ?>
