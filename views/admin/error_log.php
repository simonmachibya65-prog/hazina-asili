<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/ErrorLog.php';
requireAdmin();

$model  = new ErrorLog();
$level  = in_array($_GET['level'] ?? '', ['critical','warning','notice']) ? $_GET['level'] : '';
$page   = max(1, (int)($_GET['page'] ?? 1));
$total  = $model->countAll($level);
$pagination = paginate($total, $page);
$errors     = $model->getAll($pagination['offset'], $pagination['per_page'], $level);
$counts     = $model->getLevelCounts();

$pageTitle = 'Error Log';
include __DIR__ . '/../layouts/header.php';
?>
<div class="d-flex flex-column min-vh-100">
<?php include __DIR__ . '/../layouts/navbar_admin.php'; ?>
<main class="flex-grow-1 py-4">
<div class="container-fluid px-4">

    <?= renderFlash() ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 fw-bold mb-0"><i class="bi bi-bug text-danger"></i> Error Log</h1>
        <form method="POST" action="<?= BASE_URL ?>controllers/process.php"
              onsubmit="return confirm('Clear old error logs?')">
            <input type="hidden" name="action" value="clear_error_log">
            <input type="hidden" name="days" value="30">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <button type="submit" class="btn btn-sm btn-outline-danger">
                <i class="bi bi-trash"></i> Clear Entries &gt;30 days
            </button>
        </form>
    </div>

    <!-- Level filter + counts -->
    <div class="row g-3 mb-4">
        <?php foreach (['critical'=>'danger','warning'=>'warning','notice'=>'info'] as $lvl => $color): ?>
        <div class="col-md-4">
            <a href="?level=<?= $lvl ?>" class="text-decoration-none">
                <div class="card border-0 shadow-sm <?= $level===$lvl ? "border-{$color} border-2" : '' ?>">
                    <div class="card-body d-flex align-items-center gap-3 py-3">
                        <span class="badge bg-<?= $color ?> fs-6 px-3"><?= ucfirst($lvl) ?></span>
                        <span class="fw-bold fs-5"><?= number_format($counts[$lvl]) ?></span>
                        <span class="text-muted small">entries</span>
                    </div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if ($level): ?>
    <div class="mb-3">
        <a href="?" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x"></i> Clear filter</a>
    </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr><th>#</th><th>Level</th><th>Message</th><th>File:Line</th><th>URL</th><th>Date</th><th></th></tr>
                    </thead>
                    <tbody>
                    <?php if (empty($errors)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">
                            <i class="bi bi-check-circle text-success"></i> No errors logged.
                        </td></tr>
                    <?php else: ?>
                        <?php foreach ($errors as $i => $e): ?>
                        <tr>
                            <td class="text-muted small"><?= $pagination['offset'] + $i + 1 ?></td>
                            <td>
                                <?php $lc = ['critical'=>'danger','warning'=>'warning','notice'=>'info'][$e['level']] ?? 'secondary'; ?>
                                <span class="badge bg-<?= $lc ?>"><?= $e['level'] ?></span>
                            </td>
                            <td class="small" style="max-width:300px">
                                <?= sanitize(mb_strimwidth($e['message'], 0, 120, '…')) ?>
                            </td>
                            <td class="text-muted small text-nowrap">
                                <?= sanitize(basename($e['file'] ?? '')) ?>:<?= $e['line'] ?>
                            </td>
                            <td class="text-muted small" style="max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                                <?= sanitize($e['url'] ?? '—') ?>
                            </td>
                            <td class="text-muted small text-nowrap">
                                <?= date('M d, H:i', strtotime($e['created_at'])) ?>
                            </td>
                            <td>
                                <?php if ($e['trace']): ?>
                                <button class="btn btn-xs btn-outline-secondary btn-sm"
                                        data-bs-toggle="modal" data-bs-target="#traceModal"
                                        data-trace="<?= sanitize($e['trace']) ?>"
                                        data-msg="<?= sanitize($e['message']) ?>">
                                    <i class="bi bi-code"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../layouts/pagination.php'; ?>

</div>
</main>

<!-- Stack Trace Modal -->
<div class="modal fade" id="traceModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title"><i class="bi bi-code"></i> Stack Trace</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="fw-semibold" id="traceMsg"></p>
                <pre class="bg-dark text-light p-3 rounded small" id="traceContent" style="max-height:400px;overflow-y:auto"></pre>
            </div>
        </div>
    </div>
</div>
<script>
document.getElementById('traceModal').addEventListener('show.bs.modal', function(e) {
    const btn = e.relatedTarget;
    document.getElementById('traceMsg').textContent     = btn.dataset.msg;
    document.getElementById('traceContent').textContent = btn.dataset.trace;
});
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
</div>
