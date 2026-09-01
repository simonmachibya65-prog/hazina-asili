<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Compound.php';
require_once __DIR__ . '/../../models/Insight.php';
require_once __DIR__ . '/../../models/Recommendation.php';
requireResearcher();

$uid = $_SESSION['user_id'];

$totalCompounds = (new Compound())->countAll();
$myInsights     = (new Insight())->countAll('', $uid);
$myRecs         = (new Recommendation())->countAll('', $uid);
$pendingIns     = (new Insight())->countAll(STATUS_PENDING, $uid);
$approvedIns    = (new Insight())->countAll(STATUS_APPROVED, $uid);
$pendingRecs    = (new Recommendation())->countAll(STATUS_PENDING, $uid);
$approvedRecs   = (new Recommendation())->countAll(STATUS_APPROVED, $uid);

$recentInsights = (new Insight())->getAll(0, 5, '', $uid);
$recentRecs     = (new Recommendation())->getAll(0, 5, '', $uid);

$pageTitle = 'Researcher Dashboard';
include __DIR__ . '/../layouts/header.php';
?>
<div class="d-flex flex-column min-vh-100">
<?php include __DIR__ . '/../layouts/navbar_researcher.php'; ?>
<main class="flex-grow-1 py-4">
<div class="container-fluid px-4">

    <?= renderFlash() ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold mb-0">Welcome, <?= sanitize(currentUser()['name']) ?></h1>
            <p class="text-muted mb-0">Researcher Dashboard</p>
        </div>
        <span class="text-muted small"><?= date('l, F j, Y') ?></span>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <?php
        $statCards = [
            [$totalCompounds, 'capsule',           'success', 'Total Compounds'],
            [$myInsights,     'chat-square-text',  'primary', 'My Insights'],
            [$myRecs,         'lightbulb',         'warning', 'My Recommendations'],
            [$approvedIns + $approvedRecs, 'check-circle', 'success', 'Approved Total'],
        ];
        foreach ($statCards as [$val, $icon, $color, $label]): ?>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center stat-card">
                <div class="card-body py-3">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-2"
                         style="width:46px;height:46px;background:var(--bs-<?= $color ?>-bg-subtle,#f8f9fa)">
                        <i class="bi bi-<?= $icon ?> text-<?= $color ?> fs-4"></i>
                    </div>
                    <h3 class="fw-bold mb-0"><?= $val ?></h3>
                    <small class="text-muted"><?= $label ?></small>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Notification banners -->
    <?php if ($pendingIns > 0 || $pendingRecs > 0): ?>
    <div class="alert alert-warning d-flex align-items-center gap-2 mb-4">
        <i class="bi bi-hourglass-split fs-5"></i>
        <div>
            You have
            <?php if ($pendingIns > 0): ?><strong><?= $pendingIns ?> insight(s)</strong><?php endif; ?>
            <?php if ($pendingIns > 0 && $pendingRecs > 0): ?> and <?php endif; ?>
            <?php if ($pendingRecs > 0): ?><strong><?= $pendingRecs ?> recommendation(s)</strong><?php endif; ?>
            pending admin review.
        </div>
    </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Recent Insights -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-chat-square-text text-primary"></i> My Recent Insights</h5>
                    <a href="<?= BASE_URL ?>views/researcher/insights/create.php" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus"></i> Submit
                    </a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($recentInsights)): ?>
                        <p class="text-muted text-center py-4">No insights submitted yet.</p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light"><tr><th>Compound</th><th>Status</th><th>Date</th></tr></thead>
                            <tbody>
                            <?php foreach ($recentInsights as $ins): ?>
                            <tr>
                                <td><?= sanitize($ins['compound_name']) ?></td>
                                <td><?= statusBadge($ins['status']) ?></td>
                                <td class="text-muted small"><?= formatDate($ins['created_at']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer bg-white text-end">
                    <a href="<?= BASE_URL ?>views/researcher/insights/index.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
            </div>
        </div>

        <!-- Recent Recommendations -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-lightbulb text-warning"></i> My Recent Recommendations</h5>
                    <a href="<?= BASE_URL ?>views/researcher/recommendations/create.php" class="btn btn-sm btn-warning">
                        <i class="bi bi-plus"></i> Submit
                    </a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($recentRecs)): ?>
                        <p class="text-muted text-center py-4">No recommendations submitted yet.</p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light"><tr><th>Compound</th><th>Field</th><th>Status</th></tr></thead>
                            <tbody>
                            <?php foreach ($recentRecs as $rec): ?>
                            <tr>
                                <td><?= sanitize($rec['compound_name']) ?></td>
                                <td><span class="badge bg-secondary"><?= sanitize($rec['field_to_change']) ?></span></td>
                                <td><?= statusBadge($rec['status']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer bg-white text-end">
                    <a href="<?= BASE_URL ?>views/researcher/recommendations/index.php" class="btn btn-sm btn-outline-warning">View All</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-white"><h5 class="mb-0">Quick Actions</h5></div>
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2">
                <a href="<?= BASE_URL ?>views/researcher/compounds/index.php" class="btn btn-success">
                    <i class="bi bi-search"></i> Browse Compounds
                </a>
                <a href="<?= BASE_URL ?>views/researcher/organisms/index.php" class="btn btn-warning">
                    <i class="bi bi-tree"></i> Browse Organisms
                </a>
                <a href="<?= BASE_URL ?>views/researcher/insights/create.php" class="btn btn-outline-primary">
                    <i class="bi bi-chat-square-text"></i> Submit Insight
                </a>
                <a href="<?= BASE_URL ?>views/researcher/recommendations/create.php" class="btn btn-outline-warning">
                    <i class="bi bi-lightbulb"></i> Submit Recommendation
                </a>
            </div>
        </div>
    </div>

    <!-- Contribution Chart -->
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-white fw-semibold">
            <i class="bi bi-pie-chart text-success"></i> My Submission Status
        </div>
        <div class="card-body d-flex justify-content-center">
            <canvas id="submissionChart" height="180" style="max-width:300px"></canvas>
        </div>
    </div>

</div>
</main>
<?php include __DIR__ . '/../layouts/footer.php'; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('submissionChart'), {
    type: 'doughnut',
    data: {
        labels: ['Approved', 'Pending', 'Rejected'],
        datasets: [{
            data: [
                <?= $approvedIns + $approvedRecs ?>,
                <?= $pendingIns  + $pendingRecs  ?>,
                <?= (new \Insight())->countAll(STATUS_REJECTED, $uid) + (new \Recommendation())->countAll(STATUS_REJECTED, $uid) ?>
            ],
            backgroundColor: ['#198754','#ffc107','#dc3545']
        }]
    },
    options: {
        plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } },
        cutout: '55%'
    }
});
</script>
