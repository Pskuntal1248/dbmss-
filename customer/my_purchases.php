<?php
/**
 * customer/my_purchases.php — Customer's purchased songs
 * Online Music Shop — SwarBazaar
 */

define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('ROOT_URL', '../');

require_once ROOT_PATH . 'config/helpers.php';
require_once ROOT_PATH . 'config/db.php';

requireLogin();

// Admins can view via admin panel instead
if (isAdmin()) redirect(ROOT_URL . 'admin/purchases.php');

$custId = customerID();
$pdo    = getPDO();

$stmt = $pdo->prepare(
    "SELECT vsd.song_id, vsd.title, vsd.movie_name, vsd.singers, vsd.category,
            p.amount_paid, p.format_chosen, p.purchase_date
     FROM purchases p
     JOIN v_song_details vsd ON vsd.song_id = p.song_id
     WHERE p.customer_id = ?
     ORDER BY p.purchase_date DESC"
);
$stmt->execute([$custId]);
$purchases = $stmt->fetchAll();

// Category badge
function catBadge(string $c): string {
    $map = ['Romantic'=>'badge-romantic','Sad'=>'badge-sad','Party'=>'badge-party',
            'Sufi'=>'badge-sufi','Folk'=>'badge-folk','Patriotic'=>'badge-patriotic',
            'Classical'=>'badge-classical','Devotional'=>'badge-devotional','Item'=>'badge-item','Other'=>'badge-other'];
    return "<span class='badge " . ($map[$c] ?? 'badge-other') . "'>" . htmlspecialchars($c) . "</span>";
}

$pageTitle = 'My Library';
include ROOT_PATH . 'includes/header.php';
?>
<main class="main-content">
<div class="container py-5">

    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
        <div>
            <p class="section-label mb-1"><i class="bi bi-bag-check me-1"></i>Your Collection</p>
            <h1 class="section-title mb-0">My Music Library</h1>
            <p class="text-muted small mt-1"><?= count($purchases) ?> song(s) purchased</p>
        </div>
        <a href="<?= ROOT_URL ?>shop.php" class="btn btn-outline-primary">
            <i class="bi bi-plus-circle me-1"></i>Buy More Songs
        </a>
    </div>

    <?php if (empty($purchases)): ?>
        <div class="empty-state">
            <i class="bi bi-bag-x"></i>
            <h4>Your library is empty</h4>
            <p>You haven't purchased any songs yet.</p>
            <a href="<?= ROOT_URL ?>shop.php" class="btn btn-primary mt-2">Browse Songs</a>
        </div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php foreach ($purchases as $p): ?>
            <div class="col">
                <div class="song-card" style="border-color:rgba(124,58,237,0.3)">
                    <div class="song-card-img" style="height:120px">
                        <i class="bi bi-music-note-beamed music-icon"></i>
                        <span class="category-badge"><?= catBadge($p['category']) ?></span>
                        <span class="format-badge"><?= h($p['format_chosen']) ?></span>
                    </div>
                    <div class="song-card-body">
                        <div class="song-title"><?= h($p['title']) ?></div>
                        <div class="song-movie"><i class="bi bi-film"></i> <?= h($p['movie_name']) ?></div>
                        <div class="song-singer"><i class="bi bi-mic"></i> <?= h($p['singers'] ?? '—') ?></div>
                    </div>
                    <div class="song-card-footer" style="flex-direction:column;align-items:flex-start;gap:.4rem">
                        <div class="d-flex w-100 justify-content-between">
                            <span class="small text-muted">Paid</span>
                            <span class="price-tag" style="font-size:1rem"><?= formatPrice((float)$p['amount_paid']) ?></span>
                        </div>
                        <div class="d-flex w-100 justify-content-between">
                            <span class="small text-muted">Date</span>
                            <span class="small"><?= date('d M Y', strtotime($p['purchase_date'])) ?></span>
                        </div>
                        <a href="<?= ROOT_URL ?>song.php?id=<?= $p['song_id'] ?>" class="btn btn-outline-primary btn-sm w-100 mt-1">
                            <i class="bi bi-eye me-1"></i>View Details
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
</main>

<?php include ROOT_PATH . 'includes/footer.php'; ?>
