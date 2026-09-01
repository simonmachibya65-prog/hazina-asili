<?php
require_once __DIR__ . '/../../config/config.php';
if (isLoggedIn()) redirect('views/researcher/dashboard.php');
$pageTitle = 'Register';
include __DIR__ . '/../layouts/header.php';
?>

<div class="min-vh-100 d-flex align-items-center bg-light py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow-lg border-0 rounded-4">
                    <div class="card-body p-5">

                        <!-- Header -->
                        <div class="text-center mb-4">
                            <i class="bi bi-flower1 text-success" style="font-size:2.5rem"></i>
                            <h2 class="fw-bold mt-2">Create Account</h2>
                            <p class="text-muted small">Join as a researcher to browse and contribute</p>
                        </div>

                        <?= renderFlash() ?>

                        <form method="POST" action="<?= BASE_URL ?>controllers/process.php"
                              novalidate id="registerForm">
                            <input type="hidden" name="action" value="register">
                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                            <!-- Always register as researcher -->
                            <input type="hidden" name="role" value="researcher">

                            <!-- Full Name -->
                            <div class="mb-3">
                                <label class="form-label fw-semibold">
                                    Full Name <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input type="text" name="name" class="form-control"
                                           placeholder="Dr. Jane Smith"
                                           required minlength="2"
                                           value="<?= sanitize($_POST['name'] ?? '') ?>">
                                </div>
                            </div>

                            <!-- Email -->
                            <div class="mb-3">
                                <label class="form-label fw-semibold">
                                    Email Address <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                    <input type="email" name="email" class="form-control"
                                           placeholder="you@example.com"
                                           required
                                           value="<?= sanitize($_POST['email'] ?? '') ?>">
                                </div>
                            </div>

                            <!-- Institution -->
                            <div class="mb-3">
                                <label class="form-label fw-semibold">
                                    Institution
                                    <span class="text-muted fw-normal small">(optional)</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-building"></i></span>
                                    <input type="text" name="institution" class="form-control"
                                           placeholder="University / Research Institute"
                                           value="<?= sanitize($_POST['institution'] ?? '') ?>">
                                </div>
                            </div>

                            <!-- Password -->
                            <div class="mb-3">
                                <label class="form-label fw-semibold">
                                    Password <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" name="password" id="regPassword"
                                           class="form-control"
                                           placeholder="Min 8 chars, 1 uppercase, 1 number, 1 special"
                                           required minlength="8">
                                    <button type="button"
                                            class="btn btn-outline-secondary toggle-password"
                                            data-target="regPassword">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <div class="form-text">
                                    At least 8 characters, one uppercase letter, one number, one special character.
                                </div>
                            </div>

                            <!-- Confirm Password -->
                            <div class="mb-4">
                                <label class="form-label fw-semibold">
                                    Confirm Password <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                    <input type="password" name="confirm_password" id="regConfirm"
                                           class="form-control"
                                           placeholder="Repeat password" required>
                                </div>
                            </div>

                            <!-- Submit -->
                            <div class="d-grid">
                                <button type="submit" class="btn btn-success btn-lg fw-semibold">
                                    <i class="bi bi-person-check me-1"></i> Register as Researcher
                                </button>
                            </div>

                        </form>

                        <hr class="my-4">
                        <div class="text-center">
                            <p class="text-muted small mb-3">Or sign up with</p>
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
                            <div class="small">
                                Already have an account?
                                <a href="<?= BASE_URL ?>views/auth/login.php"
                                   class="text-success fw-semibold">Sign in</a>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
