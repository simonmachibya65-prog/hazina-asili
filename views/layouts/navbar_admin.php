<?php
require_once __DIR__ . '/../../models/Insight.php';
require_once __DIR__ . '/../../models/Recommendation.php';
require_once __DIR__ . '/../../models/Notification.php';
$pendingInsights = (new Insight())->countPending();
$pendingRecs     = (new Recommendation())->countPending();
$totalPending    = $pendingInsights + $pendingRecs;
$unreadNotifs    = (new Notification())->countUnread($_SESSION['user_id']);
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow" role="navigation" aria-label="Main navigation">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= BASE_URL ?>views/admin/dashboard.php" style="text-decoration:none">
            <div class="d-flex align-items-center justify-content-center rounded-circle" style="width:50px;height:50px;background:rgba(25,135,84,0.25);border:2px solid rgba(25,135,84,0.5)">
                <span style="font-size:1.8rem">🌿</span>
            </div>
            <div class="d-flex flex-column lh-1">
                <span class="fw-bold text-white" style="font-size:1.4rem;letter-spacing:1.5px">HAZINA ASILI</span>
                <span style="font-size:.7rem;color:rgba(255,255,255,.5);letter-spacing:.5px">Admin Panel</span>
            </div>
            <span class="badge bg-danger ms-2" style="font-size:.7rem">ADMIN</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav" aria-controls="adminNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="adminNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?= activeNav('dashboard.php') ?>" href="<?= BASE_URL ?>views/admin/dashboard.php">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-capsule"></i> Compounds
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark">
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>views/admin/compounds/index.php">All Compounds</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>views/admin/compounds/create.php">Add Compound</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-tree"></i> Organisms
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark">
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>views/admin/organisms/index.php">All Organisms</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>views/admin/organisms/create.php">Add Organism</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-journal-text"></i> References
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark">
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>views/admin/references/index.php">All References</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>views/admin/references/create.php">Add Reference</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-people"></i> Users
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark">
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>views/admin/users/index.php">All Users</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>views/admin/users/create.php">Add User</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-chat-square-text"></i> Submissions
                        <?php if ($totalPending > 0): ?>
                            <span class="badge bg-warning text-dark"><?= $totalPending ?></span>
                        <?php endif; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark">
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>views/admin/insights/index.php">
                            Insights <?php if ($pendingInsights): ?><span class="badge bg-warning text-dark"><?= $pendingInsights ?></span><?php endif; ?>
                        </a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>views/admin/recommendations/index.php">
                            Recommendations <?php if ($pendingRecs): ?><span class="badge bg-warning text-dark"><?= $pendingRecs ?></span><?php endif; ?>
                        </a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-tools"></i> System
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark">
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>views/admin/activity_log.php"><i class="bi bi-clock-history me-2"></i>Activity Log</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>views/admin/error_log.php"><i class="bi bi-bug me-2"></i>Error Log</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>views/admin/export.php"><i class="bi bi-download me-2"></i>Export Data</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>views/admin/import.php"><i class="bi bi-upload me-2"></i>Import Data</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>views/admin/report.php"><i class="bi bi-file-earmark-pdf me-2"></i>Reports</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>views/admin/backup.php"><i class="bi bi-database me-2"></i>Backup</a></li>
                    </ul>
                </li>
            </ul>
            <ul class="navbar-nav align-items-center">
                <!-- Search -->
                <li class="nav-item me-2 d-none d-lg-block">
                    <form class="d-flex" style="max-width:200px" action="<?= BASE_URL ?>views/admin/compounds/index.php" method="GET">
                        <div class="input-group input-group-sm">
                            <input type="text" name="search" class="form-control form-control-sm bg-dark border-secondary text-white" placeholder="Search... (Ctrl+K)" aria-label="Search">
                        </div>
                    </form>
                </li>
                <!-- Theme toggle -->
                <li class="nav-item me-1">
                    <button class="theme-toggle nav-link" onclick="toggleDarkMode()" title="Toggle theme (Alt+T)" aria-label="Toggle dark mode">
                        <i class="bi bi-moon-fill theme-icon-dark"></i>
                        <i class="bi bi-sun-fill theme-icon-light"></i>
                    </button>
                </li>
                <!-- Notification Bell -->
                <li class="nav-item dropdown me-2">
                    <a class="nav-link position-relative" href="#" data-bs-toggle="dropdown" aria-label="Notifications">
                        <i class="bi bi-bell fs-5"></i>
                        <?php if ($unreadNotifs > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:.6rem">
                            <?= $unreadNotifs > 9 ? '9+' : $unreadNotifs ?>
                        </span>
                        <?php endif; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark" style="min-width:320px;max-height:400px;overflow-y:auto">
                        <li class="px-3 py-2 d-flex justify-content-between align-items-center border-bottom border-secondary">
                            <span class="fw-semibold">Notifications</span>
                            <a href="<?= BASE_URL ?>views/notifications.php" class="small text-info">View all</a>
                        </li>
                        <?php
                        $notifs = (new Notification())->getForUser($_SESSION['user_id'], 5);
                        if (empty($notifs)): ?>
                        <li class="px-3 py-3 text-muted small text-center">No notifications</li>
                        <?php else: foreach ($notifs as $n): ?>
                        <li>
                            <a class="dropdown-item py-2 <?= $n['is_read'] ? 'text-muted' : 'fw-semibold' ?>"
                               href="<?= $n['link'] ? sanitize($n['link']) : '#' ?>">
                                <div class="small"><?= sanitize($n['title']) ?></div>
                                <div class="text-muted" style="font-size:.75rem;white-space:normal"><?= sanitize(mb_strimwidth($n['message'],0,60,'…')) ?></div>
                                <div class="text-muted" style="font-size:.7rem"><?= formatDate($n['created_at']) ?></div>
                            </a>
                        </li>
                        <?php endforeach; endif; ?>
                    </ul>
                </li>
                <!-- User menu -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i> <?= sanitize(currentUser()['name']) ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end">
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>views/profile.php"><i class="bi bi-person me-2"></i>My Profile</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>views/notifications.php"><i class="bi bi-bell me-2"></i>Notifications <?php if ($unreadNotifs): ?><span class="badge bg-warning text-dark"><?= $unreadNotifs ?></span><?php endif; ?></a></li>
                        <li><hr class="dropdown-divider border-secondary"></li>
                        <li>
                            <form method="POST" action="<?= BASE_URL ?>controllers/process.php">
                                <input type="hidden" name="action" value="admin_logout">
                                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                <button type="submit" class="dropdown-item text-warning">
                                    <i class="bi bi-shield-x me-2"></i>Exit Admin
                                </button>
                            </form>
                        </li>
                        <li>
                            <form method="POST" action="<?= BASE_URL ?>controllers/process.php">
                                <input type="hidden" name="action" value="logout">
                                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="bi bi-box-arrow-right me-2"></i>Logout
                                </button>
                            </form>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
