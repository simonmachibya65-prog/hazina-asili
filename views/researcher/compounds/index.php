<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../models/Compound.php';
require_once __DIR__ . '/../../../models/Organism.php';
requireResearcher();

$model     = new Compound();
$organisms = (new Organism())->getAllSimple();

// ── Filters ───────────────────────────────────────────────────
$search  = sanitize($_GET['search'] ?? '');
$field   = in_array($_GET['field'] ?? '', ['name','formula','taxonomy']) ? $_GET['field'] : 'name';
$orgId   = (int)($_GET['organism_id'] ?? 0);
$mwMin   = (float)($_GET['mw_min'] ?? 0);
$mwMax   = (float)($_GET['mw_max'] ?? 0);
$sortBy  = in_array($_GET['sort'] ?? '', ['name','formula','molecular_weight','created_at']) ? $_GET['sort'] : 'name';
$sortDir = ($_GET['dir'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
$page    = max(1, (int)($_GET['page'] ?? 1));

$total      = $model->countAll($search, $field, $orgId, $mwMin, $mwMax);
$pagination = paginate($total, $page);
$compounds  = $model->getAll($pagination['offset'], $pagination['per_page'], $search, $field, $sortBy, $sortDir, $orgId, $mwMin, $mwMax);

$hasFilters = $search || $orgId || $mwMin || $mwMax;

$pageTitle = 'Browse Compounds';
include __DIR__ . '/../../layouts/header.php';
?>
<div class="d-flex flex-column min-vh-100">
<?php include __DIR__ . '/../../layouts/navbar_researcher.php'; ?>
<main class="flex-grow-1 py-4">
<div class="container-fluid px-4">

    <?= renderFlash() ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 fw-bold mb-0"><i class="bi bi-capsule text-success"></i> Compounds Database</h1>
        <span class="text-muted small"><?= number_format($total) ?> result(s)</span>
    </div>

    <!-- ── Advanced Search Panel ─────────────────────────────── -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <span class="fw-semibold"><i class="bi bi-funnel"></i> Search & Filter</span>
            <?php if ($hasFilters): ?>
            <a href="?" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x"></i> Clear All</a>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <!-- Text search -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Search Term</label>
                    <input type="text" name="search" class="form-control" placeholder="Search..."
                           value="<?= sanitize($search) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Search By</label>
                    <select name="field" class="form-select">
                        <option value="name"     <?= $field==='name'?'selected':'' ?>>Name / Description</option>
                        <option value="formula"  <?= $field==='formula'?'selected':'' ?>>Formula</option>
                        <option value="taxonomy" <?= $field==='taxonomy'?'selected':'' ?>>Taxonomy</option>
                    </select>
                </div>
                <!-- Organism filter -->
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Source Organism</label>
                    <select name="organism_id" class="form-select">
                        <option value="">All Organisms</option>
                        <?php foreach ($organisms as $o): ?>
                        <option value="<?= $o['id'] ?>" <?= $orgId==$o['id']?'selected':'' ?>>
                            <?= sanitize($o['scientific_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- MW range -->
                <div class="col-md-1">
                    <label class="form-label fw-semibold small">MW Min</label>
                    <input type="number" name="mw_min" class="form-control" placeholder="0"
                           step="0.01" min="0" value="<?= $mwMin ?: '' ?>">
                </div>
                <div class="col-md-1">
                    <label class="form-label fw-semibold small">MW Max</label>
                    <input type="number" name="mw_max" class="form-control" placeholder="∞"
                           step="0.01" min="0" value="<?= $mwMax ?: '' ?>">
                </div>
                <!-- Sort -->
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Sort By</label>
                    <div class="input-group">
                        <select name="sort" class="form-select">
                            <option value="name"             <?= $sortBy==='name'?'selected':'' ?>>Name</option>
                            <option value="molecular_weight" <?= $sortBy==='molecular_weight'?'selected':'' ?>>MW</option>
                            <option value="created_at"       <?= $sortBy==='created_at'?'selected':'' ?>>Date Added</option>
                        </select>
                        <select name="dir" class="form-select" style="max-width:70px">
                            <option value="ASC"  <?= $sortDir==='ASC'?'selected':'' ?>>↑</option>
                            <option value="DESC" <?= $sortDir==='DESC'?'selected':'' ?>>↓</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Active filter badges -->
    <?php if ($hasFilters): ?>
    <div class="mb-3 d-flex flex-wrap gap-2 align-items-center">
        <small class="text-muted">Active filters:</small>
        <?php if ($search): ?><span class="badge bg-success">Search: "<?= sanitize($search) ?>"</span><?php endif; ?>
        <?php if ($orgId): ?><span class="badge bg-warning text-dark">Organism filter active</span><?php endif; ?>
        <?php if ($mwMin): ?><span class="badge bg-info text-dark">MW ≥ <?= $mwMin ?></span><?php endif; ?>
        <?php if ($mwMax): ?><span class="badge bg-info text-dark">MW ≤ <?= $mwMax ?></span><?php endif; ?>
        <strong class="text-muted small"><?= $total ?> result(s)</strong>
    </div>
    <?php endif; ?>

    <!-- Results -->
    <div class="row g-3">
        <?php if (empty($compounds)): ?>
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5 text-muted">
                        <i class="bi bi-search fs-1 d-block mb-3"></i>
                        No compounds match your search. Try adjusting the filters.
                    </div>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($compounds as $c): ?>
            <div class="col-md-6 col-xl-4">
                <div class="card border-0 shadow-sm h-100 compound-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="card-title fw-bold mb-0"><?= sanitize($c['name']) ?></h5>
                            <code class="text-success small"><?= sanitize($c['formula']) ?></code>
                        </div>
                        <p class="text-muted small mb-1">
                            <i class="bi bi-speedometer2"></i> <?= number_format($c['molecular_weight'], 2) ?> g/mol
                        </p>
                        <?php if ($c['organism_name']): ?>
                        <p class="text-muted small mb-1">
                            <i class="bi bi-tree"></i> <em><?= sanitize($c['organism_name']) ?></em>
                        </p>
                        <?php endif; ?>
                        <?php if ($c['description']): ?>
                        <p class="card-text small text-muted mb-0">
                            <?= sanitize(mb_strimwidth($c['description'], 0, 100, '…')) ?>
                        </p>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer bg-white border-0 d-flex gap-2">
                        <a href="<?= BASE_URL ?>views/admin/compounds/view.php?id=<?= $c['id'] ?>"
                           class="btn btn-sm btn-outline-success flex-grow-1">
                            <i class="bi bi-eye"></i> View Details
                        </a>
                        <a href="<?= BASE_URL ?>views/researcher/insights/create.php?compound_id=<?= $c['id'] ?>"
                           class="btn btn-sm btn-outline-primary" title="Submit Insight">
                            <i class="bi bi-chat-square-text"></i>
                        </a>
                        <a href="<?= BASE_URL ?>views/researcher/recommendations/create.php?compound_id=<?= $c['id'] ?>"
                           class="btn btn-sm btn-outline-warning" title="Submit Recommendation">
                            <i class="bi bi-lightbulb"></i>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php include __DIR__ . '/../../layouts/pagination.php'; ?>

</div>
</main>
<?php include __DIR__ . '/../../layouts/footer.php'; ?>
</div>
