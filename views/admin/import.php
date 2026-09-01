<?php
require_once __DIR__ . '/../../config/config.php';
requireAdmin();

$step = $_GET['step'] ?? 'upload';
$preview = $_SESSION['import_result'] ?? null;

$pageTitle = 'Import Data';
include __DIR__ . '/../layouts/header.php';
?>
<div class="d-flex flex-column min-vh-100">
<?php include __DIR__ . '/../layouts/navbar_admin.php'; ?>
<main class="flex-grow-1 py-4" id="main-content">
<div class="container-fluid px-4">

    <?= renderFlash() ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold mb-0"><i class="bi bi-upload text-primary"></i> Import Data</h1>
            <p class="text-muted mb-0">Bulk import from CSV files with validation preview</p>
        </div>
        <a href="<?= BASE_URL ?>views/admin/import_template.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-download"></i> Download Templates
        </a>
    </div>

    <?php if ($step === 'upload' || !$preview): ?>
    <!-- ── Upload Step ──────────────────────────────────────── -->
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold">
                    <i class="bi bi-cloud-upload"></i> Step 1: Upload CSV File
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= BASE_URL ?>controllers/process.php" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="import_preview">
                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

                        <div class="mb-3">
                            <label class="form-label">Data Type</label>
                            <select name="import_type" class="form-select" required>
                                <option value="compounds">Compounds</option>
                                <option value="organisms">Organisms</option>
                                <option value="references">References</option>
                            </select>
                            <div class="form-text">Select what type of data you're importing.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">CSV File</label>
                            <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                            <div class="form-text">
                                Max 10MB. First row must be headers.<br>
                                <strong>Compounds:</strong> name, formula, molecular_weight, description (optional)<br>
                                <strong>Organisms:</strong> scientific_name, kingdom, phylum, class, order_name, family, genus, species, cell_type, habitat, description<br>
                                <strong>References:</strong> title, author, year, citation
                            </div>
                        </div>

                        <div class="alert alert-info small">
                            <i class="bi bi-info-circle"></i>
                            Your data will be <strong>validated first</strong> — nothing is imported until you review and confirm.
                            Maximum 500 rows per import.
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-eye"></i> Upload &amp; Preview
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php elseif ($step === 'preview' && $preview): ?>
    <!-- ── Preview Step ─────────────────────────────────────── -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-search"></i> Step 2: Review Import Preview</h5>
            <div class="d-flex gap-2">
                <span class="badge bg-success"><?= $preview['valid_rows'] ?> valid</span>
                <?php if ($preview['error_count']): ?><span class="badge bg-danger"><?= $preview['error_count'] ?> errors</span><?php endif; ?>
                <?php if ($preview['warning_count']): ?><span class="badge bg-warning text-dark"><?= $preview['warning_count'] ?> warnings</span><?php endif; ?>
                <span class="badge bg-secondary"><?= $preview['total_rows'] ?> total</span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height:500px;overflow-y:auto">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th style="width:50px">#</th>
                            <th style="width:80px">Status</th>
                            <?php foreach ($preview['headers'] as $h): ?>
                            <th><?= sanitize(ucfirst(str_replace('_', ' ', $h))) ?></th>
                            <?php endforeach; ?>
                            <th>Issues</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($preview['rows'] as $row): ?>
                        <tr class="<?= $row['valid'] ? '' : 'table-danger' ?>">
                            <td class="text-muted"><?= $row['row_number'] ?></td>
                            <td>
                                <?php if ($row['valid']): ?>
                                    <span class="badge bg-success"><i class="bi bi-check"></i></span>
                                <?php else: ?>
                                    <span class="badge bg-danger"><i class="bi bi-x"></i></span>
                                <?php endif; ?>
                            </td>
                            <?php foreach ($preview['headers'] as $h): ?>
                            <td class="small"><?= sanitize(mb_strimwidth($row['data'][$h] ?? '', 0, 40, '…')) ?></td>
                            <?php endforeach; ?>
                            <td class="small">
                                <?php foreach ($row['errors'] as $e): ?>
                                    <span class="text-danger"><i class="bi bi-x-circle"></i> <?= sanitize($e) ?></span><br>
                                <?php endforeach; ?>
                                <?php foreach ($row['warnings'] as $w): ?>
                                    <span class="text-warning"><i class="bi bi-exclamation-triangle"></i> <?= sanitize($w) ?></span><br>
                                <?php endforeach; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white d-flex justify-content-between align-items-center">
            <div class="text-muted small">
                <?= $preview['valid_rows'] ?> row(s) will be imported. <?= $preview['error_count'] ?> row(s) will be skipped.
            </div>
            <div class="d-flex gap-2">
                <form method="POST" action="<?= BASE_URL ?>controllers/process.php" class="d-inline">
                    <input type="hidden" name="action" value="import_cancel">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <button type="submit" class="btn btn-outline-secondary">
                        <i class="bi bi-x"></i> Cancel
                    </button>
                </form>
                <?php if ($preview['valid_rows'] > 0): ?>
                <form method="POST" action="<?= BASE_URL ?>controllers/process.php" class="d-inline" data-confirm="Are you sure you want to import <?= $preview['valid_rows'] ?> row(s)?">
                    <input type="hidden" name="action" value="import_commit">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle"></i> Confirm Import (<?= $preview['valid_rows'] ?> rows)
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>
</main>
<?php include __DIR__ . '/../layouts/footer.php'; ?>
</div>
