<?php if (isset($pagination) && $pagination['total_pages'] > 1): ?>
<nav aria-label="Page navigation" class="mt-3">
    <ul class="pagination justify-content-center flex-wrap">
        <li class="page-item <?= $pagination['current'] <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['current'] - 1])) ?>">
                <i class="bi bi-chevron-left"></i>
            </a>
        </li>
        <?php for ($i = max(1, $pagination['current'] - 2); $i <= min($pagination['total_pages'], $pagination['current'] + 2); $i++): ?>
            <li class="page-item <?= $i === $pagination['current'] ? 'active' : '' ?>">
                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>
        <li class="page-item <?= $pagination['current'] >= $pagination['total_pages'] ? 'disabled' : '' ?>">
            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['current'] + 1])) ?>">
                <i class="bi bi-chevron-right"></i>
            </a>
        </li>
    </ul>
    <p class="text-center text-muted small">
        Showing page <?= $pagination['current'] ?> of <?= $pagination['total_pages'] ?>
        (<?= $pagination['total'] ?> total records)
    </p>
</nav>
<?php endif; ?>
