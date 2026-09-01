<?php
require_once __DIR__ . '/../../../config/config.php';
requireAdmin();
$pageTitle = 'Add Organism';
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
        <h1 class="h3 fw-bold mb-0">Add New Organism</h1>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form method="POST" action="<?= BASE_URL ?>controllers/process.php" enctype="multipart/form-data" novalidate>
                <input type="hidden" name="action" value="create_organism">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

                <!-- Scientific Name -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Scientific Name <span class="text-danger">*</span></label>
                    <input type="text" name="scientific_name" class="form-control fst-italic"
                           placeholder="e.g. Camellia sinensis" required>
                </div>

                <hr class="my-4">
                <h5 class="fw-semibold mb-3"><i class="bi bi-diagram-3 text-warning"></i> Taxonomic Classification</h5>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Kingdom <span class="text-danger">*</span></label>
                        <input type="text" name="kingdom" class="form-control" placeholder="e.g. Plantae" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Phylum <span class="text-danger">*</span></label>
                        <input type="text" name="phylum" class="form-control" placeholder="e.g. Tracheophyta" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Class <span class="text-danger">*</span></label>
                        <input type="text" name="class" class="form-control" placeholder="e.g. Magnoliopsida" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Order</label>
                        <input type="text" name="order_name" class="form-control" placeholder="e.g. Ericales">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Family</label>
                        <input type="text" name="family" class="form-control" placeholder="e.g. Theaceae">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Genus</label>
                        <input type="text" name="genus" class="form-control" placeholder="e.g. Camellia">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Species</label>
                        <input type="text" name="species" class="form-control" placeholder="e.g. sinensis">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Cell Type</label>
                        <select name="cell_type" class="form-select">
                            <option value="">— Select —</option>
                            <option value="eukaryotic">Eukaryotic</option>
                            <option value="prokaryotic">Prokaryotic</option>
                        </select>
                    </div>
                </div>

                <hr class="my-4">
                <h5 class="fw-semibold mb-3"><i class="bi bi-info-circle text-info"></i> Additional Information</h5>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Habitat</label>
                    <input type="text" name="habitat" class="form-control"
                           placeholder="e.g. Tropical and subtropical regions of Asia">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Description</label>
                    <textarea name="description" class="form-control" rows="3"
                              placeholder="General notes about this organism..."></textarea>
                </div>

                <hr class="my-4">
                <h5 class="fw-semibold mb-3"><i class="bi bi-image text-success"></i> Structure Image</h5>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Upload Image</label>
                    <input type="file" name="structure_image" class="form-control" accept="image/*">
                    <div class="form-text">Accepted: JPG, PNG, GIF, WEBP. Max 5MB.</div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-warning px-4">
                        <i class="bi bi-check-circle"></i> Save Organism
                    </button>
                    <a href="<?= BASE_URL ?>views/admin/organisms/index.php" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
</main>
<?php include __DIR__ . '/../../layouts/footer.php'; ?>
</div>
