<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../models/Compound.php';
requireResearcher();

$compounds  = (new Compound())->getAll(0, 9999);
$preselect  = (int)($_GET['compound_id'] ?? 0);
$pageTitle  = 'Submit Insight';
include __DIR__ . '/../../layouts/header.php';
?>
<div class="d-flex flex-column min-vh-100">
<?php include __DIR__ . '/../../layouts/navbar_researcher.php'; ?>
<main class="flex-grow-1 py-4">
<div class="container px-4" style="max-width:700px">

    <?= renderFlash() ?>

    <div class="d-flex align-items-center mb-4">
        <a href="<?= BASE_URL ?>views/researcher/insights/index.php" class="btn btn-outline-secondary me-3">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h1 class="h3 fw-bold mb-0">Submit Insight</h1>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <p class="text-muted mb-4">
                Share your research findings or observations about a compound.
                Your insight will be reviewed by an admin before publication.
            </p>

            <form method="POST" action="<?= BASE_URL ?>controllers/process.php" novalidate>
                <input type="hidden" name="action" value="submit_insight">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

                <div class="mb-3">
                    <label class="form-label fw-semibold">Compound <span class="text-danger">*</span></label>
                    <select name="compound_id" class="form-select" required>
                        <option value="">— Select a compound —</option>
                        <?php foreach ($compounds as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $preselect == $c['id'] ? 'selected' : '' ?>>
                            <?= sanitize($c['name']) ?> (<?= sanitize($c['formula']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">
                        Insight <span class="text-danger">*</span>
                        <small class="text-muted fw-normal">(min 10 characters)</small>
                    </label>
                    <textarea name="insight_text" class="form-control" rows="6" required minlength="10"
                              placeholder="Describe your research finding, observation, or analysis about this compound..."></textarea>
                    <div class="form-text">
                        <span id="charCount">0</span> characters
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-send"></i> Submit Insight
                    </button>
                    <a href="<?= BASE_URL ?>views/researcher/insights/index.php" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
</main>
<?php include __DIR__ . '/../../layouts/footer.php'; ?>
</div>
<script>
document.querySelector('textarea[name="insight_text"]').addEventListener('input', function() {
    document.getElementById('charCount').textContent = this.value.length;
});
</script>
