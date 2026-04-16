<?php
/**
 * auth/register.php — Customer Registration
 * Online Music Shop — SwarBazaar
 */

define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('ROOT_URL', '../');

require_once ROOT_PATH . 'config/helpers.php';
require_once ROOT_PATH . 'config/db.php';

if (isLoggedIn()) redirect(ROOT_URL . 'index.php');

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username']  ?? '');
    $email    = trim($_POST['email']     ?? '');
    $password = $_POST['password']       ?? '';
    $confirm  = $_POST['confirm']        ?? '';
    $phone    = trim($_POST['phone']     ?? '');

    // Validation
    if (!$fullName || !$username || !$email || !$password) {
        $error = 'All required fields must be filled.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
        $error = 'Username must be 3–30 characters (letters, numbers, underscore only).';
    } else {
        $pdo = getPDO();

        // Check uniqueness
        $chk = $pdo->prepare("SELECT user_id FROM users WHERE username = ? OR email = ? LIMIT 1");
        $chk->execute([$username, $email]);
        if ($chk->fetch()) {
            $error = 'Username or email already exists. Please choose another.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);

            $pdo->beginTransaction();
            try {
                $insUser = $pdo->prepare(
                    "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'customer')"
                );
                $insUser->execute([$username, $email, $hash]);
                $userId = (int)$pdo->lastInsertId();

                $insCust = $pdo->prepare(
                    "INSERT INTO customers (user_id, full_name, phone) VALUES (?, ?, ?)"
                );
                $insCust->execute([$userId, $fullName, $phone]);

                $pdo->commit();
                $success = 'Account created successfully! You can now <a href="login.php">login</a>.';
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}

$pageTitle = 'Register';
include ROOT_PATH . 'includes/header.php';
?>
<div class="auth-wrapper">
    <div class="auth-card" style="max-width:520px">
        <div class="text-center mb-4">
            <div class="auth-logo"><i class="bi bi-person-plus"></i></div>
            <h2 class="fw-bold">Create Account</h2>
            <p class="text-muted small">Join SwarBazaar and start buying Bollywood music</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle me-2"></i><?= $error ?>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i><?= $success ?>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!$success): ?>
        <form action="register.php" method="POST" id="registerForm" novalidate>

            <div class="mb-3">
                <label for="fullName" class="form-label">Full Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="fullName" name="full_name"
                       placeholder="Alice Sharma" value="<?= h($_POST['full_name'] ?? '') ?>" required>
            </div>

            <div class="row g-3 mb-3">
                <div class="col">
                    <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="username" name="username"
                           placeholder="alice123" value="<?= h($_POST['username'] ?? '') ?>" required>
                </div>
                <div class="col">
                    <label for="phone" class="form-label">Phone</label>
                    <input type="tel" class="form-control" id="phone" name="phone"
                           placeholder="9876543210" value="<?= h($_POST['phone'] ?? '') ?>">
                </div>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                <input type="email" class="form-control" id="email" name="email"
                       placeholder="you@example.com" value="<?= h($_POST['email'] ?? '') ?>" required>
            </div>

            <div class="row g-3 mb-4">
                <div class="col">
                    <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" id="password" name="password"
                           placeholder="Min 6 characters" required>
                </div>
                <div class="col">
                    <label for="confirm" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" id="confirm" name="confirm"
                           placeholder="Repeat password" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold" id="registerSubmitBtn">
                <i class="bi bi-person-check me-2"></i>Create Account
            </button>
        </form>
        <?php endif; ?>

        <hr style="border-color:var(--border);margin:1.5rem 0">
        <p class="text-center text-muted small mb-0">
            Already have an account? <a href="login.php" class="fw-semibold">Login here</a>
        </p>
    </div>
</div>

<?php include ROOT_PATH . 'includes/footer.php'; ?>
