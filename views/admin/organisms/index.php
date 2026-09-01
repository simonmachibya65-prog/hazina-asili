<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../models/Organism.php';
requireAdmin();

$model  = new Organism();
$search = sanitize($_GET['search'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$total  = $model->countAll($search);
$pagination = paginate($total, $page);
$organisms  = $model->getAll($pagination['offset'], $pagination['per_page'], $search);

$pageTitle = 'Manage Organisms';
include __DIR__ . '/../../layouts/header.php';
?>
<div class="d-flex flex-column min-vh-100">
<?php include __DIR__ . '/../../layouts/navbar_admin.php'; ?>
<main class="flex-grow-1 py-4">
<div class="container-fluid px-4">

    <?= renderFlash() ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 fw-bold mb-0"><i class="bi bi-tree text-warning"></i> Organisms</h1>
        <a href="<?= BASE_URL ?>views/admin/organisms/create.php" class="btn btn-warning">
            <i class="bi bi-plus-circle"></i> Add Organism
        </a>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-6">
                    <input type="text" name="search" class="form-control"
                           placeholder="Search by name, kingdom, family, genus, habitat..."
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
                        <tr>
                            <th>#</th>
                            <th>Image</th>
                            <th>Scientific Name</th>
                            <th>Kingdom</th>
                            <th>Family</th>
                            <th>Genus</th>
                            <th>Cell Type</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($organisms)): ?>
                        <tr><td colspan="8" class="text-center text-muted py-4">No organisms found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($organisms as $i => $o): ?>
                        <tr>
                            <td class="text-muted small"><?= $pagination['offset'] + $i + 1 ?></td>
                            <td>
                                <?php if (!empty($o['structure_image'])): ?>
                                    <img src="<?= BASE_URL ?>assets/uploads/organisms/<?= sanitize($o['structure_image']) ?>"
                                         alt="" class="rounded" style="width:40px;height:40px;object-fit:cover">
                                <?php else: ?>
                                    <span class="d-inline-flex align-items-center justify-content-center rounded bg-light"
                                          style="width:40px;height:40px">
                                        <i class="bi bi-tree text-muted"></i>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="fw-semibold fst-italic"><?= sanitize($o['scientific_name']) ?></td>
                            <td><?= sanitize($o['kingdom']) ?></td>
                            <td><?= sanitize($o['family'] ?? '') ?: '<span class="text-muted">—</span>' ?></td>
                            <td class="fst-italic"><?= sanitize($o['genus'] ?? '') ?: '<span class="text-muted">—</span>' ?></td>
                            <td>
                                <?php if (!empty($o['cell_type'])): ?>
                                    <span class="badge bg-<?= $o['cell_type'] === 'eukaryotic' ? 'success' : 'info' ?> bg-opacity-75">
                                        <?= ucfirst($o['cell_type']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= BASE_URL ?>views/admin/organisms/view.php?id=<?= $o['id'] ?>"
                                       class="btn btn-outline-info" title="View"><i class="bi bi-eye"></i></a>
                                    <a href="<?= BASE_URL ?>views/admin/organisms/edit.php?id=<?= $o['id'] ?>"
                                       class="btn btn-outline-warning" title="Edit"><i class="bi bi-pencil"></i></a>
                                    <button type="button" class="btn btn-outline-danger" title="Delete"
                                            onclick="confirmDelete(<?= $o['id'] ?>, '<?= sanitize($o['scientific_name']) ?>')">
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
            <div class="modal-body">Delete organism <strong id="deleteItemName"></strong>?</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" action="<?= BASE_URL ?>controllers/process.php">
                    <input type="hidden" name="action" value="delete_organism">
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
