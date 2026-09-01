<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/ActivityLog.php';
requireAdmin();

$model  = new ActivityLog();
$action = sanitize($_GET['action'] ?? '');
$uid    = (int)($_GET['user_id'] ?? 0);
$page   = max(1, (int)($_GET['page'] ?? 1));
$total  = $model->countAll($action, $uid);
$pagination = paginate($total, $page);
$logs       = $model->getAll($pagination['offset'], $pagination['per_page'], $action, $uid);
$stats      = $model->getStats();

$pageTitle = 'Activity Log';
include __DIR__ . '/../layouts/header.php';
?>
<div class="d-flex flex-column min-vh-100">
<?php include __DIR__ . '/../layouts/navbar_admin.php'; ?>
<main class="flex-grow-1 py-4">
<div class="container-fluid px-4">

    <?= renderFlash() ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 fw-bold mb-0"><i class="bi bi-clock-history text-secondary"></i> Activity Log</h1>
        <div class="text-muted small text-end">
            <div><?= number_format($stats['total'] ?? 0) ?> total entries</div>
            <div><?= $stats['today'] ?? 0 ?> today · <?= $stats['this_week'] ?? 0 ?> this week</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Filter by Action</label>
                    <input type="text" name="action" class="form-control" placeholder="e.g. compound_update"
                           value="<?= sanitize($action) ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i> Filter</button>
                </div>
                <?php if ($action || $uid): ?>
                <div class="col-md-2">
                    <a href="?" class="btn btn-outline-secondary w-100"><i class="bi bi-x"></i> Clear</a>
                </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr><th>#</th><th>User</th><th>Action</th><th>Entity</th><th>Changes</th><th>IP</th><th>Date</th></tr>
                    </thead>
                    <tbody>
                    <?php if (empty($logs)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">No activity recorded.</td></tr>
                    <?php else: ?>
                        <?php foreach ($logs as $i => $log): ?>
                        <?php
                            $old = $log['old_values'] ? json_decode($log['old_values'], true) : [];
                            $new = $log['new_values'] ? json_decode($log['new_values'], true) : [];
                            $hasChanges = !empty($old) || !empty($new);
                        ?>
                        <tr>
                            <td class="text-muted small"><?= $pagination['offset'] + $i + 1 ?></td>
                            <td>
                                <div class="fw-semibold small"><?= sanitize($log['user_name'] ?? 'System') ?></div>
                                <?php if ($log['role']): ?>
                                <span class="badge <?= $log['role']===ROLE_ADMIN?'bg-danger':'bg-success' ?> small">
                                    <?= ucfirst($log['role']) ?>
                                </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-primary bg-opacity-75">
                                    <?= sanitize(str_replace('_',' ', $log['action'])) ?>
                                </span>
                            </td>
                            <td class="text-muted small">
                                <?php if ($log['entity_type']): ?>
                                <span class="badge bg-secondary"><?= sanitize($log['entity_type']) ?></span>
                                <?php if ($log['entity_id']): ?>#<?= $log['entity_id'] ?><?php endif; ?>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                            <td>
                                <?php if ($hasChanges): ?>
                                <button class="btn btn-xs btn-outline-info btn-sm"
                                        data-bs-toggle="modal" data-bs-target="#diffModal"
                                        data-old="<?= sanitize(json_encode($old)) ?>"
                                        data-new="<?= sanitize(json_encode($new)) ?>"
                                        data-action="<?= sanitize($log['action']) ?>">
                                    <i class="bi bi-eye"></i> Diff
                                </button>
                                <?php elseif ($log['details']): ?>
                                <span class="text-muted small"><?= sanitize(mb_strimwidth($log['details'],0,50,'…')) ?></span>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                            <td class="text-muted small"><?= sanitize($log['ip_address'] ?? '—') ?></td>
                            <td class="text-muted small text-nowrap">
                                <?= date('M d, Y H:i', strtotime($log['created_at'])) ?>
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

<!-- Diff Modal -->
<div class="modal fade" id="diffModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title"><i class="bi bi-eye"></i> Change Details — <span id="diffAction"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <table class="table table-sm mb-0" id="diffTable">
                    <thead class="table-light"><tr><th>Field</th><th class="text-danger">Before</th><th class="text-success">After</th></tr></thead>
                    <tbody id="diffBody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
document.getElementById('diffModal').addEventListener('show.bs.modal', function(e) {
    const btn    = e.relatedTarget;
    const old    = JSON.parse(btn.dataset.old || '{}');
    const nw     = JSON.parse(btn.dataset.new || '{}');
    const action = btn.dataset.action;
    document.getElementById('diffAction').textContent = action.replace(/_/g,' ');

    const allKeys = new Set([...Object.keys(old), ...Object.keys(nw)]);
    const tbody   = document.getElementById('diffBody');
    tbody.innerHTML = '';

    allKeys.forEach(key => {
        const o = old[key] ?? '—';
        const n = nw[key]  ?? '—';
        const changed = String(o) !== String(n);
        const tr = document.createElement('tr');
        if (changed) tr.classList.add('table-warning');
        tr.innerHTML = `<td><span class="badge bg-secondary">${key}</span></td>
                        <td class="text-danger small"><del>${o}</del></td>
                        <td class="text-success small"><strong>${n}</strong></td>`;
        tbody.appendChild(tr);
    });

    if (!tbody.children.length) {
        tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-3">No field-level changes recorded.</td></tr>';
    }
});
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
</div>
