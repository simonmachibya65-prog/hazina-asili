<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../models/Organism.php';
requireResearcher();

$model  = new Organism();
$search = sanitize($_GET['search'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$total  = $model->countAll($search);
$pagination = paginate($total, $page);
$organisms  = $model->getAll($pagination['offset'], $pagination['per_page'], $search);

$pageTitle = 'Browse Organisms';
include __DIR__ . '/../../layouts/header.php';
?>
<div class="d-flex flex-column min-vh-100">
<?php include __DIR__ . '/../../layouts/navbar_researcher.php'; ?>
<main class="flex-grow-1 py-4">
<div class="container-fluid px-4">

    <?= renderFlash() ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 fw-bold mb-0"><i class="bi bi-tree text-warning"></i> Organisms</h1>
        <span class="badge bg-secondary fs-6"><?= $total ?> total</span>
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

    <div class="row g-3">
        <?php if (empty($organisms)): ?>
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center text-muted py-5">
                        <i class="bi bi-tree fs-1 d-block mb-2"></i>
                        No organisms found.
                    </div>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($organisms as $o): ?>
            <div class="col-md-6 col-lg-4">
                <a href="<?= BASE_URL ?>views/researcher/organisms/view.php?id=<?= $o['id'] ?>" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100 action-card">
                        <div class="card-body d-flex gap-3">
                            <?php if (!empty($o['structure_image'])): ?>
                                <img src="<?= BASE_URL ?>assets/uploads/organisms/<?= sanitize($o['structure_image']) ?>"
                                     alt="" class="rounded flex-shrink-0" style="width:60px;height:60px;object-fit:cover">
                            <?php else: ?>
                                <div class="d-flex align-items-center justify-content-center rounded bg-light flex-shrink-0"
                                     style="width:60px;height:60px">
                                    <i class="bi bi-tree text-muted fs-4"></i>
                                </div>
                            <?php endif; ?>
                            <div class="flex-grow-1 min-w-0">
                                <h6 class="fw-bold fst-italic mb-1 text-dark"><?= sanitize($o['scientific_name']) ?></h6>
                                <div class="text-muted small">
                                    <?= sanitize($o['kingdom']) ?>
                                    <?php if (!empty($o['family'])): ?> · <?= sanitize($o['family']) ?><?php endif; ?>
                                </div>
                                <?php if (!empty($o['habitat'])): ?>
                                    <div class="text-muted small mt-1 text-truncate">
                                        <i class="bi bi-geo-alt"></i> <?= sanitize($o['habitat']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php include __DIR__ . '/../../layouts/pagination.php'; ?>

</div>
</main>
<?php include __DIR__ . '/../../layouts/footer.php'; ?>
</div>
