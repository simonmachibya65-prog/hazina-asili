<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../models/Insight.php';
requireResearcher();

$model  = new Insight();
$uid    = $_SESSION['user_id'];
$status = in_array($_GET['status'] ?? '', [STATUS_PENDING, STATUS_APPROVED, STATUS_REJECTED]) ? $_GET['status'] : '';
$page   = max(1, (int)($_GET['page'] ?? 1));
$total  = $model->countAll($status, $uid);
$pagination = paginate($total, $page);
$insights   = $model->getAll($pagination['offset'], $pagination['per_page'], $status, $uid);

$pageTitle = 'My Insights';
include __DIR__ . '/../../layouts/header.php';
?>
<div class="d-flex flex-column min-vh-100">
<?php include __DIR__ . '/../../layouts/navbar_researcher.php'; ?>
<main class="flex-grow-1 py-4">
<div class="container-fluid px-4">

    <?= renderFlash() ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 fw-bold mb-0"><i class="bi bi-chat-square-text text-primary"></i> My Insights</h1>
        <a href="<?= BASE_URL ?>views/researcher/insights/create.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Submit New Insight
        </a>
    </div>

    <!-- Filter tabs -->
    <div class="mb-3">
        <?php
        $filters = ['' => 'All', STATUS_PENDING => 'Pending', STATUS_APPROVED => 'Approved', STATUS_REJECTED => 'Rejected'];
        $colors  = ['' => 'secondary', STATUS_PENDING => 'warning', STATUS_APPROVED => 'success', STATUS_REJECTED => 'danger'];
        foreach ($filters as $val => $label):
            $cnt = $model->countAll($val, $uid);
        ?>
        <a href="?status=<?= $val ?>"
           class="btn btn-sm me-1 btn-<?= $status === $val ? $colors[$val] : 'outline-'.$colors[$val] ?>">
            <?= $label ?> <span class="badge bg-light text-dark"><?= $cnt ?></span>
        </a>
        <?php endforeach; ?>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr><th>#</th><th>Compound</th><th>Insight (preview)</th><th>Status</th><th>Submitted</th></tr>
                    </thead>
                    <tbody>
                    <?php if (empty($insights)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-5">
                            No insights found.
                            <a href="<?= BASE_URL ?>views/researcher/insights/create.php">Submit your first insight.</a>
                        </td></tr>
                    <?php else: ?>
                        <?php foreach ($insights as $i => $ins): ?>
                        <tr>
                            <td class="text-muted small"><?= $pagination['offset'] + $i + 1 ?></td>
                            <td class="fw-semibold"><?= sanitize($ins['compound_name']) ?></td>
                            <td class="text-muted small">
                                <?= sanitize(mb_strimwidth($ins['insight_text'], 0, 100, '…')) ?>
                            </td>
                            <td><?= statusBadge($ins['status']) ?></td>
                            <td class="text-muted small"><?= formatDate($ins['created_at']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../../layouts/pagination.php'; ?>

</div>
</main>
<?php include __DIR__ . '/../../layouts/footer.php'; ?>
</div>
