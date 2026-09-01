<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../models/Recommendation.php';
require_once __DIR__ . '/../../../models/Compound.php';
requireAdmin();

$id  = (int)($_GET['id'] ?? 0);
$rec = (new Recommendation())->findById($id);
if (!$rec) { setFlash('error', 'Recommendation not found.'); redirect('views/admin/recommendations/index.php'); }

// Load current compound value for comparison
$compound = (new Compound())->findById($rec['compound_id']);
$currentValue = $compound[$rec['field_to_change']] ?? '—';

$pageTitle = 'Review Recommendation';
include __DIR__ . '/../../layouts/header.php';
?>
<div class="d-flex flex-column min-vh-100">
<?php include __DIR__ . '/../../layouts/navbar_admin.php'; ?>
<main class="flex-grow-1 py-4">
<div class="container px-4" style="max-width:750px">

    <?= renderFlash() ?>

    <div class="d-flex align-items-center mb-4">
        <a href="<?= BASE_URL ?>views/admin/recommendations/index.php" class="btn btn-outline-secondary me-3">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h1 class="h3 fw-bold mb-0">Review Recommendation</h1>
        <span class="ms-3"><?= statusBadge($rec['status']) ?></span>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white">
            <div class="row">
                <div class="col-md-4">
                    <small class="text-muted">Researcher</small>
                    <div class="fw-semibold"><?= sanitize($rec['researcher_name']) ?></div>
                </div>
                <div class="col-md-4">
                    <small class="text-muted">Compound</small>
                    <div class="fw-semibold"><?= sanitize($rec['compound_name']) ?></div>
                </div>
                <div class="col-md-4">
                    <small class="text-muted">Submitted</small>
                    <div class="small"><?= formatDate($rec['created_at']) ?></div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <h6 class="text-muted mb-3">Proposed Change</h6>
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="p-3 bg-light rounded text-center">
                        <small class="text-muted d-block">Field</small>
                        <span class="badge bg-secondary fs-6"><?= sanitize($rec['field_to_change']) ?></span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 bg-light rounded">
                        <small class="text-muted d-block mb-1">Current Value</small>
                        <span class="text-danger fw-semibold"><?= sanitize((string)$currentValue) ?></span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 bg-success bg-opacity-10 border border-success rounded">
                        <small class="text-muted d-block mb-1">Suggested Value</small>
                        <span class="text-success fw-semibold"><?= sanitize($rec['suggested_value']) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($rec['status'] === STATUS_PENDING): ?>
    <div class="d-flex gap-3 flex-wrap">
        <form method="POST" action="<?= BASE_URL ?>controllers/process.php" class="flex-grow-1">
            <input type="hidden" name="action" value="approve_recommendation">
            <input type="hidden" name="id" value="<?= $id ?>">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <div class="mb-2">
                <label class="form-label fw-semibold">Admin Comment (optional)</label>
                <textarea name="admin_comment" class="form-control" rows="2" placeholder="Add a note for the researcher..."></textarea>
            </div>
            <button type="submit" class="btn btn-success px-4">
                <i class="bi bi-check-circle"></i> Approve
            </button>
        </form>
        <form method="POST" action="<?= BASE_URL ?>controllers/process.php" class="flex-grow-1">
            <input type="hidden" name="action" value="reject_recommendation">
            <input type="hidden" name="id" value="<?= $id ?>">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <div class="mb-2">
                <label class="form-label fw-semibold">Rejection Reason (optional)</label>
                <textarea name="admin_comment" class="form-control" rows="2" placeholder="Explain why this is being rejected..."></textarea>
            </div>
            <button type="submit" class="btn btn-danger px-4">
                <i class="bi bi-x-circle"></i> Reject
            </button>
        </form>
    </div>
    <?php else: ?>
    <div class="alert alert-<?= $rec['status'] === STATUS_APPROVED ? 'success' : 'danger' ?>">
        <strong><?= ucfirst($rec['status']) ?></strong> by <?= sanitize($rec['reviewer_name'] ?? 'Admin') ?>
        on <?= $rec['reviewed_at'] ? date('M d, Y H:i', strtotime($rec['reviewed_at'])) : '—' ?>
        <?php if ($rec['admin_comment']): ?>
        <hr class="my-2">
        <strong>Admin note:</strong> <?= sanitize($rec['admin_comment']) ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>

</div>
</main>
<?php include __DIR__ . '/../../layouts/footer.php'; ?>
</div>
