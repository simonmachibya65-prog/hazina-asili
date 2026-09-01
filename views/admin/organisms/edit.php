<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../models/Organism.php';
requireAdmin();

$id  = (int)($_GET['id'] ?? 0);
$org = (new Organism())->findById($id);
if (!$org) { setFlash('error', 'Organism not found.'); redirect('views/admin/organisms/index.php'); }

$pageTitle = 'Edit Organism';
include __DIR__ . '/../../layouts/header.php';
?>
<div class="d-flex flex-column min-vh-100">
<?php include __DIR__ . '/../../layouts/navbar_admin.php'; ?>
<main class="flex-grow-1 py-4">
<div class="container px-4" style="max-width:750px">

    <?= renderFlash() ?>

    <div class="d-flex align-items-center mb-4">
        <a href="<?= BASE_URL ?>views/admin/organisms/index.php" class="btn btn-outline-secondary me-3">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h1 class="h3 fw-bold mb-0">Edit Organism</h1>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form method="POST" action="<?= BASE_URL ?>controllers/process.php" enctype="multipart/form-data" novalidate>
                <input type="hidden" name="action" value="update_organism">
                <input type="hidden" name="id" value="<?= $id ?>">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

                <!-- Scientific Name -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Scientific Name <span class="text-danger">*</span></label>
                    <input type="text" name="scientific_name" class="form-control fst-italic"
                           required value="<?= sanitize($org['scientific_name']) ?>">
                </div>

                <hr class="my-4">
                <h5 class="fw-semibold mb-3"><i class="bi bi-diagram-3 text-warning"></i> Taxonomic Classification</h5>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Kingdom <span class="text-danger">*</span></label>
                        <input type="text" name="kingdom" class="form-control" required value="<?= sanitize($org['kingdom']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Phylum <span class="text-danger">*</span></label>
                        <input type="text" name="phylum" class="form-control" required value="<?= sanitize($org['phylum']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Class <span class="text-danger">*</span></label>
                        <input type="text" name="class" class="form-control" required value="<?= sanitize($org['class']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Order</label>
                        <input type="text" name="order_name" class="form-control" value="<?= sanitize($org['order_name'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Family</label>
                        <input type="text" name="family" class="form-control" value="<?= sanitize($org['family'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Genus</label>
                        <input type="text" name="genus" class="form-control" value="<?= sanitize($org['genus'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Species</label>
                        <input type="text" name="species" class="form-control" value="<?= sanitize($org['species'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Cell Type</label>
                        <select name="cell_type" class="form-select">
                            <option value="">— Select —</option>
                            <option value="eukaryotic" <?= ($org['cell_type'] ?? '') === 'eukaryotic' ? 'selected' : '' ?>>Eukaryotic</option>
                            <option value="prokaryotic" <?= ($org['cell_type'] ?? '') === 'prokaryotic' ? 'selected' : '' ?>>Prokaryotic</option>
                        </select>
                    </div>
                </div>

                <hr class="my-4">
                <h5 class="fw-semibold mb-3"><i class="bi bi-info-circle text-info"></i> Additional Information</h5>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Habitat</label>
                    <input type="text" name="habitat" class="form-control" value="<?= sanitize($org['habitat'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Description</label>
                    <textarea name="description" class="form-control" rows="3"><?= sanitize($org['description'] ?? '') ?></textarea>
                </div>

                <hr class="my-4">
                <h5 class="fw-semibold mb-3"><i class="bi bi-image text-success"></i> Structure Image</h5>

                <?php if (!empty($org['structure_image'])): ?>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Current Image</label>
                    <div class="d-flex align-items-center gap-3">
                        <img src="<?= BASE_URL ?>assets/uploads/organisms/<?= sanitize($org['structure_image']) ?>"
                             alt="Organism structure" class="rounded border" style="max-height:120px;max-width:200px">
                        <div class="form-check">
                            <input type="checkbox" name="remove_image" value="1" class="form-check-input" id="removeImg">
                            <label class="form-check-label text-danger" for="removeImg">Remove current image</label>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="mb-4">
                    <label class="form-label fw-semibold"><?= !empty($org['structure_image']) ? 'Replace Image' : 'Upload Image' ?></label>
                    <input type="file" name="structure_image" class="form-control" accept="image/*">
                    <div class="form-text">Accepted: JPG, PNG, GIF, WEBP. Max 5MB.</div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-warning px-4">
                        <i class="bi bi-save"></i> Update Organism
                    </button>
                    <a href="<?= BASE_URL ?>views/admin/organisms/view.php?id=<?= $id ?>" class="btn btn-outline-info">View</a>
                    <a href="<?= BASE_URL ?>views/admin/organisms/index.php" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
</main>
<?php include __DIR__ . '/../../layouts/footer.php'; ?>
</div>
