<?php
/**
 * admin/purchases.php — View All Purchases (read-only)
 * Online Music Shop — SwarBazaar
 */

define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('ROOT_URL', '../');

require_once ROOT_PATH . 'config/helpers.php';
require_once ROOT_PATH . 'config/db.php';

requireAdmin();
$pdo = getPDO();

// Revenue per song
$topSongs = $pdo->query("
    SELECT s.title, s.movie_name,
           COUNT(p.purchase_id)   AS units_sold,
           SUM(p.amount_paid)     AS total_revenue
    FROM songs s
    LEFT JOIN purchases p ON p.song_id = s.song_id
    GROUP BY s.song_id, s.title, s.movie_name
    ORDER BY total_revenue DESC
    LIMIT 5
")->fetchAll();

// All purchases
$purchases = $pdo->query("SELECT * FROM v_purchase_details ORDER BY purchase_date DESC")->fetchAll();

// Total revenue
$total = array_sum(array_column($purchases, 'amount_paid'));

$pageTitle = 'All Purchases';
include ROOT_PATH . 'includes/header.php';
?>
<main class="main-content">
<div class="container-fluid"><div class="row">
<?php include 'partials/sidebar.php'; ?>
<div class="col-md-9 col-lg-10 py-4 px-4">

    <h1 class="fw-bold mb-1">Purchase History</h1>
    <p class="text-muted mb-4">Total Revenue: <strong class="text-accent">₹<?= number_format((float)($total ?? 0), 2) ?></strong>
       &bull; <?= count($purchases) ?> transaction(s)</p>

    <!-- Top Songs -->
    <?php if ($topSongs): ?>
    <div class="admin-card mb-4">
        <h5 class="fw-bold mb-3"><i class="bi bi-trophy me-2 text-accent"></i>Top Selling Songs</h5>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead><tr><th>Song</th><th>Movie</th><th>Units Sold</th><th>Revenue</th></tr></thead>
                <tbody>
                <?php foreach($topSongs as $t): ?>
                <tr>
                    <td class="fw-semibold"><?= h($t['title']) ?></td>
                    <td class="text-muted small"><?= h($t['movie_name']) ?></td>
                    <td><span class="badge bg-primary"><?= $t['units_sold'] ?></span></td>
                    <td class="text-accent fw-semibold">₹<?= number_format((float)($t['total_revenue'] ?? 0), 2) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- All Purchases -->
    <div class="admin-card">
        <h5 class="fw-bold mb-3"><i class="bi bi-clock-history me-2"></i>All Transactions</h5>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr><th>#</th><th>Customer</th><th>Song</th><th>Movie</th><th>Format</th><th>Amount</th><th>Date</th></tr>
                </thead>
                <tbody>
                <?php if(empty($purchases)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No purchases yet.</td></tr>
                <?php else: ?>
                <?php foreach($purchases as $p): ?>
                <tr>
                    <td><?= $p['purchase_id'] ?></td>
                    <td>
                        <strong><?= h($p['full_name']) ?></strong><br>
                        <small class="text-muted"><?= h($p['email']) ?></small>
                    </td>
                    <td class="fw-semibold"><?= h($p['song_title']) ?></td>
                    <td class="text-muted small"><?= h($p['movie_name']) ?></td>
                    <td><span class="badge bg-secondary"><?= h($p['format_chosen']) ?></span></td>
                    <td class="text-accent fw-semibold">₹<?= number_format((float)($p['amount_paid'] ?? 0), 2) ?></td>
                    <td class="text-muted small"><?= date('d M Y, h:i A', strtotime($p['purchase_date'])) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div></div></div>
</main>
<?php include ROOT_PATH . 'includes/footer.php'; ?>
