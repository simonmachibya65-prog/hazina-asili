<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../models/Compound.php';
requireResearcher();

$compounds = (new Compound())->getAll(0, 9999);
$preselect = (int)($_GET['compound_id'] ?? 0);
$pageTitle = 'Submit Recommendation';
include __DIR__ . '/../../layouts/header.php';
?>
<div class="d-flex flex-column min-vh-100">
<?php include __DIR__ . '/../../layouts/navbar_researcher.php'; ?>
<main class="flex-grow-1 py-4">
<div class="container px-4" style="max-width:700px">

    <?= renderFlash() ?>

    <div class="d-flex align-items-center mb-4">
        <a href="<?= BASE_URL ?>views/researcher/recommendations/index.php" class="btn btn-outline-secondary me-3">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h1 class="h3 fw-bold mb-0">Submit Recommendation</h1>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <p class="text-muted mb-4">
                Suggest a correction or update to an existing compound's data.
                Your recommendation will be reviewed by an admin.
            </p>

            <form method="POST" action="<?= BASE_URL ?>controllers/process.php" novalidate>
                <input type="hidden" name="action" value="submit_recommendation">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

                <div class="mb-3">
                    <label class="form-label fw-semibold">Compound <span class="text-danger">*</span></label>
                    <select name="compound_id" class="form-select" required id="compoundSelect">
                        <option value="">— Select a compound —</option>
                        <?php foreach ($compounds as $c): ?>
                        <option value="<?= $c['id'] ?>"
                                data-name="<?= sanitize($c['name']) ?>"
                                data-formula="<?= sanitize($c['formula']) ?>"
                                data-mw="<?= $c['molecular_weight'] ?>"
                                data-desc="<?= sanitize($c['description']) ?>"
                                <?= $preselect == $c['id'] ? 'selected' : '' ?>>
                            <?= sanitize($c['name']) ?> (<?= sanitize($c['formula']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Field to Change <span class="text-danger">*</span></label>
                    <select name="field_to_change" class="form-select" required id="fieldSelect">
                        <option value="">— Select field —</option>
                        <option value="name">Name</option>
                        <option value="formula">Molecular Formula</option>
                        <option value="molecular_weight">Molecular Weight</option>
                        <option value="description">Description</option>
                    </select>
                </div>

                <!-- Current value display -->
                <div class="mb-3" id="currentValueBox" style="display:none">
                    <label class="form-label text-muted">Current Value</label>
                    <div class="p-2 bg-light rounded border text-muted small" id="currentValueText"></div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Suggested Value <span class="text-danger">*</span></label>
                    <textarea name="suggested_value" class="form-control" rows="4" required
                              placeholder="Enter your suggested value..."></textarea>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-warning px-4">
                        <i class="bi bi-send"></i> Submit Recommendation
                    </button>
                    <a href="<?= BASE_URL ?>views/researcher/recommendations/index.php" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
</main>
<?php include __DIR__ . '/../../layouts/footer.php'; ?>
</div>
<script>
// Show current value when compound + field are selected
const compoundSelect = document.getElementById('compoundSelect');
const fieldSelect    = document.getElementById('fieldSelect');
const currentBox     = document.getElementById('currentValueBox');
const currentText    = document.getElementById('currentValueText');

function updateCurrentValue() {
    const opt   = compoundSelect.options[compoundSelect.selectedIndex];
    const field = fieldSelect.value;
    if (!opt.value || !field) { currentBox.style.display = 'none'; return; }
    const map = { name: opt.dataset.name, formula: opt.dataset.formula, molecular_weight: opt.dataset.mw, description: opt.dataset.desc };
    currentText.textContent = map[field] || '—';
    currentBox.style.display = 'block';
}

compoundSelect.addEventListener('change', updateCurrentValue);
fieldSelect.addEventListener('change', updateCurrentValue);
updateCurrentValue();
</script>
