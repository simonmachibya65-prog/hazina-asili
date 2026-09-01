<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Compound.php';
require_once __DIR__ . '/../../models/Organism.php';
require_once __DIR__ . '/../../models/Reference.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../helpers/report_functions.php';
requireAdmin();

$type = sanitize($_GET['type'] ?? '');
$id   = (int)($_GET['id'] ?? 0);

// ─── Handle report generation (printable HTML) ───────────────
if ($type === 'compound' && $id) {
    $model = new Compound();
    $compound = $model->findById($id);
    if (!$compound) { setFlash('error', 'Compound not found.'); redirect('views/admin/report.php'); }
    $references = $model->getReferences($id);
    generateCompoundReport($compound, $references);
    exit;
}

if ($type === 'organism' && $id) {
    $orgModel = new Organism();
    $org = $orgModel->findById($id);
    if (!$org) { setFlash('error', 'Organism not found.'); redirect('views/admin/report.php'); }
    $compounds = $orgModel->getCompounds($id);
    generateOrganismReport($org, $compounds);
    exit;
}

if ($type === 'summary') {
    $compoundModel = new Compound();
    $orgModel = new Organism();
    $refModel = new Reference();
    $userModel = new User();
    generateSummaryReport($compoundModel, $orgModel, $refModel, $userModel);
    exit;
}

// ─── Report selection page ───────────────────────────────────
$compoundModel = new Compound();
$orgModel = new Organism();
$compounds = $compoundModel->getAll(0, 999);
$organisms = $orgModel->getAll(0, 999);

$pageTitle = 'Generate Report';
include __DIR__ . '/../layouts/header.php';
?>
<div class="d-flex flex-column min-vh-100">
<?php include __DIR__ . '/../layouts/navbar_admin.php'; ?>
<main class="flex-grow-1 py-4">
<div class="container px-4" style="max-width:800px">

    <?= renderFlash() ?>

    <h1 class="h3 fw-bold mb-4"><i class="bi bi-file-earmark-pdf text-danger"></i> Generate Report</h1>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center p-4">
                    <i class="bi bi-clipboard-data fs-1 text-primary d-block mb-3"></i>
                    <h5 class="fw-bold">Database Summary</h5>
                    <p class="text-muted small">Full overview of all data and statistics.</p>
                    <a href="?type=summary" class="btn btn-primary" target="_blank">
                        <i class="bi bi-printer"></i> Generate
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="text-center mb-3">
                        <i class="bi bi-capsule fs-1 text-success d-block mb-2"></i>
                        <h5 class="fw-bold">Compound Report</h5>
                    </div>
                    <form method="GET" target="_blank">
                        <input type="hidden" name="type" value="compound">
                        <select name="id" class="form-select mb-3" required>
                            <option value="">— Select Compound —</option>
                            <?php foreach ($compounds as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= sanitize($c['name']) ?> (<?= sanitize($c['formula']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-success w-100"><i class="bi bi-printer"></i> Generate</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="text-center mb-3">
                        <i class="bi bi-tree fs-1 text-warning d-block mb-2"></i>
                        <h5 class="fw-bold">Organism Report</h5>
                    </div>
                    <form method="GET" target="_blank">
                        <input type="hidden" name="type" value="organism">
                        <select name="id" class="form-select mb-3" required>
                            <option value="">— Select Organism —</option>
                            <?php foreach ($organisms as $o): ?>
                            <option value="<?= $o['id'] ?>"><?= sanitize($o['scientific_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-warning w-100"><i class="bi bi-printer"></i> Generate</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>
</main>
<?php include __DIR__ . '/../layouts/footer.php'; ?>
</div>
