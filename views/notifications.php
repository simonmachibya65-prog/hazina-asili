<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Notification.php';
requireLogin();

$model  = new Notification();
$uid    = $_SESSION['user_id'];
$page   = max(1, (int)($_GET['page'] ?? 1));
$total  = $model->countAll($uid);
$pagination = paginate($total, $page);
$notifs     = $model->getAll($uid, $pagination['offset'], $pagination['per_page']);

$pageTitle = 'Notifications';
$navFile   = isAdmin() && !empty($_SESSION['admin_secret_access']) ? __DIR__ . '/layouts/navbar_admin.php' : __DIR__ . '/layouts/navbar_researcher.php';
include __DIR__ . '/layouts/header.php';
?>
<div class="d-flex flex-column min-vh-100">
<?php include $navFile; ?>
<main class="flex-grow-1 py-4">
<div class="container px-4" style="max-width:800px">

    <?= renderFlash() ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 fw-bold mb-0"><i class="bi bi-bell"></i> Notifications</h1>
        <?php if ($total > 0): ?>
        <form method="POST" action="<?= BASE_URL ?>controllers/process.php">
            <input type="hidden" name="action" value="mark_all_read">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <button type="submit" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-check-all"></i> Mark All Read
            </button>
        </form>
        <?php endif; ?>
    </div>

    <?php if (empty($notifs)): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5 text-muted">
            <i class="bi bi-bell-slash fs-1 d-block mb-3"></i>
            No notifications yet.
        </div>
    </div>
    <?php else: ?>
    <div class="card border-0 shadow-sm">
        <div class="list-group list-group-flush">
            <?php foreach ($notifs as $n): ?>
            <?php
            $typeColors = ['success'=>'success','danger'=>'danger','warning'=>'warning','info'=>'info','recommendation'=>'primary','insight'=>'primary'];
            $color = $typeColors[$n['type']] ?? 'secondary';
            ?>
            <div class="list-group-item list-group-item-action <?= !$n['is_read'] ? 'bg-light' : '' ?>">
                <div class="d-flex align-items-start gap-3">
                    <div class="mt-1">
                        <span class="badge bg-<?= $color ?> rounded-circle p-2">
                            <i class="bi bi-bell"></i>
                        </span>
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between">
                            <strong class="<?= !$n['is_read'] ? '' : 'text-muted' ?>">
                                <?= sanitize($n['title']) ?>
                            </strong>
                            <small class="text-muted text-nowrap ms-2">
                                <?= date('M d, H:i', strtotime($n['created_at'])) ?>
                            </small>
                        </div>
                        <p class="mb-1 small <?= !$n['is_read'] ? '' : 'text-muted' ?>">
                            <?= sanitize($n['message']) ?>
                        </p>
                        <div class="d-flex gap-2 mt-1">
                            <?php if ($n['link']): ?>
                            <a href="<?= sanitize($n['link']) ?>" class="btn btn-xs btn-outline-<?= $color ?> btn-sm">
                                <i class="bi bi-arrow-right"></i> View
                            </a>
                            <?php endif; ?>
                            <?php if (!$n['is_read']): ?>
                            <a href="<?= BASE_URL ?>controllers/process.php?mark_read=<?= $n['id'] ?>"
                               class="btn btn-xs btn-outline-secondary btn-sm">
                                <i class="bi bi-check"></i> Mark Read
                            </a>
                            <?php endif; ?>
                            <form method="POST" action="<?= BASE_URL ?>controllers/process.php" class="d-inline">
                                <input type="hidden" name="action" value="delete_notification">
                                <input type="hidden" name="id" value="<?= $n['id'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                <button type="submit" class="btn btn-xs btn-outline-danger btn-sm">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php include __DIR__ . '/layouts/pagination.php'; ?>
    <?php endif; ?>

</div>
</main>
<?php include __DIR__ . '/layouts/footer.php'; ?>
</div>
