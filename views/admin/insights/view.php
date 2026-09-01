<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../models/Insight.php';
requireAdmin();

$id      = (int)($_GET['id'] ?? 0);
$model   = new Insight();
$insight = $model->findById($id);
if (!$insight) { setFlash('error', 'Insight not found.'); redirect('views/admin/insights/index.php'); }

$pageTitle = 'Review Insight';
include __DIR__ . '/../../layouts/header.php';
?>
<div class="d-flex flex-column min-vh-100">
<?php include __DIR__ . '/../../layouts/navbar_admin.php'; ?>
<main class="flex-grow-1 py-4">
<div class="container px-4" style="max-width:750px">

    <?= renderFlash() ?>

    <div class="d-flex align-items-center mb-4">
        <a href="<?= BASE_URL ?>views/admin/insights/index.php" class="btn btn-outline-secondary me-3">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h1 class="h3 fw-bold mb-0">Review Insight</h1>
        <span class="ms-3"><?= statusBadge($insight['status']) ?></span>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white">
            <div class="row">
                <div class="col-md-6">
                    <small class="text-muted">Researcher</small>
                    <div class="fw-semibold"><?= sanitize($insight['researcher_name']) ?></div>
                </div>
                <div class="col-md-4">
                    <small class="text-muted">Compound</small>
                    <div class="fw-semibold"><?= sanitize($insight['compound_name']) ?></div>
                </div>
                <div class="col-md-2">
                    <small class="text-muted">Submitted</small>
                    <div class="small"><?= formatDate($insight['created_at']) ?></div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <h6 class="text-muted mb-2">Insight Text</h6>
            <div class="p-3 bg-light rounded">
                <?= nl2br(sanitize($insight['insight_text'])) ?>
            </div>
        </div>
    </div>

    <?php if ($insight['status'] === STATUS_PENDING): ?>
    <div class="d-flex gap-3 flex-wrap">
        <form method="POST" action="<?= BASE_URL ?>controllers/process.php" class="flex-grow-1">
            <input type="hidden" name="action" value="approve_insight">
            <input type="hidden" name="id" value="<?= $id ?>">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <div class="mb-2">
                <label class="form-label fw-semibold">Admin Comment (optional)</label>
                <textarea name="admin_comment" class="form-control" rows="2" placeholder="Add a note for the researcher..."></textarea>
            </div>
            <button type="submit" class="btn btn-success px-4">
                <i class="bi bi-check-circle"></i> Approve Insight
            </button>
        </form>
        <form method="POST" action="<?= BASE_URL ?>controllers/process.php" class="flex-grow-1">
            <input type="hidden" name="action" value="reject_insight">
            <input type="hidden" name="id" value="<?= $id ?>">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <div class="mb-2">
                <label class="form-label fw-semibold">Rejection Reason (optional)</label>
                <textarea name="admin_comment" class="form-control" rows="2" placeholder="Explain why this is being rejected..."></textarea>
            </div>
            <button type="submit" class="btn btn-danger px-4">
                <i class="bi bi-x-circle"></i> Reject Insight
            </button>
        </form>
    </div>
    <?php else: ?>
    <div class="alert alert-<?= $insight['status'] === STATUS_APPROVED ? 'success' : 'danger' ?>">
        <strong><?= ucfirst($insight['status']) ?></strong> by <?= sanitize($insight['reviewer_name'] ?? 'Admin') ?>
        on <?= $insight['reviewed_at'] ? date('M d, Y H:i', strtotime($insight['reviewed_at'])) : '—' ?>
        <?php if ($insight['admin_comment']): ?>
        <hr class="my-2">
        <strong>Admin note:</strong> <?= sanitize($insight['admin_comment']) ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>

</div>
</main>
<?php include __DIR__ . '/../../layouts/footer.php'; ?>
</div>
