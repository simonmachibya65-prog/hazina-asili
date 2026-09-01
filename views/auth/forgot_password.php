<?php
require_once __DIR__ . '/../../config/config.php';
$pageTitle = 'Forgot Password';
include __DIR__ . '/../layouts/header.php';
?>
<div class="min-vh-100 d-flex align-items-center bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5 col-lg-4">
                <div class="card shadow-lg border-0 rounded-4">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="bi bi-key text-warning" style="font-size:2.5rem"></i>
                            <h2 class="fw-bold mt-2">Forgot Password</h2>
                            <p class="text-muted small">Enter your email to receive a reset link</p>
                        </div>

                        <?= renderFlash() ?>

                        <?php if (isset($_SESSION['demo_reset_token'])): ?>
                        <div class="alert alert-info small">
                            <strong>Demo Mode:</strong> Reset token generated.<br>
                            <a href="<?= BASE_URL ?>views/auth/reset_password.php?token=<?= sanitize($_SESSION['demo_reset_token']) ?>" class="alert-link">
                                Click here to reset password
                            </a>
                        </div>
                        <?php endif; ?>

                        <form method="POST" action="<?= BASE_URL ?>controllers/process.php">
                            <input type="hidden" name="action" value="request_reset">
                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

                            <div class="mb-4">
                                <label class="form-label fw-semibold">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                    <input type="email" name="email" class="form-control" placeholder="you@example.com" required>
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-warning btn-lg">
                                    <i class="bi bi-send"></i> Send Reset Link
                                </button>
                            </div>
                        </form>

                        <hr class="my-4">
                        <div class="text-center small">
                            <a href="<?= BASE_URL ?>views/auth/login.php" class="text-muted">
                                <i class="bi bi-arrow-left"></i> Back to Login
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../layouts/footer.php'; ?>
