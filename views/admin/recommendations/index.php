<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../models/Recommendation.php';
requireAdmin();

$model  = new Recommendation();
$status = in_array($_GET['status'] ?? '', [STATUS_PENDING, STATUS_APPROVED, STATUS_REJECTED]) ? $_GET['status'] : '';
$page   = max(1, (int)($_GET['page'] ?? 1));
$total  = $model->countAll($status);
$pagination = paginate($total, $page);
$recs       = $model->getAll($pagination['offset'], $pagination['per_page'], $status);

$pageTitle = 'Manage Recommendations';
include __DIR__ . '/../../layouts/header.php';
?>
<div class="d-flex flex-column min-vh-100">
<?php include __DIR__ . '/../../layouts/navbar_admin.php'; ?>
<main class="flex-grow-1 py-4">
<div class="container-fluid px-4">

    <?= renderFlash() ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 fw-bold mb-0"><i class="bi bi-lightbulb text-warning"></i> Researcher Recommendations</h1>
        <div class="d-flex gap-2">
            <?php
            $counts = [
                ''              => $model->countAll(),
                STATUS_PENDING  => $model->countAll(STATUS_PENDING),
                STATUS_APPROVED => $model->countAll(STATUS_APPROVED),
                STATUS_REJECTED => $model->countAll(STATUS_REJECTED),
            ];
            $filters = ['' => ['All','secondary'], STATUS_PENDING => ['Pending','warning'], STATUS_APPROVED => ['Approved','success'], STATUS_REJECTED => ['Rejected','danger']];
            foreach ($filters as $val => [$label, $color]): ?>
            <a href="?status=<?= $val ?>"
               class="btn btn-sm btn-<?= $status === $val ? $color : 'outline-'.$color ?>">
                <?= $label ?> <span class="badge bg-light text-dark"><?= $counts[$val] ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr><th>#</th><th>Researcher</th><th>Compound</th><th>Field</th><th>Suggested Value</th><th>Status</th><th>Date</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                    <?php if (empty($recs)): ?>
                        <tr><td colspan="8" class="text-center text-muted py-4">No recommendations found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recs as $i => $rec): ?>
                        <tr>
                            <td class="text-muted small"><?= $pagination['offset'] + $i + 1 ?></td>
                            <td><?= sanitize($rec['researcher_name']) ?></td>
                            <td class="fw-semibold"><?= sanitize($rec['compound_name']) ?></td>
                            <td><span class="badge bg-secondary"><?= sanitize($rec['field_to_change']) ?></span></td>
                            <td class="text-muted small" style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                                <?= sanitize($rec['suggested_value']) ?>
                            </td>
                            <td><?= statusBadge($rec['status']) ?></td>
                            <td class="text-muted small"><?= formatDate($rec['created_at']) ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= BASE_URL ?>views/admin/recommendations/view.php?id=<?= $rec['id'] ?>"
                                       class="btn btn-outline-info"><i class="bi bi-eye"></i></a>
                                    <?php if ($rec['status'] === STATUS_PENDING): ?>
                                    <form method="POST" action="<?= BASE_URL ?>controllers/process.php" class="d-inline">
                                        <input type="hidden" name="action" value="approve_recommendation">
                                        <input type="hidden" name="id" value="<?= $rec['id'] ?>">
                                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                        <button type="submit" class="btn btn-outline-success" title="Approve">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                    </form>
                                    <form method="POST" action="<?= BASE_URL ?>controllers/process.php" class="d-inline">
                                        <input type="hidden" name="action" value="reject_recommendation">
                                        <input type="hidden" name="id" value="<?= $rec['id'] ?>">
                                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                        <button type="submit" class="btn btn-outline-danger" title="Reject">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    <form method="POST" action="<?= BASE_URL ?>controllers/process.php" class="d-inline"
                                          onsubmit="return confirm('Delete this recommendation?')">
                                        <input type="hidden" name="action" value="delete_recommendation">
                                        <input type="hidden" name="id" value="<?= $rec['id'] ?>">
                                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                        <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </div>
                            </td>
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
