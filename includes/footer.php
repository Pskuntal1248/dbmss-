<?php /** Footer — included at bottom of every page */ ?>
</main><!-- /.main-content — opened in each page body -->

<footer class="site-footer mt-5">
    <div class="container">
        <div class="row gy-4">
            <div class="col-lg-4">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="bi bi-music-note-beamed fs-4 text-primary"></i>
                    <span class="fw-bold fs-5">SwarBazaar</span>
                </div>
                <p class="small text-muted">Your destination for authentic Bollywood songs in high-quality MP3 &amp; WAV formats.</p>
            </div>
            <div class="col-lg-2">
                <h6 class="fw-semibold mb-3">Shop</h6>
                <ul class="list-unstyled small">
                    <li><a href="<?= ROOT_URL ?>shop.php" class="footer-link">All Songs</a></li>
                    <li><a href="<?= ROOT_URL ?>shop.php?cat=Romantic" class="footer-link">Romantic</a></li>
                    <li><a href="<?= ROOT_URL ?>shop.php?cat=Party" class="footer-link">Party</a></li>
                    <li><a href="<?= ROOT_URL ?>shop.php?cat=Sufi" class="footer-link">Sufi &amp; Classical</a></li>
                </ul>
            </div>
            <div class="col-lg-2">
                <h6 class="fw-semibold mb-3">Account</h6>
                <ul class="list-unstyled small">
                    <?php if (!isLoggedIn()): ?>
                        <li><a href="<?= ROOT_URL ?>auth/login.php" class="footer-link">Login</a></li>
                        <li><a href="<?= ROOT_URL ?>auth/register.php" class="footer-link">Register</a></li>
                    <?php else: ?>
                        <li><a href="<?= ROOT_URL ?>customer/my_purchases.php" class="footer-link">My Library</a></li>
                        <li><a href="<?= ROOT_URL ?>auth/logout.php" class="footer-link">Logout</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="col-lg-4">
                <h6 class="fw-semibold mb-3">About This Project</h6>
                <p class="small text-muted">
                    A college DBMS project demonstrating normalized MySQL schema,
                    PHP PDO, role-based access control, and a Bootstrap 5 UI.
                </p>
            </div>
        </div>
        <hr class="footer-divider my-3">
        <div class="text-center small text-muted pb-3">
            &copy; <?= date('Y') ?> SwarBazaar &mdash; DBMS College Project &bull;
            Built with PHP + MySQL + Bootstrap 5
        </div>
    </div>
</footer>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script src="<?= ROOT_URL ?>assets/js/main.js"></script>
</body>
</html>
