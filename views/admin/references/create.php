<?php
require_once __DIR__ . '/../../../config/config.php';
requireAdmin();
$pageTitle = 'Add Reference';
include __DIR__ . '/../../layouts/header.php';
?>
<div class="d-flex flex-column min-vh-100">
<?php include __DIR__ . '/../../layouts/navbar_admin.php'; ?>
<main class="flex-grow-1 py-4">
<div class="container px-4" style="max-width:650px">

    <?= renderFlash() ?>

    <div class="d-flex align-items-center mb-4">
        <a href="<?= BASE_URL ?>views/admin/references/index.php" class="btn btn-outline-secondary me-3">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h1 class="h3 fw-bold mb-0">Add New Reference</h1>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form method="POST" action="<?= BASE_URL ?>controllers/process.php" novalidate>
                <input type="hidden" name="action" value="create_reference">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

                <div class="mb-3">
                    <label class="form-label fw-semibold">Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" required
                           placeholder="Publication title">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Author(s) <span class="text-danger">*</span></label>
                    <input type="text" name="author" class="form-control" required
                           placeholder="e.g. Smith, J., Doe, A.">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Year <span class="text-danger">*</span></label>
                    <input type="number" name="year" class="form-control" required
                           min="1900" max="<?= date('Y') ?>" placeholder="<?= date('Y') ?>">
                </div>
                <div class="mb-4">
                    <label class="form-label fw-semibold">Full Citation <span class="text-danger">*</span></label>
                    <textarea name="citation" class="form-control" rows="3" required
                              placeholder="Full citation in APA/MLA/etc. format"></textarea>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-info text-white px-4">
                        <i class="bi bi-check-circle"></i> Save Reference
                    </button>
                    <a href="<?= BASE_URL ?>views/admin/references/index.php" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
</main>
<?php include __DIR__ . '/../../layouts/footer.php'; ?>
</div>
