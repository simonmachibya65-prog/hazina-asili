<?php
require_once __DIR__ . '/../../config/config.php';
if (isLoggedIn()) {
    redirect('views/researcher/dashboard.php');
}
$pageTitle = 'Login';
include __DIR__ . '/../layouts/header.php';
?>

<div class="min-vh-100 d-flex align-items-center py-5"
     style="background:linear-gradient(135deg,#0f2027,#203a43,#2c5364)">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5 col-lg-4">

                <div class="card shadow-lg border-0 rounded-4">
                    <div class="card-body p-5">

                        <!-- Brand -->
                        <div class="text-center mb-4">
                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3"
                                 style="width:120px;height:120px;background:linear-gradient(135deg,#198754,#0f5132);box-shadow:0 10px 40px rgba(25,135,84,0.35)"
                                 id="logoTrigger">
                                <span style="font-size:3.5rem">🌿</span>
                            </div>
                            <h3 class="fw-bold mb-1" style="letter-spacing:3px;color:#198754">HAZINA ASILI</h3>
                            <p class="text-muted mt-1" style="letter-spacing:.5px;font-size:.9rem">Natural Organic Compounds Database</p>
                        </div>

                        <?= renderFlash() ?>

                        <!-- Researcher Login Form (default visible) -->
                        <div id="loginForm">
                            <form method="POST" action="<?= BASE_URL ?>controllers/process.php" novalidate>
                                <input type="hidden" name="action" value="login">
                                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

                                <div class="mb-3">
                                    <label class="form-label fw-semibold small">Email Address</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0">
                                            <i class="bi bi-envelope text-muted"></i>
                                        </span>
                                        <input type="email" name="email" id="emailField"
                                               class="form-control border-start-0 ps-0"
                                               placeholder="you@example.com"
                                               required autocomplete="email">
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="form-label fw-semibold small mb-0">Password</label>
                                        <a href="<?= BASE_URL ?>views/auth/forgot_password.php"
                                           class="text-muted small text-decoration-none">
                                            Forgot password?
                                        </a>
                                    </div>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0">
                                            <i class="bi bi-lock text-muted"></i>
                                        </span>
                                        <input type="password" name="password" id="passwordField"
                                               class="form-control border-start-0 ps-0"
                                               placeholder="••••••••"
                                               required autocomplete="current-password">
                                        <button type="button"
                                                class="btn btn-light border border-start-0 toggle-password"
                                                data-target="passwordField">
                                            <i class="bi bi-eye text-muted"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="d-grid">
                                    <button type="submit"
                                            class="btn btn-success btn-lg fw-semibold rounded-3">
                                        <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                                    </button>
                                </div>
                            </form>
                        </div>

                        <hr class="my-4" id="registerLink">
                        <div class="text-center" id="registerSection">
                            <p class="text-muted small mb-3">Or continue with</p>
                            <div class="d-flex gap-2 justify-content-center mb-3">
                                <a href="<?= BASE_URL ?>controllers/oauth.php?provider=google" class="btn btn-outline-secondary btn-sm px-3">
                                    <svg width="16" height="16" viewBox="0 0 24 24" class="me-1"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                                    Google
                                </a>
                                <a href="<?= BASE_URL ?>controllers/oauth.php?provider=microsoft" class="btn btn-outline-secondary btn-sm px-3">
                                    <svg width="16" height="16" viewBox="0 0 24 24" class="me-1"><rect fill="#F25022" x="1" y="1" width="10" height="10"/><rect fill="#7FBA00" x="13" y="1" width="10" height="10"/><rect fill="#00A4EF" x="1" y="13" width="10" height="10"/><rect fill="#FFB900" x="13" y="13" width="10" height="10"/></svg>
                                    Microsoft
                                </a>
                            </div>
                            <p class="text-muted small mb-0">
                                Don't have an account?
                                <a href="<?= BASE_URL ?>views/auth/register.php"
                                   class="text-success fw-semibold text-decoration-none">
                                    Create one here
                                </a>
                            </p>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
