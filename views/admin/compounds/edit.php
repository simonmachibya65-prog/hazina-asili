<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../models/Compound.php';
require_once __DIR__ . '/../../../models/Organism.php';
require_once __DIR__ . '/../../../models/Reference.php';
requireAdmin();

$id = (int)($_GET['id'] ?? 0);
$model = new Compound();
$compound = $model->findById($id);
if (!$compound) { setFlash('error', 'Compound not found.'); redirect('views/admin/compounds/index.php'); }

$organisms  = (new Organism())->getAllSimple();
$references = (new Reference())->getAllSimple();
$attached   = array_column($model->getReferences($id), 'id');
$pageTitle  = 'Edit Compound';
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
        <h1 class="h3 fw-bold mb-0">Edit: <?= sanitize($compound['name']) ?></h1>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form method="POST" action="<?= BASE_URL ?>controllers/process.php" enctype="multipart/form-data" novalidate>
                <input type="hidden" name="action" value="update_compound">
                <input type="hidden" name="id" value="<?= $id ?>">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Compound Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required
                               value="<?= sanitize($compound['name']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Molecular Formula <span class="text-danger">*</span></label>
                        <input type="text" name="formula" class="form-control" required
                               value="<?= sanitize($compound['formula']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Molecular Weight (g/mol) <span class="text-danger">*</span></label>
                        <input type="number" name="molecular_weight" class="form-control" step="0.001" min="0.001" required
                               value="<?= $compound['molecular_weight'] ?>">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Source Organism</label>
                        <select name="organism_id" class="form-select">
                            <option value="">— None —</option>
                            <?php foreach ($organisms as $org): ?>
                            <option value="<?= $org['id'] ?>" <?= $compound['organism_id'] == $org['id'] ? 'selected' : '' ?>>
                                <?= sanitize($org['scientific_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" class="form-control" rows="4"><?= sanitize($compound['description']) ?></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold"><i class="bi bi-bezier2 text-primary"></i> Molecular Structure Image</label>
                        <?php if (!empty($compound['structure_image'])): ?>
                        <div class="mb-2 d-flex align-items-center gap-3">
                            <img src="<?= BASE_URL ?>assets/uploads/compounds/<?= sanitize($compound['structure_image']) ?>"
                                 alt="Structure" class="rounded border" style="max-height:100px;max-width:180px">
                            <div class="form-check">
                                <input type="checkbox" name="remove_structure_image" value="1" class="form-check-input" id="removeStructImg">
                                <label class="form-check-label text-danger" for="removeStructImg">Remove current image</label>
                            </div>
                        </div>
                        <?php endif; ?>
                        <input type="file" name="structure_image" class="form-control" accept="image/*,.svg">
                        <div class="form-text">Upload a 2D structure diagram (JPG, PNG, SVG, WEBP). Max 5MB.</div>
                    </div>
                    <?php if (!empty($references)): ?>
                    <div class="col-12">
                        <label class="form-label fw-semibold">References</label>
                        <div class="border rounded p-3" style="max-height:200px;overflow-y:auto">
                            <?php foreach ($references as $ref): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="reference_ids[]"
                                       value="<?= $ref['id'] ?>" id="ref<?= $ref['id'] ?>"
                                       <?= in_array($ref['id'], $attached) ? 'checked' : '' ?>>
                                <label class="form-check-label small" for="ref<?= $ref['id'] ?>">
                                    <?= sanitize($ref['author']) ?> (<?= $ref['year'] ?>) — <?= sanitize($ref['title']) ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Change Summary <span class="text-muted small">(for version history)</span></label>
                    <input type="text" name="change_summary" class="form-control"
                           placeholder="e.g. Corrected molecular weight, Updated description..."
                           value="Updated via admin panel">
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-warning px-4">
                        <i class="bi bi-save"></i> Update Compound
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
