<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../models/Organism.php';
requireResearcher();

$id    = (int)($_GET['id'] ?? 0);
$model = new Organism();
$org   = $model->findById($id);
if (!$org) { setFlash('error', 'Organism not found.'); redirect('views/researcher/organisms/index.php'); }

$compounds = $model->getCompounds($id);

$pageTitle = $org['scientific_name'];
include __DIR__ . '/../../layouts/header.php';
?>
<div class="d-flex flex-column min-vh-100">
<?php include __DIR__ . '/../../layouts/navbar_researcher.php'; ?>
<main class="flex-grow-1 py-4">
<div class="container px-4" style="max-width:900px">

    <div class="d-flex align-items-center mb-4">
        <a href="<?= BASE_URL ?>views/researcher/organisms/index.php" class="btn btn-outline-secondary me-3">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <h1 class="h3 fw-bold mb-0 fst-italic"><?= sanitize($org['scientific_name']) ?></h1>
            <small class="text-muted"><?= sanitize($org['kingdom']) ?> · <?= sanitize($org['phylum']) ?></small>
        </div>
    </div>

    <div class="row g-4">
        <!-- Structure Image -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">
                    <i class="bi bi-image text-success"></i> Structure
                </div>
                <div class="card-body text-center">
                    <?php if (!empty($org['structure_image'])): ?>
                        <img src="<?= BASE_URL ?>assets/uploads/organisms/<?= sanitize($org['structure_image']) ?>"
                             alt="<?= sanitize($org['scientific_name']) ?>"
                             class="img-fluid rounded" style="max-height:280px">
                    <?php else: ?>
                        <div class="text-muted py-5">
                            <i class="bi bi-image fs-1 d-block mb-2"></i>
                            No image available
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Taxonomy -->
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold">
                    <i class="bi bi-diagram-3 text-warning"></i> Taxonomic Classification
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped mb-0">
                        <tbody>
                            <tr><th class="ps-4" style="width:30%">Kingdom</th><td><?= sanitize($org['kingdom']) ?></td></tr>
                            <tr><th class="ps-4">Phylum</th><td><?= sanitize($org['phylum']) ?></td></tr>
                            <tr><th class="ps-4">Class</th><td><?= sanitize($org['class']) ?></td></tr>
                            <tr><th class="ps-4">Order</th><td><?= sanitize($org['order_name'] ?? '') ?: '<span class="text-muted">—</span>' ?></td></tr>
                            <tr><th class="ps-4">Family</th><td><?= sanitize($org['family'] ?? '') ?: '<span class="text-muted">—</span>' ?></td></tr>
                            <tr><th class="ps-4">Genus</th><td class="fst-italic"><?= sanitize($org['genus'] ?? '') ?: '<span class="text-muted">—</span>' ?></td></tr>
                            <tr><th class="ps-4">Species</th><td class="fst-italic"><?= sanitize($org['species'] ?? '') ?: '<span class="text-muted">—</span>' ?></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Additional Info -->
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold">
                    <i class="bi bi-info-circle text-info"></i> Details
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="text-muted small">Cell Type</label>
                            <div>
                                <?php if ($org['cell_type']): ?>
                                    <span class="badge bg-<?= $org['cell_type'] === 'eukaryotic' ? 'success' : 'info' ?>">
                                        <?= ucfirst($org['cell_type']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <label class="text-muted small">Habitat</label>
                            <div><?= sanitize($org['habitat'] ?? '') ?: '<span class="text-muted">—</span>' ?></div>
                        </div>
                        <div class="col-12">
                            <label class="text-muted small">Description</label>
                            <div><?= nl2br(sanitize($org['description'] ?? '')) ?: '<span class="text-muted">No description available.</span>' ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Linked Compounds -->
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold">
                    <i class="bi bi-capsule text-success"></i> Compounds from this Organism (<?= count($compounds) ?>)
                </div>
                <div class="card-body p-0">
                    <?php if (empty($compounds)): ?>
                        <p class="text-muted text-center py-4">No compounds linked to this organism.</p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr><th>Structure</th><th>Name</th><th>Formula</th><th>Molecular Weight</th></tr>
                            </thead>
                            <tbody>
                            <?php foreach ($compounds as $c): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($c['structure_image'])): ?>
                                        <img src="<?= BASE_URL ?>assets/uploads/compounds/<?= sanitize($c['structure_image']) ?>"
                                             alt="" class="rounded" style="width:50px;height:50px;object-fit:contain">
                                    <?php else: ?>
                                        <span class="d-inline-flex align-items-center justify-content-center rounded bg-light"
                                              style="width:50px;height:50px">
                                            <i class="bi bi-bezier2 text-muted"></i>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="fw-semibold"><?= sanitize($c['name']) ?></td>
                                <td><code><?= sanitize($c['formula']) ?></code></td>
                                <td><?= number_format($c['molecular_weight'], 4) ?> g/mol</td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</div>
</main>
<?php include __DIR__ . '/../../layouts/footer.php'; ?>
</div>
