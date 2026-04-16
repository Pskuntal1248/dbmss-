<?php
/**
 * Header / HTML <head> + Navbar
 * Included at the top of every page.
 */

// Bootstrap paths/helpers if not already loaded
if (!function_exists('isLoggedIn')) {
    require_once dirname(__DIR__) . '/config/helpers.php';
}

$pageTitle = $pageTitle ?? 'Online Music Shop';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Online Music Shop — Buy your favourite Bollywood movie songs in MP3 or WAV format.">
    <title><?= h($pageTitle) ?> | SwarBazaar</title>

    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Font: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= ROOT_URL ?>assets/css/style.css">
</head>
<body>

<!-- ===== NAVBAR ===== -->
<nav class="navbar navbar-expand-lg navbar-dark fixed-top" id="mainNav">
    <div class="container">
        <!-- Brand -->
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="<?= ROOT_URL ?>index.php">
            <i class="bi bi-music-note-beamed brand-icon"></i>
            <span>SwarBazaar</span>
        </a>

        <!-- Toggler -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navContent">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="<?= ROOT_URL ?>index.php">
                        <i class="bi bi-house me-1"></i>Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= ROOT_URL ?>shop.php">
                        <i class="bi bi-grid me-1"></i>Browse Songs
                    </a>
                </li>
                <?php if (isLoggedIn()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= ROOT_URL ?>customer/my_purchases.php">
                            <i class="bi bi-bag-check me-1"></i>My Library
                        </a>
                    </li>
                <?php endif; ?>
                <?php if (isAdmin()): ?>
                    <li class="nav-item">
                        <a class="nav-link text-warning fw-semibold" href="<?= ROOT_URL ?>admin/dashboard.php">
                            <i class="bi bi-speedometer2 me-1"></i>Admin
                        </a>
                    </li>
                <?php endif; ?>
            </ul>

            <!-- Right side -->
            <ul class="navbar-nav ms-auto align-items-center gap-2">
                <?php if (!isLoggedIn()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= ROOT_URL ?>auth/login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-primary btn-sm px-3" href="<?= ROOT_URL ?>auth/register.php">Register</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center gap-1"
                           href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle fs-5"></i>
                            <?= h($_SESSION['username'] ?? 'Account') ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <?php if (isAdmin()): ?>
                                <li><a class="dropdown-item" href="<?= ROOT_URL ?>admin/dashboard.php">
                                    <i class="bi bi-speedometer2 me-2"></i>Admin Dashboard</a></li>
                                <li><hr class="dropdown-divider"></li>
                            <?php else: ?>
                                <li><a class="dropdown-item" href="<?= ROOT_URL ?>customer/my_purchases.php">
                                    <i class="bi bi-bag-check me-2"></i>My Library</a></li>
                                <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item text-danger" href="<?= ROOT_URL ?>auth/logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<!-- Spacer for fixed navbar -->
<div style="height:70px;"></div>
