<?php
/**
 * index.php — Homepage / Hero Landing
 * Online Music Shop — SwarBazaar
 */

define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
define('ROOT_URL', '');

require_once 'config/helpers.php';
require_once 'config/db.php';

$pdo = getPDO();

// Fetch latest 6 songs for "Featured" section
$songStmt = $pdo->query("SELECT * FROM v_song_details ORDER BY song_id DESC LIMIT 6");
$featuredSongs = $songStmt->fetchAll();

// Stats counts
$counts = $pdo->query("
    SELECT
        (SELECT COUNT(*) FROM songs)     AS total_songs,
        (SELECT COUNT(*) FROM singers)   AS total_singers,
        (SELECT COUNT(*) FROM customers) AS total_customers
")->fetch();

$pageTitle = 'Home';

// Category badge helper (reused across pages)
function catBadge(string $cat): string {
    $map = [
        'Romantic'   => 'badge-romantic',
        'Sad'        => 'badge-sad',
        'Party'      => 'badge-party',
        'Sufi'       => 'badge-sufi',
        'Folk'       => 'badge-folk',
        'Patriotic'  => 'badge-patriotic',
        'Classical'  => 'badge-classical',
        'Devotional' => 'badge-devotional',
        'Item'       => 'badge-item',
        'Other'      => 'badge-other',
    ];
    $cls = $map[$cat] ?? 'badge-other';
    return "<span class='badge $cls'>" . htmlspecialchars($cat) . "</span>";
}

include 'includes/header.php';
?>
<main class="main-content">

<!-- ===== HERO ===== -->
<section class="hero-section">
    <div class="hero-bg-glow"></div>
    <div class="container position-relative">
        <div class="row align-items-center gy-5">
            <div class="col-lg-6">
                <p class="section-label mb-2">🎵 India's Favourite Bollywood Music Store</p>
                <h1 class="hero-title">
                    Your Stage.<br>
                    Your <span class="gradient-text">Bollywood</span><br>
                    Soundtrack.
                </h1>
                <p class="hero-subtitle mt-3">
                    Browse, discover, and buy high-quality MP3 &amp; WAV versions of your
                    all-time favourite movie songs — instantly.
                </p>
                <div class="mt-4 d-flex gap-3 flex-wrap">
                    <a href="shop.php" class="btn btn-primary btn-lg px-4">
                        <i class="bi bi-grid me-2"></i>Browse Songs
                    </a>
                    <?php if (!isLoggedIn()): ?>
                        <a href="auth/register.php" class="btn btn-outline-light btn-lg px-4">
                            <i class="bi bi-person-plus me-2"></i>Join Free
                        </a>
                    <?php endif; ?>
                </div>
                <!-- Stats -->
                <div class="hero-stats mt-4">
                    <div class="hero-stat">
                        <div class="number"><?= $counts['total_songs'] ?>+</div>
                        <div class="label">Songs</div>
                    </div>
                    <div class="hero-stat">
                        <div class="number"><?= $counts['total_singers'] ?>+</div>
                        <div class="label">Singers</div>
                    </div>
                    <div class="hero-stat">
                        <div class="number"><?= $counts['total_customers'] ?>+</div>
                        <div class="label">Customers</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-5 offset-lg-1 d-none d-lg-block">
                <div class="floating-card">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div style="width:50px;height:50px;background:linear-gradient(135deg,#7c3aed,#a78bfa);
                                    border-radius:12px;display:flex;align-items:center;justify-content:center;">
                            <i class="bi bi-music-note-list text-white fs-4"></i>
                        </div>
                        <div>
                            <div class="fw-bold">Now Playing</div>
                            <div class="text-muted small">Tum Hi Ho</div>
                        </div>
                        <div class="ms-auto"><i class="bi bi-play-circle-fill fs-2 text-primary"></i></div>
                    </div>
                    <!-- Mini waveform SVG -->
                    <svg viewBox="0 0 200 40" class="w-100" style="height:40px">
                        <path d="M0,20 Q10,5 20,20 Q30,35 40,20 Q50,5 60,20 Q70,35 80,20
                                 Q90,8 100,20 Q110,32 120,20 Q130,8 140,20 Q150,35 160,20
                                 Q170,5 180,20 Q190,35 200,20"
                              fill="none" stroke="#7c3aed" stroke-width="2.5" stroke-linecap="round"/>
                    </svg>
                    <div class="d-flex justify-content-between mt-2 small text-muted">
                        <span>1:45</span><span>4:22</span>
                    </div>
                    <div class="progress mt-2" style="height:4px;background:rgba(255,255,255,0.1)">
                        <div class="progress-bar bg-primary" style="width:40%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== INLINE SEARCH ===== -->
<section class="search-section">
    <div class="container">
        <form action="shop.php" method="GET" class="search-wrapper">
            <i class="bi bi-search search-icon"></i>
            <input
                type="text"
                name="q"
                class="form-control search-input"
                placeholder="Search by song, movie, singer, composer, or label…"
                value="<?= h($_GET['q'] ?? '') ?>"
                id="heroSearchInput"
            >
            <button type="submit" class="btn btn-primary search-btn">Search</button>
        </form>
    </div>
</section>

<!-- ===== FEATURED SONGS ===== -->
<section class="py-5">
    <div class="container">
        <div class="d-flex align-items-end justify-content-between mb-4 flex-wrap gap-3">
            <div>
                <p class="section-label">✨ Handpicked For You</p>
                <h2 class="section-title mb-0">Featured Songs</h2>
            </div>
            <a href="shop.php" class="btn btn-outline-primary">
                View All <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-4">
            <?php foreach ($featuredSongs as $song): ?>
            <div class="col song-card-wrapper fade-up" data-cat="<?= h($song['category']) ?>">
                <div class="song-card">
                    <div class="song-card-img">
                        <i class="bi bi-music-note-beamed music-icon"></i>
                        <span class="category-badge"><?= catBadge($song['category']) ?></span>
                        <span class="format-badge"><?= h($song['available_as']) ?></span>
                    </div>
                    <div class="song-card-body">
                        <div class="song-title" title="<?= h($song['title']) ?>"><?= h($song['title']) ?></div>
                        <div class="song-movie"><i class="bi bi-film"></i><?= h($song['movie_name']) ?></div>
                        <div class="song-singer"><i class="bi bi-mic"></i><?= h($song['singers'] ?? '—') ?></div>
                    </div>
                    <div class="song-card-footer">
                        <span class="price-tag"><?= formatPrice((float)$song['price']) ?></span>
                        <span class="duration-tag"><i class="bi bi-clock"></i> <?= formatDuration($song['duration']) ?></span>
                        <a href="song.php?id=<?= $song['song_id'] ?>" class="btn btn-buy btn-sm">
                            <i class="bi bi-eye me-1"></i>Details
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ===== CATEGORIES BANNER ===== -->
<section class="py-5 bg-surface-2">
    <div class="container text-center">
        <p class="section-label">Explore by Mood</p>
        <h2 class="section-title mb-4">Browse Categories</h2>
        <div class="row row-cols-2 row-cols-md-4 row-cols-lg-5 g-3 justify-content-center">
            <?php
            $cats = ['Romantic','Sad','Party','Sufi','Folk','Patriotic','Classical','Devotional','Item'];
            $icons = ['💕','😢','🎉','🌙','🪘','🇮🇳','🎻','🕉️','💃'];
            foreach ($cats as $i => $c): ?>
            <div class="col">
                <a href="shop.php?cat=<?= urlencode($c) ?>"
                   class="d-block p-3 rounded text-center fade-up"
                   style="background:var(--glass);border:1px solid var(--border);transition:all .25s">
                    <div style="font-size:2rem"><?= $icons[$i] ?></div>
                    <div class="fw-semibold small mt-1"><?= $c ?></div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ===== CTA (if not logged in) ===== -->
<?php if (!isLoggedIn()): ?>
<section class="py-5">
    <div class="container">
        <div class="p-5 rounded text-center"
             style="background:linear-gradient(135deg,var(--primary-dark),#1b003d);border:1px solid var(--border)">
            <h2 class="fw-bold mb-2">Ready to Build Your Music Library?</h2>
            <p class="text-muted mb-4">Join thousands of music lovers. Register free and start buying songs instantly.</p>
            <a href="auth/register.php" class="btn btn-primary btn-lg px-5">
                <i class="bi bi-person-plus me-2"></i>Create Free Account
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

</main>

<?php include 'includes/footer.php'; ?>
