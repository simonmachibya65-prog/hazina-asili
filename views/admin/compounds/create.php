<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../models/Organism.php';
require_once __DIR__ . '/../../../models/Reference.php';
requireAdmin();

$organisms  = (new Organism())->getAllSimple();
$references = (new Reference())->getAllSimple();
$pageTitle  = 'Add Compound';
include __DIR__ . '/../../layouts/header.php';
?>
<div class="d-flex flex-column min-vh-100">
<?php include __DIR__ . '/../../layouts/navbar_admin.php'; ?>
<main class="flex-grow-1 py-4">
<div class="container px-4" style="max-width:800px">

    <?= renderFlash() ?>

    <div class="d-flex align-items-center mb-4">
        <a href="<?= BASE_URL ?>views/admin/compounds/index.php" class="btn btn-outline-secondary me-3">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h1 class="h3 fw-bold mb-0">Add New Compound</h1>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form method="POST" action="<?= BASE_URL ?>controllers/process.php" enctype="multipart/form-data" novalidate>
                <input type="hidden" name="action" value="create_compound">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Compound Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Quercetin" required minlength="2">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Molecular Formula <span class="text-danger">*</span></label>
                        <input type="text" name="formula" class="form-control" placeholder="e.g. C15H10O7" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Molecular Weight (g/mol) <span class="text-danger">*</span></label>
                        <input type="number" name="molecular_weight" class="form-control" placeholder="e.g. 302.24"
                               step="0.001" min="0.001" required>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Source Organism</label>
                        <select name="organism_id" class="form-select">
                            <option value="">— Select Organism (optional) —</option>
                            <?php foreach ($organisms as $org): ?>
                            <option value="<?= $org['id'] ?>"><?= sanitize($org['scientific_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" class="form-control" rows="4"
                                  placeholder="Biological activity, properties, uses..."></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold"><i class="bi bi-bezier2 text-primary"></i> Molecular Structure Image</label>
                        <input type="file" name="structure_image" class="form-control" accept="image/*,.svg">
                        <div class="form-text">Upload a 2D structure diagram (JPG, PNG, SVG, WEBP). Max 5MB.</div>
                    </div>
                    <?php if (!empty($references)): ?>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Attach References</label>
                        <div class="border rounded p-3" style="max-height:200px;overflow-y:auto">
                            <?php foreach ($references as $ref): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="reference_ids[]"
                                       value="<?= $ref['id'] ?>" id="ref<?= $ref['id'] ?>">
                                <label class="form-check-label small" for="ref<?= $ref['id'] ?>">
                                    <?= sanitize($ref['author']) ?> (<?= $ref['year'] ?>) — <?= sanitize($ref['title']) ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-success px-4">
                        <i class="bi bi-check-circle"></i> Save Compound
                    </button>
                    <a href="<?= BASE_URL ?>views/admin/compounds/index.php" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
</main>
<?php include __DIR__ . '/../../layouts/footer.php'; ?>
</div>
