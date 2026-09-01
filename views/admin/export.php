<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Compound.php';
require_once __DIR__ . '/../../models/Organism.php';
require_once __DIR__ . '/../../models/Reference.php';
require_once __DIR__ . '/../../models/Insight.php';
require_once __DIR__ . '/../../models/Recommendation.php';
requireAdmin();

$type   = sanitize($_GET['type'] ?? '');
$format = sanitize($_GET['format'] ?? '');

// ─── Handle CSV export ────────────────────────────────────────────────────────
if ($type && $format === 'csv') {
    $data    = [];
    $headers = [];

    switch ($type) {
        case 'compounds':
            $model   = new Compound();
            $rows    = $model->getAll(0, 9999);
            $headers = ['ID','Name','Formula','Molecular Weight','Organism','Description'];
            foreach ($rows as $r) {
                $data[] = [$r['id'], $r['name'], $r['formula'], $r['molecular_weight'], $r['organism_name'] ?? '', $r['description']];
            }
            break;
        case 'organisms':
            $model   = new Organism();
            $rows    = $model->getAll(0, 9999);
            $headers = ['ID','Scientific Name','Kingdom','Phylum','Class','Order','Family','Genus','Species','Cell Type','Habitat','Description'];
            foreach ($rows as $r) {
                $data[] = [
                    $r['id'], $r['scientific_name'], $r['kingdom'], $r['phylum'], $r['class'],
                    $r['order_name'] ?? '', $r['family'] ?? '', $r['genus'] ?? '', $r['species'] ?? '',
                    $r['cell_type'] ?? '', $r['habitat'] ?? '', $r['description'] ?? ''
                ];
            }
            break;
        case 'references':
            $model   = new Reference();
            $rows    = $model->getAll(0, 9999);
            $headers = ['ID','Title','Author','Year','Citation'];
            foreach ($rows as $r) {
                $data[] = [$r['id'], $r['title'], $r['author'], $r['year'], $r['citation']];
            }
            break;
        case 'insights':
            $model   = new Insight();
            $rows    = $model->getAll(0, 9999);
            $headers = ['ID','Researcher','Compound','Insight','Status','Date'];
            foreach ($rows as $r) {
                $data[] = [$r['id'], $r['researcher_name'], $r['compound_name'], $r['insight_text'], $r['status'], $r['created_at']];
            }
            break;
        case 'recommendations':
            $model   = new Recommendation();
            $rows    = $model->getAll(0, 9999);
            $headers = ['ID','Researcher','Compound','Field','Suggested Value','Status','Date'];
            foreach ($rows as $r) {
                $data[] = [$r['id'], $r['researcher_name'], $r['compound_name'], $r['field_to_change'], $r['suggested_value'], $r['status'], $r['created_at']];
            }
            break;
    }

    if (!empty($headers)) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $type . '_export_' . date('Ymd_His') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, $headers);
        foreach ($data as $row) fputcsv($out, $row);
        fclose($out);
        exit;
    }
}

$pageTitle = 'Export Data';
include __DIR__ . '/../layouts/header.php';
?>
<div class="d-flex flex-column min-vh-100">
<?php include __DIR__ . '/../layouts/navbar_admin.php'; ?>
<main class="flex-grow-1 py-4">
<div class="container px-4" style="max-width:700px">

    <?= renderFlash() ?>

    <h1 class="h3 fw-bold mb-4"><i class="bi bi-download text-secondary"></i> Export Data</h1>

    <div class="row g-4">
        <?php
        $exports = [
            ['compounds',      'Compounds',      'capsule',       'success'],
            ['organisms',      'Organisms',      'tree',          'warning'],
            ['references',     'References',     'journal-text',  'info'],
            ['insights',       'Insights',       'chat-square-text','primary'],
            ['recommendations','Recommendations','lightbulb',     'secondary'],
        ];
        foreach ($exports as [$key, $label, $icon, $color]):
        ?>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100 action-card">
                <div class="card-body d-flex align-items-center gap-3 p-4">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width:52px;height:52px;background:#f8f9fa">
                        <i class="bi bi-<?= $icon ?> text-<?= $color ?> fs-4"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="mb-0 fw-semibold"><?= $label ?></h5>
                        <small class="text-muted">Export all <?= strtolower($label) ?> as CSV</small>
                    </div>
                    <a href="?type=<?= $key ?>&format=csv"
                       class="btn btn-<?= $color ?> btn-sm px-3"
                       data-bs-toggle="tooltip" title="Download CSV">
                        <i class="bi bi-download me-1"></i> CSV
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

</div>
</main>
<?php include __DIR__ . '/../layouts/footer.php'; ?>
</div>
