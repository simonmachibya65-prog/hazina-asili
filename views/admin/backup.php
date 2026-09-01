<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/ActivityLog.php';
requireAdmin();

$message = '';

// ── Handle backup download ────────────────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'backup_db') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid security token.');
        redirect('views/admin/backup.php');
    }

    $db = Database::getInstance()->getConnection();
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

    $sql  = "-- HAZINA ASILI Database Backup\n";
    $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $sql .= "-- By: " . sanitize(currentUser()['name']) . "\n\n";
    $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

    foreach ($tables as $table) {
        // Table structure
        $create = $db->query("SHOW CREATE TABLE `{$table}`")->fetch();
        $sql .= "-- Table: {$table}\n";
        $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
        $sql .= $create['Create Table'] . ";\n\n";

        // Table data
        $rows = $db->query("SELECT * FROM `{$table}`")->fetchAll();
        if (!empty($rows)) {
            $cols = '`' . implode('`, `', array_keys($rows[0])) . '`';
            $sql .= "INSERT INTO `{$table}` ({$cols}) VALUES\n";
            $vals = [];
            foreach ($rows as $row) {
                $escaped = array_map(function($v) use ($db) {
                    if ($v === null) return 'NULL';
                    return "'" . addslashes($v) . "'";
                }, array_values($row));
                $vals[] = '(' . implode(', ', $escaped) . ')';
            }
            $sql .= implode(",\n", $vals) . ";\n\n";
        }
    }

    $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";

    (new ActivityLog())->log($_SESSION['user_id'], 'database_backup', 'Manual database backup downloaded');

    $filename = 'hazina_asili_backup_' . date('Ymd_His') . '.sql';
    header('Content-Type: application/octet-stream');
    header("Content-Disposition: attachment; filename=\"{$filename}\"");
    header('Content-Length: ' . strlen($sql));
    echo $sql;
    exit;
}

$pageTitle = 'Database Backup';
include __DIR__ . '/../layouts/header.php';
?>
<div class="d-flex flex-column min-vh-100">
<?php include __DIR__ . '/../layouts/navbar_admin.php'; ?>
<main class="flex-grow-1 py-4">
<div class="container px-4" style="max-width:700px">

    <?= renderFlash() ?>

    <h1 class="h3 fw-bold mb-4"><i class="bi bi-database text-dark"></i> Database Backup</h1>

    <div class="row g-4">
        <!-- Manual Backup -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center p-4">
                    <i class="bi bi-download text-success fs-1 mb-3 d-block"></i>
                    <h5 class="fw-semibold">Manual Backup</h5>
                    <p class="text-muted small">Download a complete SQL dump of the entire database including all tables and data.</p>
                </div>
                <div class="card-footer bg-white border-0 text-center pb-4">
                    <form method="POST" action="<?= BASE_URL ?>views/admin/backup.php">
                        <input type="hidden" name="action" value="backup_db">
                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                        <button type="submit" class="btn btn-success px-4">
                            <i class="bi bi-download"></i> Download Backup (.sql)
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Restore Instructions -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <i class="bi bi-arrow-counterclockwise text-warning fs-1 mb-3 d-block text-center"></i>
                    <h5 class="fw-semibold text-center">Restore Database</h5>
                    <p class="text-muted small">To restore from a backup file:</p>
                    <ol class="small text-muted">
                        <li>Open <strong>phpMyAdmin</strong></li>
                        <li>Select the <code>natural_compounds_db</code> database</li>
                        <li>Click <strong>Import</strong> tab</li>
                        <li>Choose your <code>.sql</code> backup file</li>
                        <li>Click <strong>Go</strong></li>
                    </ol>
                    <div class="alert alert-warning small py-2 mb-0">
                        <i class="bi bi-exclamation-triangle"></i>
                        Restoring will overwrite all current data.
                    </div>
                </div>
            </div>
        </div>

        <!-- Backup Tips -->
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold">
                    <i class="bi bi-info-circle text-info"></i> Backup Best Practices
                </div>
                <div class="card-body">
                    <ul class="mb-0 small text-muted">
                        <li>Schedule regular backups — at minimum weekly for active research databases.</li>
                        <li>Store backup files in a secure, off-site location (cloud storage, external drive).</li>
                        <li>Test your backups periodically by restoring to a test environment.</li>
                        <li>Keep multiple backup versions — don't overwrite old backups immediately.</li>
                        <li>For automated backups, use XAMPP's scheduled tasks or a cron job with <code>mysqldump</code>.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

</div>
</main>
<?php include __DIR__ . '/../layouts/footer.php'; ?>
</div>
