<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../models/Compound.php';
requireLogin();

$model = new Compound();
$ids = array_filter(array_map('intval', $_GET['ids'] ?? []));
$compounds = [];
foreach ($ids as $id) {
    $c = $model->findById($id);
    if ($c) $compounds[] = $c;
}

// Get all compounds for the selector
$allCompounds = $model->getAll(0, 999);

$pageTitle = 'Compare Compounds';
$navFile = isAdmin() && !empty($_SESSION['admin_secret_access']) ? __DIR__ . '/../../layouts/navbar_admin.php' : __DIR__ . '/../../layouts/navbar_researcher.php';
include __DIR__ . '/../../layouts/header.php';
?>
<div class="d-flex flex-column min-vh-100">
<?php include $navFile; ?>
<main class="flex-grow-1 py-4">
<div class="container-fluid px-4">

    <?= renderFlash() ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 fw-bold mb-0"><i class="bi bi-arrows-angle-expand text-primary"></i> Compare Compounds</h1>
    </div>

    <!-- Compound Selector -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Compound 1</label>
                    <select name="ids[]" class="form-select">
                        <option value="">— Select —</option>
                        <?php foreach ($allCompounds as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= in_array($c['id'], $ids) && ($ids[0] ?? 0) == $c['id'] ? 'selected' : '' ?>>
                            <?= sanitize($c['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Compound 2</label>
                    <select name="ids[]" class="form-select">
                        <option value="">— Select —</option>
                        <?php foreach ($allCompounds as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= isset($ids[1]) && $ids[1] == $c['id'] ? 'selected' : '' ?>>
                            <?= sanitize($c['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Compound 3 <small class="text-muted">(optional)</small></label>
                    <select name="ids[]" class="form-select">
                        <option value="">— Select —</option>
                        <?php foreach ($allCompounds as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= isset($ids[2]) && $ids[2] == $c['id'] ? 'selected' : '' ?>>
                            <?= sanitize($c['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-arrows-angle-expand"></i> Compare
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php if (count($compounds) >= 2): ?>
    <!-- Comparison Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0 text-center">
                    <thead class="table-dark">
                        <tr>
                            <th class="text-start" style="width:20%">Property</th>
                            <?php foreach ($compounds as $c): ?>
                            <th><?= sanitize($c['name']) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Structure Image -->
                        <tr>
                            <td class="text-start fw-semibold">Structure</td>
                            <?php foreach ($compounds as $c): ?>
                            <td>
                                <?php if (!empty($c['structure_image'])): ?>
                                    <img src="<?= BASE_URL ?>assets/uploads/compounds/<?= sanitize($c['structure_image']) ?>"
                                         alt="" class="rounded" style="max-height:120px;max-width:150px">
                                <?php else: ?>
                                    <span class="text-muted"><i class="bi bi-bezier2 fs-3"></i></span>
                                <?php endif; ?>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <!-- Formula -->
                        <tr>
                            <td class="text-start fw-semibold">Molecular Formula</td>
                            <?php foreach ($compounds as $c): ?>
                            <td><code class="fs-6"><?= sanitize($c['formula']) ?></code></td>
                            <?php endforeach; ?>
                        </tr>
                        <!-- Molecular Weight -->
                        <tr>
                            <td class="text-start fw-semibold">Molecular Weight</td>
                            <?php foreach ($compounds as $c): ?>
                            <td>
                                <strong><?= number_format($c['molecular_weight'], 4) ?></strong> g/mol
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <!-- Source Organism -->
                        <tr>
                            <td class="text-start fw-semibold">Source Organism</td>
                            <?php foreach ($compounds as $c): ?>
                            <td class="fst-italic"><?= sanitize($c['organism_name'] ?? '—') ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <!-- Kingdom -->
                        <tr>
                            <td class="text-start fw-semibold">Kingdom</td>
                            <?php foreach ($compounds as $c): ?>
                            <td><?= sanitize($c['kingdom'] ?? '—') ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <!-- Family -->
                        <tr>
                            <td class="text-start fw-semibold">Family</td>
                            <?php foreach ($compounds as $c): ?>
                            <td><?= sanitize($c['organism_family'] ?? '—') ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <!-- Description -->
                        <tr>
                            <td class="text-start fw-semibold">Description</td>
                            <?php foreach ($compounds as $c): ?>
                            <td class="small text-start"><?= sanitize(mb_strimwidth($c['description'] ?? '', 0, 150, '…')) ?></td>
                            <?php endforeach; ?>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- MW Comparison Chart -->
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-white fw-semibold">
            <i class="bi bi-bar-chart text-primary"></i> Molecular Weight Comparison
        </div>
        <div class="card-body">
            <canvas id="compareChart" height="100"></canvas>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
    new Chart(document.getElementById('compareChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($compounds, 'name')) ?>,
            datasets: [{
                label: 'Molecular Weight (g/mol)',
                data: <?= json_encode(array_column($compounds, 'molecular_weight')) ?>,
                backgroundColor: ['#0d6efd88', '#19875488', '#ffc10788'],
                borderColor: ['#0d6efd', '#198754', '#ffc107'],
                borderWidth: 2
            }]
        },
        options: {
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, title: { display: true, text: 'g/mol' } } }
        }
    });
    </script>

    <?php elseif (!empty($ids)): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle"></i> Please select at least 2 compounds to compare.
    </div>
    <?php else: ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5 text-muted">
            <i class="bi bi-arrows-angle-expand fs-1 d-block mb-3"></i>
            <h5>Select 2 or 3 compounds above to compare their properties side-by-side.</h5>
        </div>
    </div>
    <?php endif; ?>

</div>
</main>
<?php include __DIR__ . '/../../layouts/footer.php'; ?>
</div>
