<?php
require_once __DIR__ . '/../../../config/config.php';
requireAdmin();
$pageTitle = 'Add User';
include __DIR__ . '/../../layouts/header.php';
?>
<div class="d-flex flex-column min-vh-100">
<?php include __DIR__ . '/../../layouts/navbar_admin.php'; ?>
<main class="flex-grow-1 py-4">
<div class="container px-4" style="max-width:600px">

    <?= renderFlash() ?>

    <div class="d-flex align-items-center mb-4">
        <a href="<?= BASE_URL ?>views/admin/users/index.php" class="btn btn-outline-secondary me-3">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h1 class="h3 fw-bold mb-0">Add New User</h1>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form method="POST" action="<?= BASE_URL ?>controllers/process.php" novalidate>
                <input type="hidden" name="action" value="create_user">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

                <div class="mb-3">
                    <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required minlength="2" placeholder="Full name">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" required placeholder="email@example.com">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="password" name="password" id="userPass" class="form-control"
                               required minlength="8" placeholder="Min 8 characters">
                        <button type="button" class="btn btn-outline-secondary toggle-password" data-target="userPass">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-semibold">Role <span class="text-danger">*</span></label>
                    <select name="role" class="form-select" required>
                        <option value="researcher">Researcher</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-person-plus"></i> Create User
                    </button>
                    <a href="<?= BASE_URL ?>views/admin/users/index.php" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
</main>
<?php include __DIR__ . '/../../layouts/footer.php'; ?>
</div>
