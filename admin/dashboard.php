<?php
/**
 * admin/dashboard.php — Admin Overview
 * Online Music Shop — SwarBazaar
 */

define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('ROOT_URL', '../');

require_once ROOT_PATH . 'config/helpers.php';
require_once ROOT_PATH . 'config/db.php';

requireAdmin();

$pdo = getPDO();

$stats = $pdo->query("
    SELECT
        (SELECT COUNT(*) FROM songs)          AS songs,
        (SELECT COUNT(*) FROM singers)        AS singers,
        (SELECT COUNT(*) FROM composers)      AS composers,
        (SELECT COUNT(*) FROM record_companies) AS companies,
        (SELECT COUNT(*) FROM customers)      AS customers,
        (SELECT COUNT(*) FROM purchases)      AS purchases,
        (SELECT COALESCE(SUM(amount_paid),0) FROM purchases) AS revenue
")->fetch();

// Recent purchases
$recent = $pdo->query("SELECT * FROM v_purchase_details ORDER BY purchase_date DESC LIMIT 8")->fetchAll();

$pageTitle = 'Admin Dashboard';
include ROOT_PATH . 'includes/header.php';
?>
<main class="main-content">
<div class="container-fluid">
<div class="row">

    <!-- Sidebar -->
    <?php include 'partials/sidebar.php'; ?>

    <!-- Main -->
    <div class="col-md-9 col-lg-10 py-4 px-4">

        <h1 class="fw-bold mb-1">Dashboard</h1>
        <p class="text-muted mb-4">Welcome back, <?= h($_SESSION['username']) ?>! Here's an overview.</p>

        <!-- Stat Cards -->
        <div class="row row-cols-2 row-cols-md-4 g-3 mb-4">
            <?php
            $statItems = [
                ['icon'=>'music-note-list', 'val'=>$stats['songs'],     'lbl'=>'Songs',      'color'=>'#a78bfa'],
                ['icon'=>'mic',             'val'=>$stats['singers'],   'lbl'=>'Singers',    'color'=>'#93c5fd'],
                ['icon'=>'music-player',    'val'=>$stats['composers'], 'lbl'=>'Composers',  'color'=>'#6ee7b7'],
                ['icon'=>'building',        'val'=>$stats['companies'], 'lbl'=>'Labels',     'color'=>'#fcd34d'],
                ['icon'=>'people',          'val'=>$stats['customers'], 'lbl'=>'Customers',  'color'=>'#f9a8d4'],
                ['icon'=>'bag-check',       'val'=>$stats['purchases'], 'lbl'=>'Purchases',  'color'=>'#fdba74'],
                ['icon'=>'currency-rupee',  'val'=>'₹'.number_format($stats['revenue'],2), 'lbl'=>'Revenue', 'color'=>'#fbbf24'],
            ];
            foreach ($statItems as $s): ?>
            <div class="col">
                <div class="stat-card">
                    <i class="bi bi-<?= $s['icon'] ?> stat-icon" style="color:<?= $s['color'] ?>"></i>
                    <div class="stat-val mt-2"><?= $s['val'] ?></div>
                    <div class="stat-lbl"><?= $s['lbl'] ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Quick Links -->
        <div class="row g-3 mb-4">
            <?php
            $links = [
                ['href'=>'songs.php',     'icon'=>'music-note-list', 'label'=>'Manage Songs'],
                ['href'=>'singers.php',   'icon'=>'mic',             'label'=>'Manage Singers'],
                ['href'=>'composers.php', 'icon'=>'music-player',    'label'=>'Manage Composers'],
                ['href'=>'companies.php', 'icon'=>'building',        'label'=>'Manage Labels'],
                ['href'=>'purchases.php', 'icon'=>'bag-check',       'label'=>'View Purchases'],
            ];
            foreach ($links as $l): ?>
            <div class="col-6 col-md-4 col-lg-2">
                <a href="<?= $l['href'] ?>" class="admin-card d-flex flex-column align-items-center
                          text-center gap-2 text-decoration-none" style="padding:1.2rem">
                    <i class="bi bi-<?= $l['icon'] ?> fs-2 text-primary-custom"></i>
                    <span class="small fw-semibold text-muted"><?= $l['label'] ?></span>
                </a>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Recent Purchases -->
        <div class="admin-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0"><i class="bi bi-clock-history me-2"></i>Recent Purchases</h5>
                <a href="purchases.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <?php if (empty($recent)): ?>
                <p class="text-muted small text-center py-3">No purchases yet.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>#</th><th>Customer</th><th>Song</th><th>Movie</th>
                            <th>Format</th><th>Amount</th><th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recent as $r): ?>
                        <tr>
                            <td><?= $r['purchase_id'] ?></td>
                            <td><strong><?= h($r['full_name']) ?></strong><br>
                                <small class="text-muted"><?= h($r['username']) ?></small></td>
                            <td><?= h($r['song_title']) ?></td>
                            <td class="text-muted small"><?= h($r['movie_name']) ?></td>
                            <td><span class="badge bg-secondary"><?= h($r['format_chosen']) ?></span></td>
                            <td class="text-accent fw-semibold">₹<?= number_format($r['amount_paid'],2) ?></td>
                            <td class="text-muted small"><?= date('d M Y', strtotime($r['purchase_date'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</div>
</main>

<?php include ROOT_PATH . 'includes/footer.php'; ?>
