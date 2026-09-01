<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../models/Reference.php';
requireAdmin();

$model  = new Reference();
$search = sanitize($_GET['search'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$total  = $model->countAll($search);
$pagination = paginate($total, $page);
$refs   = $model->getAll($pagination['offset'], $pagination['per_page'], $search);

$pageTitle = 'Manage References';
include __DIR__ . '/../../layouts/header.php';
?>
<div class="d-flex flex-column min-vh-100">
<?php include __DIR__ . '/../../layouts/navbar_admin.php'; ?>
<main class="flex-grow-1 py-4">
<div class="container-fluid px-4">

    <?= renderFlash() ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 fw-bold mb-0"><i class="bi bi-journal-text text-info"></i> References</h1>
        <a href="<?= BASE_URL ?>views/admin/references/create.php" class="btn btn-info text-white">
            <i class="bi bi-plus-circle"></i> Add Reference
        </a>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-6">
                    <input type="text" name="search" class="form-control"
                           placeholder="Search by title, author, or citation..."
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
                        <tr><th>#</th><th>Title</th><th>Author</th><th>Year</th><th>Citation</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                    <?php if (empty($refs)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">No references found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($refs as $i => $r): ?>
                        <tr>
                            <td class="text-muted small"><?= $pagination['offset'] + $i + 1 ?></td>
                            <td class="fw-semibold"><?= sanitize($r['title']) ?></td>
                            <td><?= sanitize($r['author']) ?></td>
                            <td><span class="badge bg-secondary"><?= $r['year'] ?></span></td>
                            <td class="text-muted small" style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                                <?= sanitize($r['citation']) ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= BASE_URL ?>views/admin/references/edit.php?id=<?= $r['id'] ?>"
                                       class="btn btn-outline-warning"><i class="bi bi-pencil"></i></a>
                                    <button type="button" class="btn btn-outline-danger"
                                            onclick="confirmDelete(<?= $r['id'] ?>, '<?= sanitize($r['title']) ?>')">
                                        <i class="bi bi-trash"></i>
                                    </button>
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
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">Delete reference <strong id="deleteItemName"></strong>?</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" action="<?= BASE_URL ?>controllers/process.php">
                    <input type="hidden" name="action" value="delete_reference">
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
