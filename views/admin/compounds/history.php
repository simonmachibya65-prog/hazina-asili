<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../models/Compound.php';
require_once __DIR__ . '/../../../models/CompoundVersion.php';
requireAdmin();

$id       = (int)($_GET['id'] ?? 0);
$model    = new Compound();
$compound = $model->findById($id);
if (!$compound) { setFlash('error', 'Compound not found.'); redirect('views/admin/compounds/index.php'); }

$vModel   = new CompoundVersion();
$history  = $vModel->getHistory($id);

$pageTitle = 'Version History: ' . sanitize($compound['name']);
include __DIR__ . '/../../layouts/header.php';
?>
<div class="d-flex flex-column min-vh-100">
<?php include __DIR__ . '/../../layouts/navbar_admin.php'; ?>
<main class="flex-grow-1 py-4">
<div class="container px-4" style="max-width:1000px">

    <?= renderFlash() ?>

    <div class="d-flex align-items-center mb-4">
        <a href="<?= BASE_URL ?>views/admin/compounds/view.php?id=<?= $id ?>" class="btn btn-outline-secondary me-3">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <h1 class="h3 fw-bold mb-0">Version History</h1>
            <p class="text-muted mb-0"><?= sanitize($compound['name']) ?> — Current version: <strong>v<?= $compound['version'] ?></strong></p>
        </div>
    </div>

    <?php if (empty($history)): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5 text-muted">
            <i class="bi bi-clock-history fs-1 d-block mb-3"></i>
            No version history yet. History is recorded when the compound is edited.
        </div>
    </div>
    <?php else: ?>

    <div class="timeline">
        <?php foreach ($history as $i => $v): ?>
        <?php
            $old = $v['old_values'] ? json_decode($v['old_values'], true) : [];
            $new = $v['new_values'] ? json_decode($v['new_values'], true) : [];
            $changes = [];
            foreach ($new as $field => $newVal) {
                $oldVal = $old[$field] ?? null;
                if ((string)$oldVal !== (string)$newVal) {
                    $changes[] = ['field' => $field, 'old' => $oldVal, 'new' => $newVal];
                }
            }
        ?>
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <div>
                    <span class="badge bg-primary me-2">v<?= $v['version'] ?></span>
                    <strong><?= sanitize($v['change_summary'] ?? 'No summary') ?></strong>
                </div>
                <div class="text-end">
                    <div class="small text-muted">
                        <i class="bi bi-person"></i> <?= sanitize($v['changed_by_name'] ?? 'Unknown') ?>
                    </div>
                    <div class="small text-muted">
                        <i class="bi bi-clock"></i> <?= date('M d, Y H:i', strtotime($v['created_at'])) ?>
                    </div>
                </div>
            </div>
            <?php if (!empty($changes)): ?>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr><th style="width:150px">Field</th><th>Before</th><th>After</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($changes as $ch): ?>
                        <tr>
                            <td><span class="badge bg-secondary"><?= sanitize($ch['field']) ?></span></td>
                            <td class="text-danger small"><del><?= sanitize((string)($ch['old'] ?? '—')) ?></del></td>
                            <td class="text-success small"><strong><?= sanitize((string)$ch['new']) ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
            <div class="card-footer bg-white">
                <form method="POST" action="<?= BASE_URL ?>controllers/process.php"
                      onsubmit="return confirm('Roll back to version <?= $v['version'] ?>? Current data will be saved as a new version.')">
                    <input type="hidden" name="action" value="rollback_compound">
                    <input type="hidden" name="id" value="<?= $id ?>">
                    <input type="hidden" name="version" value="<?= $v['version'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <button type="submit" class="btn btn-sm btn-outline-warning">
                        <i class="bi bi-arrow-counterclockwise"></i> Rollback to v<?= $v['version'] ?>
                    </button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>
</main>
<?php include __DIR__ . '/../../layouts/footer.php'; ?>
</div>
