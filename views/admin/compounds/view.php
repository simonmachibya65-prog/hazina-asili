<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../models/Compound.php';
requireLogin();

$id = (int)($_GET['id'] ?? 0);
$model = new Compound();
$compound = $model->findById($id);
if (!$compound) { setFlash('error', 'Compound not found.'); redirect(isAdmin() ? 'views/admin/compounds/index.php' : 'views/researcher/compounds/index.php'); }

$references = $model->getReferences($id);
$pageTitle  = sanitize($compound['name']);
$backUrl    = isAdmin() ? BASE_URL.'views/admin/compounds/index.php' : BASE_URL.'views/researcher/compounds/index.php';
$navFile    = isAdmin() && !empty($_SESSION['admin_secret_access']) ? __DIR__ . '/../../layouts/navbar_admin.php' : __DIR__ . '/../../layouts/navbar_researcher.php';
include __DIR__ . '/../../layouts/header.php';
?>
<div class="d-flex flex-column min-vh-100">
<?php include $navFile; ?>
<main class="flex-grow-1 py-4">
<div class="container px-4" style="max-width:950px">

    <div class="d-flex align-items-center mb-4">
        <a href="<?= $backUrl ?>" class="btn btn-outline-secondary me-3"><i class="bi bi-arrow-left"></i></a>
        <h1 class="h3 fw-bold mb-0"><?= sanitize($compound['name']) ?></h1>
        <?php if (isAdmin()): ?>
        <a href="<?= BASE_URL ?>views/admin/compounds/edit.php?id=<?= $id ?>" class="btn btn-warning ms-auto">
            <i class="bi bi-pencil"></i> Edit
        </a>
        <a href="<?= BASE_URL ?>views/admin/compounds/history.php?id=<?= $id ?>" class="btn btn-outline-secondary ms-2">
            <i class="bi bi-clock-history"></i> History (v<?= $compound['version'] ?>)
        </a>
        <?php endif; ?>
    </div>

    <div class="row g-4">
        <!-- Molecular Structure Image -->
        <div class="col-md-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-bezier2"></i> Molecular Structure</h5>
                </div>
                <div class="card-body text-center d-flex align-items-center justify-content-center">
                    <?php if (!empty($compound['structure_image'])): ?>
                        <img src="<?= BASE_URL ?>assets/uploads/compounds/<?= sanitize($compound['structure_image']) ?>"
                             alt="<?= sanitize($compound['name']) ?> structure"
                             class="img-fluid rounded" style="max-height:280px">
                    <?php else: ?>
                        <div class="text-muted py-4">
                            <i class="bi bi-bezier2 fs-1 d-block mb-2"></i>
                            <p class="mb-1">No structure image uploaded</p>
                            <code class="fs-5"><?= sanitize($compound['formula']) ?></code>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Compound Details -->
        <div class="col-md-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Compound Details</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped mb-0">
                        <tbody>
                            <tr><th class="ps-4" style="width:40%">Name</th><td class="fw-semibold"><?= sanitize($compound['name']) ?></td></tr>
                            <tr><th class="ps-4">Molecular Formula</th><td><code class="fs-6"><?= sanitize($compound['formula']) ?></code></td></tr>
                            <tr><th class="ps-4">Molecular Weight</th><td><?= number_format($compound['molecular_weight'], 4) ?> g/mol</td></tr>
                            <tr><th class="ps-4">Version</th><td><span class="badge bg-secondary">v<?= $compound['version'] ?></span></td></tr>
                            <?php if ($compound['organism_name']): ?>
                            <tr>
                                <th class="ps-4">Source Organism</th>
                                <td>
                                    <a href="<?= BASE_URL ?>views/<?= isAdmin() ? 'admin' : 'researcher' ?>/organisms/view.php?id=<?= $compound['organism_id'] ?>"
                                       class="text-decoration-none">
                                        <em><?= sanitize($compound['organism_name']) ?></em>
                                    </a>
                                </td>
                            </tr>
                            <tr><th class="ps-4">Kingdom</th><td><?= sanitize($compound['kingdom'] ?? '—') ?></td></tr>
                            <tr><th class="ps-4">Family</th><td><?= sanitize($compound['organism_family'] ?? '—') ?></td></tr>
                            <tr><th class="ps-4">Order</th><td><?= sanitize($compound['organism_order'] ?? '—') ?></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Description -->
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-file-text"></i> Description</h5>
                </div>
                <div class="card-body">
                    <?= $compound['description'] ? nl2br(sanitize($compound['description'])) : '<p class="text-muted">No description available.</p>' ?>
                </div>
            </div>
        </div>

        <!-- Source Organism Card (if linked) -->
        <?php if ($compound['organism_name'] && !empty($compound['organism_image'])): ?>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-warning bg-opacity-25">
                    <h5 class="mb-0"><i class="bi bi-tree text-warning"></i> Source Organism</h5>
                </div>
                <div class="card-body text-center">
                    <img src="<?= BASE_URL ?>assets/uploads/organisms/<?= sanitize($compound['organism_image']) ?>"
                         alt="<?= sanitize($compound['organism_name']) ?>"
                         class="img-fluid rounded mb-2" style="max-height:160px">
                    <p class="fst-italic fw-semibold mb-0"><?= sanitize($compound['organism_name']) ?></p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- References -->
        <?php if (!empty($references)): ?>
        <div class="<?= ($compound['organism_name'] && !empty($compound['organism_image'])) ? 'col-md-6' : 'col-12' ?>">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-journal-text"></i> References (<?= count($references) ?>)</h5>
                </div>
                <ul class="list-group list-group-flush">
                    <?php foreach ($references as $ref): ?>
                    <li class="list-group-item">
                        <strong><?= sanitize($ref['title']) ?></strong><br>
                        <small class="text-muted"><?= sanitize($ref['author']) ?> (<?= $ref['year'] ?>)</small><br>
                        <small><?= sanitize($ref['citation']) ?></small>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>
    </div>

</div>
</main>
<?php include __DIR__ . '/../../layouts/footer.php'; ?>
</div>
