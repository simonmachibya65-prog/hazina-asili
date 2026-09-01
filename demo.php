<?php
require_once __DIR__ . '/config/config.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    redirect('views/researcher/dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo Access — <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', sans-serif;
        }

        .demo-card {
            border-radius: 1.25rem;
            border: none;
            overflow: hidden;
            transition: transform .2s ease, box-shadow .2s ease;
        }
        .demo-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 16px 40px rgba(0,0,0,.25) !important;
        }

        .admin-card  { border-top: 5px solid #dc3545; }
        .researcher-card { border-top: 5px solid #198754; }

        .role-badge {
            font-size: .7rem;
            letter-spacing: .5px;
            text-transform: uppercase;
            padding: .3rem .7rem;
            border-radius: 2rem;
        }

        .credential-row {
            background: #f8f9fa;
            border-radius: .5rem;
            padding: .5rem .75rem;
            font-family: monospace;
            font-size: .9rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn-login {
            border-radius: .75rem;
            font-weight: 600;
            letter-spacing: .3px;
            padding: .65rem 1.5rem;
            transition: all .2s ease;
        }

        .feature-item {
            font-size: .82rem;
            color: #6c757d;
            display: flex;
            align-items: flex-start;
            gap: .4rem;
            margin-bottom: .3rem;
        }
        .feature-item i { margin-top: 2px; flex-shrink: 0; }

        .app-title {
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: -1px;
            color: #fff;
        }
        .app-subtitle { color: rgba(255,255,255,.6); font-size: .95rem; }

        .divider-text {
            color: rgba(255,255,255,.4);
            font-size: .8rem;
            text-align: center;
            position: relative;
        }
        .divider-text::before,
        .divider-text::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 40%;
            height: 1px;
            background: rgba(255,255,255,.15);
        }
        .divider-text::before { left: 0; }
        .divider-text::after  { right: 0; }

        .toast-container { z-index: 9999; }
    </style>
</head>
<body class="d-flex flex-column align-items-center justify-content-center py-5 px-3">

    <!-- App branding -->
    <div class="text-center mb-4">
        <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-2"
             style="width:80px;height:80px;background:linear-gradient(135deg,#198754,#0f5132);box-shadow:0 8px 32px rgba(25,135,84,0.4)">
            <i class="bi bi-leaf text-white" style="font-size:2.2rem"></i>
        </div>
        <div class="app-title mt-1"><?= APP_NAME ?></div>
        <div class="app-subtitle">Natural Organic Compounds Database</div>
        <div class="app-subtitle mt-1">
            <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25 px-3 py-1">
                v<?= APP_VERSION ?> · Demo Mode
            </span>
        </div>
    </div>

    <!-- Demo cards -->
    <div class="row g-4 justify-content-center w-100" style="max-width:780px">

        <!-- ── Admin Card ─────────────────────────────────────── -->
        <div class="col-md-6">
            <div class="card demo-card admin-card shadow-lg h-100">
                <div class="card-body p-4">

                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                             style="width:52px;height:52px;background:#f8d7da">
                            <i class="bi bi-shield-lock text-danger fs-4"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0">Admin Account</h5>
                            <span class="role-badge bg-danger text-white">Full Access</span>
                        </div>
                    </div>

                    <!-- Credentials -->
                    <div class="mb-3">
                        <div class="credential-row mb-2">
                            <span class="text-muted small">Email</span>
                            <span class="fw-semibold">admin@hazina-asili.com</span>
                        </div>
                        <div class="credential-row">
                            <span class="text-muted small">Password</span>
                            <span class="fw-semibold">Admin@1234</span>
                        </div>
                    </div>

                    <!-- Features -->
                    <div class="mb-4">
                        <div class="feature-item"><i class="bi bi-check-circle-fill text-danger"></i> Manage compounds, organisms, references</div>
                        <div class="feature-item"><i class="bi bi-check-circle-fill text-danger"></i> Manage all users</div>
                        <div class="feature-item"><i class="bi bi-check-circle-fill text-danger"></i> Approve / reject insights &amp; recommendations</div>
                        <div class="feature-item"><i class="bi bi-check-circle-fill text-danger"></i> View audit trail &amp; version history</div>
                        <div class="feature-item"><i class="bi bi-check-circle-fill text-danger"></i> Analytics dashboard &amp; charts</div>
                        <div class="feature-item"><i class="bi bi-check-circle-fill text-danger"></i> Export CSV, generate reports, backup DB</div>
                    </div>

                    <button class="btn btn-danger btn-login w-100"
                            onclick="loginAs('admin@hazina-asili.com','Admin@1234')">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Login as Admin
                    </button>

                </div>
            </div>
        </div>

        <!-- ── Researcher Card ────────────────────────────────── -->
        <div class="col-md-6">
            <div class="card demo-card researcher-card shadow-lg h-100">
                <div class="card-body p-4">

                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                             style="width:52px;height:52px;background:#d1e7dd">
                            <i class="bi bi-person-workspace text-success fs-4"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0">Researcher Account</h5>
                            <span class="role-badge bg-success text-white">Limited Access</span>
                        </div>
                    </div>

                    <!-- Credentials -->
                    <div class="mb-3">
                        <div class="credential-row mb-2">
                            <span class="text-muted small">Email</span>
                            <span class="fw-semibold">researcher@hazina-asili.com</span>
                        </div>
                        <div class="credential-row">
                            <span class="text-muted small">Password</span>
                            <span class="fw-semibold">Admin@1234</span>
                        </div>
                    </div>

                    <!-- Features -->
                    <div class="mb-4">
                        <div class="feature-item"><i class="bi bi-check-circle-fill text-success"></i> Browse &amp; search compound database</div>
                        <div class="feature-item"><i class="bi bi-check-circle-fill text-success"></i> Advanced multi-filter search</div>
                        <div class="feature-item"><i class="bi bi-check-circle-fill text-success"></i> Submit insights on compounds</div>
                        <div class="feature-item"><i class="bi bi-check-circle-fill text-success"></i> Submit data change recommendations</div>
                        <div class="feature-item"><i class="bi bi-check-circle-fill text-success"></i> Track submission approval status</div>
                        <div class="feature-item"><i class="bi bi-check-circle-fill text-success"></i> Receive notifications on decisions</div>
                    </div>

                    <button class="btn btn-success btn-login w-100"
                            onclick="loginAs('researcher@hazina-asili.com','Admin@1234')">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Login as Researcher
                    </button>

                </div>
            </div>
        </div>

    </div>

    <!-- Divider -->
    <div class="divider-text w-100 my-4" style="max-width:780px">or login manually</div>

    <!-- Manual login link -->
    <a href="<?= BASE_URL ?>views/auth/login.php"
       class="btn btn-outline-light btn-login px-5">
        <i class="bi bi-person me-2"></i>Go to Login Page
    </a>

    <!-- Register link -->
    <div class="mt-3 text-center" style="color:rgba(255,255,255,.5);font-size:.85rem">
        Don't have an account?
        <a href="<?= BASE_URL ?>views/auth/register.php"
           class="text-info text-decoration-none fw-semibold">Register here</a>
    </div>

    <!-- Hidden auto-login form -->
    <form id="autoLoginForm" method="POST"
          action="<?= BASE_URL ?>controllers/process.php" style="display:none">
        <input type="hidden" name="action"     value="login">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="email"      id="autoEmail">
        <input type="hidden" name="password"   id="autoPassword">
    </form>

    <!-- Toast notification -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div id="loginToast" class="toast align-items-center text-white border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body" id="toastMsg">Logging in...</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto"
                        data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function loginAs(email, password) {
        // Show toast
        const toastEl  = document.getElementById('loginToast');
        const toastMsg = document.getElementById('toastMsg');
        const isAdmin  = email.includes('admin@');

        toastEl.classList.remove('bg-danger', 'bg-success');
        toastEl.classList.add(isAdmin ? 'bg-danger' : 'bg-success');
        toastMsg.innerHTML = '<i class="bi bi-box-arrow-in-right me-2"></i>Logging in as <strong>'
                           + (isAdmin ? 'Admin' : 'Researcher') + '</strong>...';

        const toast = new bootstrap.Toast(toastEl, { delay: 2000 });
        toast.show();

        // Submit form after short delay so toast is visible
        document.getElementById('autoEmail').value    = email;
        document.getElementById('autoPassword').value = password;

        setTimeout(function () {
            document.getElementById('autoLoginForm').submit();
        }, 800);
    }
    </script>

</body>
</html>
