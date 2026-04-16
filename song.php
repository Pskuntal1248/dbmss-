<?php
/**
 * song.php — Song Detail Page + Buy button
 * Online Music Shop — SwarBazaar
 */

define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
define('ROOT_URL', '');

require_once 'config/helpers.php';
require_once 'config/db.php';

$pdo = getPDO();

$songId = (int)($_GET['id'] ?? 0);
if (!$songId) { redirect('shop.php'); }

// Load from view
$stmt = $pdo->prepare("SELECT * FROM v_song_details WHERE song_id = ?");
$stmt->execute([$songId]);
$song = $stmt->fetch();

if (!$song) {
    http_response_code(404);
    include 'includes/header.php';
    echo '<div class="container py-5 text-center">
            <div class="empty-state"><i class="bi bi-music-note-list"></i>
            <h2>Song Not Found</h2>
            <a href="shop.php" class="btn btn-primary mt-2">Back to Shop</a></div>
          </div>';
    include 'includes/footer.php';
    exit;
}

// Check if logged-in customer already bought this song
$alreadyOwned = false;
$custId = customerID();
if ($custId) {
    $owned = $pdo->prepare("SELECT purchase_id FROM purchases WHERE customer_id = ? AND song_id = ?");
    $owned->execute([$custId, $songId]);
    $alreadyOwned = (bool)$owned->fetch();
}

// Handle Buy form submission
$flash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buy'])) {
    if (!isLoggedIn()) {
        redirect("auth/login.php?redirect=" . urlencode("song.php?id=$songId"));
    }
    if (!isCustomer()) {
        $flash = '<div class="alert alert-warning alert-dismissible fade show" role="alert">
                    Admins cannot purchase songs.
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
                  </div>';
    } elseif ($alreadyOwned) {
        $flash = '<div class="alert alert-info alert-dismissible fade show" role="alert">
                    You already own this song.
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
                  </div>';
    } else {
        $format = in_array($_POST['format'] ?? '', ['MP3','WAV']) ? $_POST['format'] : 'MP3';
        // Validate format vs available_as
        if ($song['available_as'] === 'MP3' && $format === 'WAV') $format = 'MP3';
        if ($song['available_as'] === 'WAV' && $format === 'MP3') $format = 'WAV';

        $ins = $pdo->prepare("INSERT INTO purchases (customer_id, song_id, amount_paid, format_chosen)
                              VALUES (?, ?, ?, ?)");
        $ins->execute([$custId, $songId, $song['price'], $format]);
        $alreadyOwned = true;
        $flash = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i>
                    Purchase successful! <strong>' . h($song['title']) . '</strong> has been added to your library.
                    <a href="customer/my_purchases.php" class="alert-link ms-2">View My Library →</a>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
                  </div>';
    }
}

// Category badge
function catBadge(string $c): string {
    $map = ['Romantic'=>'badge-romantic','Sad'=>'badge-sad','Party'=>'badge-party',
            'Sufi'=>'badge-sufi','Folk'=>'badge-folk','Patriotic'=>'badge-patriotic',
            'Classical'=>'badge-classical','Devotional'=>'badge-devotional','Item'=>'badge-item','Other'=>'badge-other'];
    return "<span class='badge " . ($map[$c] ?? 'badge-other') . " fs-6'>" . htmlspecialchars($c) . "</span>";
}

$pageTitle = h($song['title']);
include 'includes/header.php';
?>
<main class="main-content">
<div class="container py-5">

    <?= $flash ?>

    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="shop.php">Songs</a></li>
            <li class="breadcrumb-item active"><?= h($song['title']) ?></li>
        </ol>
    </nav>

    <div class="row g-5">
        <!-- Left: Song Details -->
        <div class="col-lg-8">
            <div class="detail-hero mb-4">
                <div class="d-flex align-items-start gap-4 flex-wrap">
                    <div style="width:80px;height:80px;background:linear-gradient(135deg,#7c3aed,#a78bfa);
                                border-radius:16px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                        <i class="bi bi-music-note-beamed text-white" style="font-size:2.2rem"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="mb-2"><?= catBadge($song['category']) ?></div>
                        <h1 class="fw-bold mb-1" style="font-size:clamp(1.4rem,3vw,2rem)"><?= h($song['title']) ?></h1>
                        <p class="text-muted mb-0">
                            <i class="bi bi-film me-1"></i><strong>Movie:</strong> <?= h($song['movie_name']) ?>
                        </p>
                    </div>
                    <div class="detail-price"><?= formatPrice((float)$song['price']) ?></div>
                </div>
            </div>

            <!-- Info grid -->
            <div class="info-grid">
                <div class="info-card">
                    <div class="label"><i class="bi bi-mic me-1"></i>Singer(s)</div>
                    <div class="value" style="font-size:.9rem;color:#a78bfa"><?= h($song['singers'] ?? '—') ?></div>
                </div>
                <div class="info-card">
                    <div class="label"><i class="bi bi-music-player me-1"></i>Composer(s)</div>
                    <div class="value" style="font-size:.9rem;color:#93c5fd"><?= h($song['composers'] ?? '—') ?></div>
                </div>
                <div class="info-card">
                    <div class="label"><i class="bi bi-building me-1"></i>Label</div>
                    <div class="value" style="font-size:.9rem"><?= h($song['company_name']) ?></div>
                </div>
                <div class="info-card">
                    <div class="label"><i class="bi bi-clock me-1"></i>Duration</div>
                    <div class="value"><?= formatDuration($song['duration']) ?></div>
                </div>
                <div class="info-card">
                    <div class="label"><i class="bi bi-file-earmark-music me-1"></i>Format</div>
                    <div class="value"><?= h($song['available_as']) ?></div>
                </div>
                <div class="info-card">
                    <div class="label"><i class="bi bi-hdd me-1"></i>File Size</div>
                    <div class="value"><?= $song['size_mb'] ?> MB</div>
                </div>
            </div>
        </div>

        <!-- Right: Buy Box -->
        <div class="col-lg-4">
            <div class="admin-card" style="position:sticky;top:90px">
                <h5 class="fw-bold mb-3"><i class="bi bi-cart me-2"></i>Purchase Song</h5>
                <div class="mb-3 p-3 rounded" style="background:var(--glass);border:1px solid var(--border)">
                    <div class="d-flex justify-content-between">
                        <span class="text-muted small">Price</span>
                        <strong class="text-accent"><?= formatPrice((float)$song['price']) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mt-1">
                        <span class="text-muted small">Available As</span>
                        <span><?= h($song['available_as']) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mt-1">
                        <span class="text-muted small">File Size</span>
                        <span><?= $song['size_mb'] ?> MB</span>
                    </div>
                </div>

                <?php if ($alreadyOwned): ?>
                    <div class="text-center py-3">
                        <i class="bi bi-check-circle-fill text-success fs-2 d-block mb-2"></i>
                        <p class="fw-semibold mb-1">You already own this song</p>
                        <a href="customer/my_purchases.php" class="btn btn-outline-primary w-100 mt-2">
                            <i class="bi bi-bag-check me-2"></i>View My Library
                        </a>
                    </div>
                <?php elseif (!isLoggedIn()): ?>
                    <p class="small text-muted mb-3">Login to purchase this song.</p>
                    <a href="auth/login.php?redirect=<?= urlencode("song.php?id=$songId") ?>"
                       class="btn btn-primary w-100" id="loginToBuyBtn">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Login to Buy
                    </a>
                    <a href="auth/register.php" class="btn btn-outline-primary w-100 mt-2">
                        <i class="bi bi-person-plus me-2"></i>Create Account
                    </a>
                <?php elseif (isAdmin()): ?>
                    <p class="text-muted small text-center">Admins cannot purchase songs.</p>
                <?php else: ?>
                    <form action="song.php?id=<?= $songId ?>" method="POST" class="buy-form" id="buyForm">
                        <!-- Format selector -->
                        <?php if ($song['available_as'] === 'Both'): ?>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Choose Format</label>
                            <div class="d-flex gap-2">
                                <div class="form-check flex-fill">
                                    <input class="form-check-input" type="radio" name="format" id="fmtMP3" value="MP3" checked>
                                    <label class="form-check-label" for="fmtMP3">MP3</label>
                                </div>
                                <div class="form-check flex-fill">
                                    <input class="form-check-input" type="radio" name="format" id="fmtWAV" value="WAV">
                                    <label class="form-check-label" for="fmtWAV">WAV</label>
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                            <input type="hidden" name="format" value="<?= h($song['available_as']) ?>">
                        <?php endif; ?>
                        <button type="submit" name="buy" value="1" class="btn btn-buy w-100 py-2" id="buyNowBtn">
                            <i class="bi bi-bag-plus me-2"></i>Buy Now — <?= formatPrice((float)$song['price']) ?>
                        </button>
                    </form>
                <?php endif; ?>

                <hr style="border-color:var(--border)">
                <p class="small text-muted mb-0">
                    <i class="bi bi-shield-check me-1"></i>Secure simulated purchase. No real payment needed.
                </p>
            </div>
        </div>
    </div>

    <!-- Back link -->
    <div class="mt-4">
        <a href="shop.php" class="btn btn-outline-primary">
            <i class="bi bi-arrow-left me-1"></i>Back to Shop
        </a>
    </div>
</div>
</main>

<?php include 'includes/footer.php'; ?>
