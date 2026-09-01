<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/User.php';

$token = sanitize($_GET['token'] ?? '');
$userModel = new User();
$user = $userModel->findByResetToken($token);

if (!$user) {
    setFlash('error', 'Invalid or expired reset token.');
    redirect('views/auth/forgot_password.php');
}

$pageTitle = 'Reset Password';
include __DIR__ . '/../layouts/header.php';
?>
<div class="min-vh-100 d-flex align-items-center bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5 col-lg-4">
                <div class="card shadow-lg border-0 rounded-4">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="bi bi-shield-lock text-success" style="font-size:2.5rem"></i>
                            <h2 class="fw-bold mt-2">Reset Password</h2>
                            <p class="text-muted small">For: <?= sanitize($user['email']) ?></p>
                        </div>

                        <?= renderFlash() ?>

                        <form method="POST" action="<?= BASE_URL ?>controllers/process.php">
                            <input type="hidden" name="action" value="reset_password">
                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                            <input type="hidden" name="token" value="<?= sanitize($token) ?>">

                            <div class="mb-3">
                                <label class="form-label fw-semibold">New Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" name="password" id="newPass" class="form-control"
                                           placeholder="Min 8 characters" required minlength="8">
                                    <button type="button" class="btn btn-outline-secondary toggle-password" data-target="newPass">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold">Confirm Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                    <input type="password" name="confirm_password" class="form-control"
                                           placeholder="Repeat password" required>
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="bi bi-check-circle"></i> Reset Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../layouts/footer.php'; ?>
