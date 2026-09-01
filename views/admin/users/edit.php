<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../models/User.php';
requireAdmin();

$id   = (int)($_GET['id'] ?? 0);
$user = (new User())->findById($id);
if (!$user) { setFlash('error', 'User not found.'); redirect('views/admin/users/index.php'); }

$pageTitle = 'Edit User';
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
        <h1 class="h3 fw-bold mb-0">Edit: <?= sanitize($user['name']) ?></h1>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form method="POST" action="<?= BASE_URL ?>controllers/process.php" novalidate>
                <input type="hidden" name="action" value="update_user">
                <input type="hidden" name="id" value="<?= $id ?>">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

                <div class="mb-3">
                    <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required
                           value="<?= sanitize($user['name']) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" required
                           value="<?= sanitize($user['email']) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">New Password <span class="text-muted small">(leave blank to keep current)</span></label>
                    <div class="input-group">
                        <input type="password" name="password" id="editPass" class="form-control"
                               minlength="8" placeholder="Leave blank to keep current">
                        <button type="button" class="btn btn-outline-secondary toggle-password" data-target="editPass">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-semibold">Role <span class="text-danger">*</span></label>
                    <select name="role" class="form-select" <?= $id == $_SESSION['user_id'] ? 'disabled' : '' ?>>
                        <option value="researcher" <?= $user['role']==='researcher'?'selected':'' ?>>Researcher</option>
                        <option value="admin" <?= $user['role']==='admin'?'selected':'' ?>>Admin</option>
                    </select>
                    <?php if ($id == $_SESSION['user_id']): ?>
                        <input type="hidden" name="role" value="<?= $user['role'] ?>">
                        <div class="form-text text-warning">You cannot change your own role.</div>
                    <?php endif; ?>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-warning px-4">
                        <i class="bi bi-save"></i> Update User
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
