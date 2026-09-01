<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../models/Compound.php';
requireAdmin();

$model  = new Compound();
$search = sanitize($_GET['search'] ?? '');
$field  = in_array($_GET['field'] ?? '', ['name','formula','taxonomy']) ? $_GET['field'] : 'name';
$page   = max(1, (int)($_GET['page'] ?? 1));
$total  = $model->countAll($search, $field);
$pagination = paginate($total, $page);
$compounds  = $model->getAll($pagination['offset'], $pagination['per_page'], $search, $field);

$pageTitle = 'Manage Compounds';
include __DIR__ . '/../../layouts/header.php';
?>
<div class="d-flex flex-column min-vh-100">
<?php include __DIR__ . '/../../layouts/navbar_admin.php'; ?>
<main class="flex-grow-1 py-4">
<div class="container-fluid px-4">

    <?= renderFlash() ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 fw-bold mb-0"><i class="bi bi-capsule text-success"></i> Compounds</h1>
        <a href="<?= BASE_URL ?>views/admin/compounds/create.php" class="btn btn-success">
            <i class="bi bi-plus-circle"></i> Add Compound
        </a>
    </div>

    <!-- Search -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label fw-semibold">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Search compounds..."
                           value="<?= sanitize($search) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Search By</label>
                    <select name="field" class="form-select">
                        <option value="name" <?= $field==='name'?'selected':'' ?>>Compound Name</option>
                        <option value="formula" <?= $field==='formula'?'selected':'' ?>>Molecular Formula</option>
                        <option value="taxonomy" <?= $field==='taxonomy'?'selected':'' ?>>Taxonomy</option>
                    </select>
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
                            <th>Name</th>
                            <th>Formula</th>
                            <th>Mol. Weight</th>
                            <th>Organism</th>
                            <th>Ver.</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($compounds)): ?>
                        <tr><td colspan="7" class="text-center py-5">
                            <div class="empty-state">
                                <i class="bi bi-capsule"></i>
                                No compounds found.
                                <a href="<?= BASE_URL ?>views/admin/compounds/create.php" class="d-block mt-2 btn btn-sm btn-success">
                                    <i class="bi bi-plus-circle"></i> Add First Compound
                                </a>
                            </div>
                        </td></tr>
                    <?php else: ?>
                        <?php foreach ($compounds as $i => $c): ?>
                        <tr data-href="<?= BASE_URL ?>views/admin/compounds/view.php?id=<?= $c['id'] ?>">
                            <td class="text-muted small"><?= $pagination['offset'] + $i + 1 ?></td>
                            <td class="fw-semibold"><?= sanitize($c['name']) ?></td>
                            <td><code><?= sanitize($c['formula']) ?></code></td>
                            <td><?= number_format($c['molecular_weight'], 2) ?> g/mol</td>
                            <td><?= $c['organism_name'] ? '<em class="text-success">'.sanitize($c['organism_name']).'</em>' : '<span class="text-muted">—</span>' ?></td>
                            <td><span class="badge bg-secondary">v<?= $c['version'] ?></span></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= BASE_URL ?>views/admin/compounds/view.php?id=<?= $c['id'] ?>"
                                       class="btn btn-outline-info"
                                       data-bs-toggle="tooltip" title="View Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>views/admin/compounds/history.php?id=<?= $c['id'] ?>"
                                       class="btn btn-outline-secondary"
                                       data-bs-toggle="tooltip" title="Version History">
                                        <i class="bi bi-clock-history"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>views/admin/compounds/edit.php?id=<?= $c['id'] ?>"
                                       class="btn btn-outline-warning"
                                       data-bs-toggle="tooltip" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-danger"
                                            onclick="confirmDelete(<?= $c['id'] ?>, '<?= sanitize($c['name']) ?>')"
                                            data-bs-toggle="tooltip" title="Delete">
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

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle"></i> Confirm Delete</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete <strong id="deleteItemName"></strong>? This cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" action="<?= BASE_URL ?>controllers/process.php" id="deleteForm">
                    <input type="hidden" name="action" value="delete_compound">
                    <input type="hidden" name="id" id="deleteItemId">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <button type="submit" class="btn btn-danger"><i class="bi bi-trash"></i> Delete</button>
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
