<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../models/User.php';
requireAdmin();

$model  = new User();
$search = sanitize($_GET['search'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$total  = $model->countAll($search);
$pagination = paginate($total, $page);
$users  = $model->getAll($pagination['offset'], $pagination['per_page'], $search);

$pageTitle = 'Manage Users';
include __DIR__ . '/../../layouts/header.php';
?>
<div class="d-flex flex-column min-vh-100">
<?php include __DIR__ . '/../../layouts/navbar_admin.php'; ?>
<main class="flex-grow-1 py-4">
<div class="container-fluid px-4">

    <?= renderFlash() ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 fw-bold mb-0"><i class="bi bi-people text-primary"></i> Users</h1>
        <a href="<?= BASE_URL ?>views/admin/users/create.php" class="btn btn-primary">
            <i class="bi bi-person-plus"></i> Add User
        </a>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-6">
                    <input type="text" name="search" class="form-control" placeholder="Search by name or email..."
                           value="<?= sanitize($search) ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i> Search</button>
                </div>
                <?php if ($search): ?>
                <div class="col-md-2">
                    <a href="?" class="btn btn-outline-secondary w-100"><i class="bi bi-x"></i> Clear</a>
                </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr><th>#</th><th>Name</th><th>Email</th><th>Role</th><th>Joined</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                    <?php if (empty($users)): ?>
                        <tr><td colspan="6" class="text-center py-5">
                            <div class="empty-state">
                                <i class="bi bi-people"></i>
                                No users found.
                                <a href="<?= BASE_URL ?>views/admin/users/create.php" class="d-block mt-2 btn btn-sm btn-primary">
                                    <i class="bi bi-person-plus"></i> Add First User
                                </a>
                            </div>
                        </td></tr>
                    <?php else: ?>
                        <?php foreach ($users as $i => $u): ?>
                        <tr>
                            <td class="text-muted small"><?= $pagination['offset'] + $i + 1 ?></td>
                            <td class="fw-semibold">
                                <?= sanitize($u['name']) ?>
                                <?php if ($u['id'] == $_SESSION['user_id']): ?>
                                    <span class="badge bg-info ms-1">You</span>
                                <?php endif; ?>
                            </td>
                            <td><?= sanitize($u['email']) ?></td>
                            <td>
                                <span class="badge <?= $u['role'] === ROLE_ADMIN ? 'bg-danger' : 'bg-success' ?>">
                                    <i class="bi bi-<?= $u['role'] === ROLE_ADMIN ? 'shield-lock' : 'person-workspace' ?> me-1"></i>
                                    <?= ucfirst($u['role']) ?>
                                </span>
                            </td>
                            <td class="text-muted small"><?= formatDate($u['created_at']) ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= BASE_URL ?>views/admin/users/edit.php?id=<?= $u['id'] ?>"
                                       class="btn btn-outline-warning"
                                       data-bs-toggle="tooltip" title="Edit User">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                    <button type="button" class="btn btn-outline-danger"
                                            onclick="confirmDelete(<?= $u['id'] ?>, '<?= sanitize($u['name']) ?>')"
                                            data-bs-toggle="tooltip" title="Delete User">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    <?php else: ?>
                                    <button class="btn btn-outline-secondary" disabled
                                            data-bs-toggle="tooltip" title="Cannot delete your own account">
                                        <i class="bi bi-lock"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../../layouts/pagination.php'; ?>

</div>
</main>

<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle"></i> Confirm Delete</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">Delete user <strong id="deleteItemName"></strong>? This cannot be undone.</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" action="<?= BASE_URL ?>controllers/process.php">
                    <input type="hidden" name="action" value="delete_user">
                    <input type="hidden" name="id" id="deleteItemId">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
function confirmDelete(id, name) {
    document.getElementById('deleteItemId').value = id;
    document.getElementById('deleteItemName').textContent = name;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>
<?php include __DIR__ . '/../../layouts/footer.php'; ?>
</div>
