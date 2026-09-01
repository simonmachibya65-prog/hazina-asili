<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Compound.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../models/Organism.php';
require_once __DIR__ . '/../../models/Reference.php';
require_once __DIR__ . '/../../models/Insight.php';
require_once __DIR__ . '/../../models/Recommendation.php';
require_once __DIR__ . '/../../models/ActivityLog.php';
require_once __DIR__ . '/../../models/ErrorLog.php';
requireAdmin();

$compoundModel = new Compound();
$userModel     = new User();
$orgModel      = new Organism();
$refModel      = new Reference();
$insightModel  = new Insight();
$recModel      = new Recommendation();
$logModel      = new ActivityLog();
$errModel      = new ErrorLog();

$stats = [
    'compounds'        => $compoundModel->countAll(),
    'users'            => $userModel->countAll(),
    'organisms'        => $orgModel->countAll(),
    'references'       => $refModel->countAll(),
    'pending_insights' => $insightModel->countPending(),
    'pending_recs'     => $recModel->countPending(),
];
$compoundStats  = $compoundModel->getStats();
$activityStats  = $logModel->getStats();
$errCounts      = $errModel->getLevelCounts();
$byKingdom      = $compoundModel->getByKingdom();
$orgByKingdom   = $orgModel->getByKingdom();
$monthlyTrend   = $compoundModel->getMonthlyTrend(6);
$dailyActivity  = $logModel->getDailyActivity(14);
$actionFreq     = $logModel->getActionFrequency(30);
$recentInsights = $insightModel->getAll(0, 5, STATUS_PENDING);
$recentRecs     = $recModel->getAll(0, 5, STATUS_PENDING);

$pageTitle = 'Admin Dashboard';
include __DIR__ . '/../layouts/header.php';
?>
<div class="d-flex flex-column min-vh-100">
<?php include __DIR__ . '/../layouts/navbar_admin.php'; ?>
<main class="flex-grow-1 py-4" id="main-content">
<div class="container-fluid px-4">

    <?= renderFlash() ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold mb-0">Admin Dashboard</h1>
            <p class="text-muted mb-0">Welcome back, <?= sanitize(currentUser()['name']) ?></p>
        </div>
        <span class="text-muted small"><?= date('l, F j, Y') ?></span>
    </div>

    <!-- ── Stats Cards ─────────────────────────────────────────── -->
    <div class="row g-3 mb-4">
        <?php
        $cards = [
            ['compounds',  $stats['compounds'],  'capsule',        'success', 'Compounds',       'views/admin/compounds/index.php'],
            ['users',      $stats['users'],      'people',         'primary', 'Users',            'views/admin/users/index.php'],
            ['organisms',  $stats['organisms'],  'tree',           'warning', 'Organisms',        'views/admin/organisms/index.php'],
            ['references', $stats['references'], 'journal-text',   'info',    'References',       'views/admin/references/index.php'],
            ['pending',    $stats['pending_insights']+$stats['pending_recs'], 'hourglass-split', 'danger', 'Pending Reviews', 'views/admin/insights/index.php'],
            ['activity',   $activityStats['today'] ?? 0, 'lightning-charge', 'secondary', 'Actions Today', 'views/admin/activity_log.php'],
        ];
        foreach ($cards as [$key, $val, $icon, $color, $label, $link]): ?>
        <div class="col-6 col-md-4 col-xl-2">
            <a href="<?= BASE_URL . $link ?>" class="text-decoration-none">
                <div class="card border-0 shadow-sm text-center h-100 stat-card">
                    <div class="card-body py-3">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-2"
                             style="width:48px;height:48px;background:var(--bs-<?= $color ?>-bg-subtle,#f8f9fa)">
                            <i class="bi bi-<?= $icon ?> text-<?= $color ?> fs-4"></i>
                        </div>
                        <h3 class="fw-bold mb-0"><?= number_format((int)$val) ?></h3>
                        <small class="text-muted"><?= $label ?></small>
                    </div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ── Error alert ─────────────────────────────────────────── -->
    <?php if ($errCounts['critical'] > 0): ?>
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-4">
        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
        <div>
            <strong><?= $errCounts['critical'] ?> critical error(s)</strong> logged.
            <a href="<?= BASE_URL ?>views/admin/error_log.php" class="alert-link">View error log →</a>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── Charts Row ──────────────────────────────────────────── -->
    <div class="row g-4 mb-4">
        <!-- Compounds by Kingdom (Doughnut) -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">
                    <i class="bi bi-pie-chart text-success"></i> Compounds by Kingdom
                </div>
                <div class="card-body d-flex align-items-center justify-content-center">
                    <canvas id="kingdomChart" height="220"></canvas>
                </div>
            </div>
        </div>
        <!-- Organisms by Kingdom (Doughnut) -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">
                    <i class="bi bi-tree text-warning"></i> Organisms by Kingdom
                </div>
                <div class="card-body d-flex align-items-center justify-content-center">
                    <canvas id="orgKingdomChart" height="220"></canvas>
                </div>
            </div>
        </div>
        <!-- Monthly Trend (Bar) -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">
                    <i class="bi bi-bar-chart text-primary"></i> Compounds Added (6 months)
                </div>
                <div class="card-body">
                    <canvas id="trendChart" height="220"></canvas>
                </div>
            </div>
        </div>
        <!-- Daily Activity (Line) -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">
                    <i class="bi bi-graph-up text-warning"></i> System Activity (14 days)
                </div>
                <div class="card-body">
                    <canvas id="activityChart" height="220"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Summary Stats ───────────────────────────────────────── -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-muted small">Avg Molecular Weight</div>
                    <div class="fw-bold fs-5"><?= number_format((float)($compoundStats['avg_mw'] ?? 0), 2) ?> g/mol</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-muted small">Lightest Compound</div>
                    <div class="fw-bold fs-5"><?= number_format((float)($compoundStats['min_mw'] ?? 0), 2) ?> g/mol</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-muted small">Heaviest Compound</div>
                    <div class="fw-bold fs-5"><?= number_format((float)($compoundStats['max_mw'] ?? 0), 2) ?> g/mol</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-muted small">Total Activity (7 days)</div>
                    <div class="fw-bold fs-5"><?= number_format((int)($activityStats['this_week'] ?? 0)) ?> actions</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Pending Reviews ─────────────────────────────────────── -->
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-chat-square-text text-warning"></i> Pending Insights</h5>
                    <a href="<?= BASE_URL ?>views/admin/insights/index.php" class="btn btn-sm btn-outline-warning">View All</a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($recentInsights)): ?>
                        <p class="text-muted text-center py-4"><i class="bi bi-check-circle text-success"></i> All clear!</p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light"><tr><th>Researcher</th><th>Compound</th><th>Date</th><th></th></tr></thead>
                            <tbody>
                            <?php foreach ($recentInsights as $ins): ?>
                            <tr>
                                <td><?= sanitize($ins['researcher_name']) ?></td>
                                <td><?= sanitize($ins['compound_name']) ?></td>
                                <td class="text-muted small"><?= formatDate($ins['created_at']) ?></td>
                                <td><a href="<?= BASE_URL ?>views/admin/insights/view.php?id=<?= $ins['id'] ?>" class="btn btn-xs btn-outline-primary btn-sm">Review</a></td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-lightbulb text-warning"></i> Pending Recommendations</h5>
                    <a href="<?= BASE_URL ?>views/admin/recommendations/index.php" class="btn btn-sm btn-outline-warning">View All</a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($recentRecs)): ?>
                        <p class="text-muted text-center py-4"><i class="bi bi-check-circle text-success"></i> All clear!</p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light"><tr><th>Researcher</th><th>Compound</th><th>Field</th><th></th></tr></thead>
                            <tbody>
                            <?php foreach ($recentRecs as $rec): ?>
                            <tr>
                                <td><?= sanitize($rec['researcher_name']) ?></td>
                                <td><?= sanitize($rec['compound_name']) ?></td>
                                <td><span class="badge bg-secondary"><?= sanitize($rec['field_to_change']) ?></span></td>
                                <td><a href="<?= BASE_URL ?>views/admin/recommendations/view.php?id=<?= $rec['id'] ?>" class="btn btn-sm btn-outline-primary">Review</a></td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Top Actions + Quick Actions ────────────────────────── -->
    <div class="row g-4">
        <div class="col-md-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold"><i class="bi bi-bar-chart-steps"></i> Top Actions (30 days)</div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php foreach (array_slice($actionFreq, 0, 6) as $af): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                            <span class="small"><?= sanitize(str_replace('_',' ', $af['action'])) ?></span>
                            <span class="badge bg-primary rounded-pill"><?= $af['cnt'] ?></span>
                        </li>
                        <?php endforeach; ?>
                        <?php if (empty($actionFreq)): ?>
                        <li class="list-group-item text-muted small text-center py-3">No activity yet</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-md-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold">Quick Actions</div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        <a href="<?= BASE_URL ?>views/admin/compounds/create.php" class="btn btn-success"><i class="bi bi-plus-circle"></i> Add Compound</a>
                        <a href="<?= BASE_URL ?>views/admin/organisms/create.php" class="btn btn-outline-warning"><i class="bi bi-plus-circle"></i> Add Organism</a>
                        <a href="<?= BASE_URL ?>views/admin/references/create.php" class="btn btn-outline-info"><i class="bi bi-plus-circle"></i> Add Reference</a>
                        <a href="<?= BASE_URL ?>views/admin/users/create.php" class="btn btn-outline-primary"><i class="bi bi-person-plus"></i> Add User</a>
                        <a href="<?= BASE_URL ?>views/admin/report.php" class="btn btn-outline-danger"><i class="bi bi-file-earmark-pdf"></i> Generate Report</a>
                        <a href="<?= BASE_URL ?>views/admin/export.php" class="btn btn-outline-secondary"><i class="bi bi-download"></i> Export CSV</a>
                        <a href="<?= BASE_URL ?>views/admin/import.php" class="btn btn-outline-primary"><i class="bi bi-upload"></i> Import CSV</a>
                        <a href="<?= BASE_URL ?>views/admin/backup.php" class="btn btn-outline-dark"><i class="bi bi-database"></i> Backup DB</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
</main>
<?php include __DIR__ . '/../layouts/footer.php'; ?>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// ── Chart theme helper ────────────────────────────────────────
function getChartDefaults() {
    var isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
    Chart.defaults.color = isDark ? '#adb5bd' : '#6c757d';
    Chart.defaults.borderColor = isDark ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.08)';
    return isDark;
}
getChartDefaults();

// ── Compounds by Kingdom (Doughnut) ──────────────────────────
const kingdomData = <?= json_encode(array_column($byKingdom, 'cnt')) ?>;
const kingdomLabels = <?= json_encode(array_column($byKingdom, 'kingdom')) ?>;
new Chart(document.getElementById('kingdomChart'), {
    type: 'doughnut',
    data: {
        labels: kingdomLabels,
        datasets: [{ data: kingdomData, backgroundColor: ['#198754','#0d6efd','#ffc107','#dc3545','#0dcaf0','#6c757d','#6f42c1'] }]
    },
    options: { plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } }, cutout: '60%' }
});

// ── Organisms by Kingdom (Doughnut) ──────────────────────────
const orgKingdomData = <?= json_encode(array_column($orgByKingdom, 'cnt')) ?>;
const orgKingdomLabels = <?= json_encode(array_column($orgByKingdom, 'kingdom')) ?>;
new Chart(document.getElementById('orgKingdomChart'), {
    type: 'doughnut',
    data: {
        labels: orgKingdomLabels,
        datasets: [{ data: orgKingdomData, backgroundColor: ['#ffc107','#198754','#0d6efd','#dc3545','#0dcaf0','#6c757d','#6f42c1'] }]
    },
    options: { plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } }, cutout: '60%' }
});

// ── Monthly Trend (Bar) ───────────────────────────────────────
const trendLabels = <?= json_encode(array_column($monthlyTrend, 'month')) ?>;
const trendData   = <?= json_encode(array_column($monthlyTrend, 'cnt')) ?>;
new Chart(document.getElementById('trendChart'), {
    type: 'bar',
    data: {
        labels: trendLabels,
        datasets: [{ label: 'Compounds', data: trendData, backgroundColor: '#0d6efd88', borderColor: '#0d6efd', borderWidth: 1, borderRadius: 4 }]
    },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
});

// ── Daily Activity (Line) ─────────────────────────────────────
const actLabels = <?= json_encode(array_column($dailyActivity, 'day')) ?>;
const actData   = <?= json_encode(array_column($dailyActivity, 'cnt')) ?>;
new Chart(document.getElementById('activityChart'), {
    type: 'line',
    data: {
        labels: actLabels,
        datasets: [{ label: 'Actions', data: actData, borderColor: '#ffc107', backgroundColor: '#ffc10722', fill: true, tension: 0.3, pointRadius: 3 }]
    },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
});

// Update charts on theme change
var observer = new MutationObserver(function() { getChartDefaults(); });
observer.observe(document.documentElement, { attributes: true, attributeFilter: ['data-bs-theme'] });
</script>
