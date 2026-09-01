<?php
require_once __DIR__ . '/../../config/config.php';
http_response_code(403);
$pageTitle = '403 Forbidden';
include __DIR__ . '/../layouts/header.php';
?>
<div class="min-vh-100 d-flex align-items-center justify-content-center bg-light">
    <div class="text-center">
        <i class="bi bi-shield-x text-danger" style="font-size:5rem"></i>
        <h1 class="display-4 fw-bold mt-3">403</h1>
        <p class="lead text-muted">Access Denied</p>
        <p class="text-muted">You don't have permission to view this page.</p>
        <a href="<?= BASE_URL ?>" class="btn btn-primary mt-2">
            <i class="bi bi-house"></i> Go Home
        </a>
    </div>
</div>
<?php include __DIR__ . '/../layouts/footer.php'; ?>
