<?php
/**
 * Session + auth helpers
 * Online Music Shop — DBMS College Project
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** Returns true if user is logged in */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

/** Returns true if logged-in user is admin */
function isAdmin(): bool {
    return isLoggedIn() && ($_SESSION['role'] ?? '') === 'admin';
}

/** Returns true if logged-in user is customer */
function isCustomer(): bool {
    return isLoggedIn() && ($_SESSION['role'] ?? '') === 'customer';
}

/** Redirect helpers */
function redirect(string $url): void {
    header("Location: $url");
    exit;
}

/**
 * Require the user to be logged in.
 * If not, redirect to login page.
 */
function requireLogin(string $backTo = ''): void {
    if (!isLoggedIn()) {
        $qs = $backTo ? '?redirect=' . urlencode($backTo) : '';
        redirect(ROOT_URL . 'auth/login.php' . $qs);
    }
}

/**
 * Require admin role.
 * Shows access-denied page if not admin.
 */
function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        http_response_code(403);
        include ROOT_PATH . 'includes/header.php';
        echo '<div class="container py-5 text-center">
                <div class="denied-box">
                    <i class="bi bi-shield-lock display-1 text-danger"></i>
                    <h2 class="mt-3">Access Denied</h2>
                    <p class="text-muted">You do not have permission to view this page.</p>
                    <a href="' . ROOT_URL . '" class="btn btn-primary mt-2">Go Home</a>
                </div>
              </div>';
        include ROOT_PATH . 'includes/footer.php';
        exit;
    }
}

/** Return current customer_id from session (0 if not customer) */
function customerID(): int {
    return (int)($_SESSION['customer_id'] ?? 0);
}

/** Sanitize output */
function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

/** Format price in INR */
function formatPrice(float $price): string {
    return '₹' . number_format($price, 2);
}

/** Format duration (HH:MM:SS → MM:SS or H:MM:SS) */
function formatDuration(string $t): string {
    [$h, $m, $s] = explode(':', $t);
    if ((int)$h > 0) return "{$h}:{$m}:{$s}";
    return "{$m}:{$s}";
}

// Determine root paths dynamically so files work from any depth
if (!defined('ROOT_PATH')) {
    // Calculate path to project root from current file location
    define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
}
if (!defined('ROOT_URL')) {
    // Determine URL prefix (handles both direct and sub-folder installs)
    $script = $_SERVER['SCRIPT_NAME'] ?? '/';
    $base   = str_repeat('../', substr_count(ltrim($script, '/'), '/') - 1);
    define('ROOT_URL', $base ?: '/');
}
