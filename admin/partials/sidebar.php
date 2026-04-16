<?php
// admin/partials/sidebar.php — reused admin sidebar
?>
<div class="col-md-3 col-lg-2 p-0">
    <div class="admin-sidebar">
        <div class="mb-3 px-2">
            <p class="small fw-bold text-muted text-uppercase" style="letter-spacing:1px;margin-bottom:.5rem">Admin Panel</p>
        </div>
        <nav class="nav flex-column gap-1">
            <a class="nav-link <?= (basename($_SERVER['PHP_SELF'])==='dashboard.php')?'active':'' ?>" href="dashboard.php">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <a class="nav-link <?= (basename($_SERVER['PHP_SELF'])==='songs.php')?'active':'' ?>" href="songs.php">
                <i class="bi bi-music-note-list"></i> Songs
            </a>
            <a class="nav-link <?= (basename($_SERVER['PHP_SELF'])==='singers.php')?'active':'' ?>" href="singers.php">
                <i class="bi bi-mic"></i> Singers
            </a>
            <a class="nav-link <?= (basename($_SERVER['PHP_SELF'])==='composers.php')?'active':'' ?>" href="composers.php">
                <i class="bi bi-music-player"></i> Composers
            </a>
            <a class="nav-link <?= (basename($_SERVER['PHP_SELF'])==='companies.php')?'active':'' ?>" href="companies.php">
                <i class="bi bi-building"></i> Record Labels
            </a>
            <a class="nav-link <?= (basename($_SERVER['PHP_SELF'])==='purchases.php')?'active':'' ?>" href="purchases.php">
                <i class="bi bi-bag-check"></i> Purchases
            </a>
            <hr style="border-color:var(--border)">
            <a class="nav-link" href="../shop.php" target="_blank">
                <i class="bi bi-shop"></i> View Shop
            </a>
            <a class="nav-link text-danger" href="../auth/logout.php">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </nav>
    </div>
</div>
