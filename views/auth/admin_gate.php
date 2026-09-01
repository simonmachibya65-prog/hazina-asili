<?php
require_once __DIR__ . '/../../config/config.php';
requireLogin(); // Must be logged in first

$pageTitle = 'Admin Access';
include __DIR__ . '/../layouts/header.php';
?>

<div class="min-vh-100 d-flex align-items-center py-5"
     style="background:linear-gradient(135deg,#1a1a2e,#16213e,#0f3460)">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5 col-lg-4">

                <div class="card shadow-lg border-0 rounded-4">
                    <div class="card-body p-5">

                        <div class="text-center mb-4">
                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3"
                                 style="width:120px;height:120px;background:linear-gradient(135deg,#dc3545,#842029);box-shadow:0 10px 40px rgba(220,53,69,0.35)">
                                <span style="font-size:3.5rem">🛡️</span>
                            </div>
                            <h3 class="fw-bold mb-1" style="letter-spacing:3px;color:#dc3545">HAZINA ASILI</h3>
                            <p class="text-muted mt-1" style="font-size:.9rem">Admin Access Portal</p>
                        </div>

                        <?= renderFlash() ?>

                        <form method="POST" action="<?= BASE_URL ?>controllers/process.php" novalidate>
                            <input type="hidden" name="action" value="admin_login">
                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

                            <div class="mb-3">
                                <label class="form-label fw-semibold small">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0">
                                        <i class="bi bi-person-lock text-danger"></i>
                                    </span>
                                    <input type="text" name="admin_username"
                                           class="form-control border-start-0 ps-0"
                                           placeholder="Enter admin username"
                                           required autocomplete="off" autofocus>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold small">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0">
                                        <i class="bi bi-key text-danger"></i>
                                    </span>
                                    <input type="password" name="admin_password" id="adminPwField"
                                           class="form-control border-start-0 ps-0"
                                           placeholder="Enter admin password"
                                           required autocomplete="off">
                                    <button type="button" class="btn btn-light border border-start-0 toggle-password"
                                            data-target="adminPwField">
                                        <i class="bi bi-eye text-muted"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-danger btn-lg fw-semibold rounded-3">
                                    <i class="bi bi-shield-lock me-2"></i>Admin Sign In
                                </button>
                            </div>

                            <div class="text-center">
                                <a href="<?= BASE_URL ?>views/researcher/dashboard.php" class="btn btn-link text-muted small">
                                    ← Back to Dashboard
                                </a>
                            </div>
                        </form>

                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
