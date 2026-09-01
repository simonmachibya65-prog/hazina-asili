<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../models/Compound.php';
require_once __DIR__ . '/../../../models/Organism.php';
requireLogin();

$compoundModel = new Compound();
$orgModel      = new Organism();

// Filter params
$search  = sanitize($_GET['search'] ?? '');
$field   = sanitize($_GET['field'] ?? '');
$orgId   = (int)($_GET['organism_id'] ?? 0);
$mwMin   = (float)($_GET['mw_min'] ?? 0);
$mwMax   = (float)($_GET['mw_max'] ?? 0);
$sortBy  = sanitize($_GET['sort'] ?? 'name');
$sortDir = sanitize($_GET['dir'] ?? 'ASC');
$page    = max(1, (int)($_GET['page'] ?? 1));

$total      = $compoundModel->countAll($search, $field, $orgId, $mwMin, $mwMax);
$pagination = paginate($total, $page);
$compounds  = $compoundModel->getAll($pagination['offset'], $pagination['per_page'], $search, $field, $sortBy, $sortDir, $orgId, $mwMin, $mwMax);
$organisms  = $orgModel->getAllSimple();
$kingdoms   = $orgModel->getDistinctKingdoms();

$pageTitle = 'Advanced Search';
$navFile = isAdmin() && !empty($_SESSION['admin_secret_access']) ? __DIR__ . '/../../layouts/navbar_admin.php' : __DIR__ . '/../../layouts/navbar_researcher.php';
include __DIR__ . '/../../layouts/header.php';
?>
<div class="d-flex flex-column min-vh-100">
<?php include $navFile; ?>
<main class="flex-grow-1 py-4">
<div class="container-fluid px-4">

    <?= renderFlash() ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 fw-bold mb-0"><i class="bi bi-funnel text-primary"></i> Advanced Search</h1>
        <span class="badge bg-primary fs-6"><?= $total ?> result(s)</span>
    </div>

    <!-- Filter Panel -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <!-- Text search -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Search Text</label>
                    <input type="text" name="search" class="form-control" placeholder="Name, formula, or description..."
                           value="<?= sanitize($search) ?>">
                </div>
                <!-- Search field -->
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Search In</label>
                    <select name="field" class="form-select">
                        <option value="" <?= !$field ? 'selected' : '' ?>>All Fields</option>
                        <option value="formula" <?= $field === 'formula' ? 'selected' : '' ?>>Formula Only</option>
                        <option value="taxonomy" <?= $field === 'taxonomy' ? 'selected' : '' ?>>Taxonomy</option>
                    </select>
                </div>
                <!-- Organism filter -->
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Source Organism</label>
                    <select name="organism_id" class="form-select">
                        <option value="0">— All Organisms —</option>
                        <?php foreach ($organisms as $org): ?>
                        <option value="<?= $org['id'] ?>" <?= $orgId == $org['id'] ? 'selected' : '' ?>>
                            <?= sanitize($org['scientific_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- Sort -->
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Sort By</label>
                    <div class="input-group">
                        <select name="sort" class="form-select">
                            <option value="name" <?= $sortBy === 'name' ? 'selected' : '' ?>>Name</option>
                            <option value="formula" <?= $sortBy === 'formula' ? 'selected' : '' ?>>Formula</option>
                            <option value="molecular_weight" <?= $sortBy === 'molecular_weight' ? 'selected' : '' ?>>MW</option>
                            <option value="created_at" <?= $sortBy === 'created_at' ? 'selected' : '' ?>>Date Added</option>
                        </select>
                        <select name="dir" class="form-select" style="max-width:90px">
                            <option value="ASC" <?= $sortDir === 'ASC' ? 'selected' : '' ?>>↑ Asc</option>
                            <option value="DESC" <?= $sortDir === 'DESC' ? 'selected' : '' ?>>↓ Desc</option>
                        </select>
                    </div>
                </div>
                <!-- MW Range -->
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Min MW (g/mol)</label>
                    <input type="number" name="mw_min" class="form-control" step="0.01" min="0"
                           placeholder="0" value="<?= $mwMin ?: '' ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Max MW (g/mol)</label>
                    <input type="number" name="mw_max" class="form-control" step="0.01" min="0"
                           placeholder="∞" value="<?= $mwMax ?: '' ?>">
                </div>
                <!-- Buttons -->
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary me-2"><i class="bi bi-search"></i> Search</button>
                    <a href="?" class="btn btn-outline-secondary"><i class="bi bi-x-circle"></i> Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Results Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Structure</th>
                            <th>Name</th>
                            <th>Formula</th>
                            <th>MW (g/mol)</th>
                            <th>Organism</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($compounds)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">No compounds match your filters.</td></tr>
                    <?php else: ?>
                        <?php foreach ($compounds as $i => $c): ?>
                        <tr>
                            <td class="text-muted small"><?= $pagination['offset'] + $i + 1 ?></td>
                            <td>
                                <?php if (!empty($c['structure_image'])): ?>
                                    <img src="<?= BASE_URL ?>assets/uploads/compounds/<?= sanitize($c['structure_image'] ?? '') ?>"
                                         alt="" class="rounded" style="width:40px;height:40px;object-fit:contain">
                                <?php else: ?>
                                    <span class="d-inline-flex align-items-center justify-content-center rounded bg-light"
                                          style="width:40px;height:40px"><i class="bi bi-bezier2 text-muted"></i></span>
                                <?php endif; ?>
                            </td>
                            <td class="fw-semibold"><?= sanitize($c['name']) ?></td>
                            <td><code><?= sanitize($c['formula']) ?></code></td>
                            <td><?= number_format($c['molecular_weight'], 2) ?></td>
                            <td class="fst-italic small"><?= sanitize($c['organism_name'] ?? '—') ?></td>
                            <td>
                                <a href="<?= BASE_URL ?>views/admin/compounds/view.php?id=<?= $c['id'] ?>"
                                   class="btn btn-sm btn-outline-success" title="View"><i class="bi bi-eye"></i></a>
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
<?php include __DIR__ . '/../../layouts/footer.php'; ?>
</div>
