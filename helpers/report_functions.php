<?php
/**
 * Report generation functions
 */

function generateCompoundReport($compound, $references) {
    ?>
<!DOCTYPE html>
<html><head>
<meta charset="UTF-8">
<title>Report: <?= sanitize($compound['name']) ?></title>
<style>
    body { font-family: 'Segoe UI', Tahoma, sans-serif; margin: 2cm; color: #333; line-height: 1.6; }
    h1 { color: #198754; border-bottom: 3px solid #198754; padding-bottom: 10px; }
    h2 { color: #495057; margin-top: 30px; }
    table { width: 100%; border-collapse: collapse; margin: 15px 0; }
    th, td { padding: 10px 15px; border: 1px solid #dee2e6; text-align: left; }
    th { background: #f8f9fa; font-weight: 600; width: 35%; }
    .formula { font-family: monospace; font-size: 1.1em; color: #0d6efd; }
    .footer { margin-top: 40px; border-top: 1px solid #dee2e6; padding-top: 15px; font-size: 0.85em; color: #6c757d; }
    .structure-img { max-width: 300px; max-height: 250px; display: block; margin: 15px auto; }
    @media print { body { margin: 1.5cm; } .no-print { display: none; } }
</style>
</head><body>
    <div class="no-print" style="margin-bottom:20px;padding:10px;background:#d4edda;border-radius:8px;">
        <strong>Tip:</strong> Press <kbd>Ctrl+P</kbd> (or <kbd>Cmd+P</kbd>) to save as PDF.
        <button onclick="window.print()" style="margin-left:15px;padding:5px 15px;cursor:pointer">🖨️ Print</button>
    </div>
    <h1>Compound Report: <?= sanitize($compound['name']) ?></h1>
    <p><em>Generated: <?= date('F j, Y \a\t H:i') ?></em></p>
    <?php if (!empty($compound['structure_image'])): ?>
    <img src="<?= BASE_URL ?>assets/uploads/compounds/<?= sanitize($compound['structure_image']) ?>" alt="Structure" class="structure-img">
    <?php endif; ?>
    <h2>Chemical Properties</h2>
    <table>
        <tr><th>Compound Name</th><td><?= sanitize($compound['name']) ?></td></tr>
        <tr><th>Molecular Formula</th><td class="formula"><?= sanitize($compound['formula']) ?></td></tr>
        <tr><th>Molecular Weight</th><td><?= number_format($compound['molecular_weight'], 4) ?> g/mol</td></tr>
        <tr><th>Version</th><td>v<?= $compound['version'] ?></td></tr>
    </table>
    <?php if ($compound['organism_name']): ?>
    <h2>Biological Source</h2>
    <table>
        <tr><th>Organism</th><td><em><?= sanitize($compound['organism_name']) ?></em></td></tr>
        <tr><th>Kingdom</th><td><?= sanitize($compound['kingdom'] ?? '—') ?></td></tr>
        <tr><th>Family</th><td><?= sanitize($compound['organism_family'] ?? '—') ?></td></tr>
        <tr><th>Order</th><td><?= sanitize($compound['organism_order'] ?? '—') ?></td></tr>
    </table>
    <?php endif; ?>
    <?php if ($compound['description']): ?>
    <h2>Description</h2>
    <p><?= nl2br(sanitize($compound['description'])) ?></p>
    <?php endif; ?>
    <?php if (!empty($references)): ?>
    <h2>References</h2>
    <ol>
    <?php foreach ($references as $ref): ?>
        <li><strong><?= sanitize($ref['title']) ?></strong><br><?= sanitize($ref['author']) ?> (<?= $ref['year'] ?>)<br><em><?= sanitize($ref['citation']) ?></em></li>
    <?php endforeach; ?>
    </ol>
    <?php endif; ?>
    <div class="footer"><?= APP_FULL_NAME ?> | Report generated on <?= date('Y-m-d H:i:s') ?></div>
</body></html>
<?php
}

function generateOrganismReport($org, $compounds) {
    ?>
<!DOCTYPE html>
<html><head>
<meta charset="UTF-8">
<title>Report: <?= sanitize($org['scientific_name']) ?></title>
<style>
    body { font-family: 'Segoe UI', Tahoma, sans-serif; margin: 2cm; color: #333; line-height: 1.6; }
    h1 { color: #ffc107; border-bottom: 3px solid #ffc107; padding-bottom: 10px; }
    h2 { color: #495057; margin-top: 30px; }
    table { width: 100%; border-collapse: collapse; margin: 15px 0; }
    th, td { padding: 10px 15px; border: 1px solid #dee2e6; text-align: left; }
    th { background: #f8f9fa; font-weight: 600; }
    .formula { font-family: monospace; color: #0d6efd; }
    .footer { margin-top: 40px; border-top: 1px solid #dee2e6; padding-top: 15px; font-size: 0.85em; color: #6c757d; }
    .structure-img { max-width: 250px; max-height: 200px; display: block; margin: 15px auto; }
    @media print { body { margin: 1.5cm; } .no-print { display: none; } }
</style>
</head><body>
    <div class="no-print" style="margin-bottom:20px;padding:10px;background:#fff3cd;border-radius:8px;">
        <strong>Tip:</strong> Press <kbd>Ctrl+P</kbd> to save as PDF.
        <button onclick="window.print()" style="margin-left:15px;padding:5px 15px;cursor:pointer">🖨️ Print</button>
    </div>
    <h1>Organism Report: <em><?= sanitize($org['scientific_name']) ?></em></h1>
    <p><em>Generated: <?= date('F j, Y \a\t H:i') ?></em></p>
    <?php if (!empty($org['structure_image'])): ?>
    <img src="<?= BASE_URL ?>assets/uploads/organisms/<?= sanitize($org['structure_image']) ?>" alt="Structure" class="structure-img">
    <?php endif; ?>
    <h2>Taxonomic Classification</h2>
    <table>
        <tr><th style="width:30%">Kingdom</th><td><?= sanitize($org['kingdom']) ?></td></tr>
        <tr><th>Phylum</th><td><?= sanitize($org['phylum']) ?></td></tr>
        <tr><th>Class</th><td><?= sanitize($org['class']) ?></td></tr>
        <tr><th>Order</th><td><?= sanitize($org['order_name'] ?? '—') ?></td></tr>
        <tr><th>Family</th><td><?= sanitize($org['family'] ?? '—') ?></td></tr>
        <tr><th>Genus</th><td><em><?= sanitize($org['genus'] ?? '—') ?></em></td></tr>
        <tr><th>Species</th><td><em><?= sanitize($org['species'] ?? '—') ?></em></td></tr>
        <tr><th>Cell Type</th><td><?= ucfirst($org['cell_type'] ?? '—') ?></td></tr>
        <tr><th>Habitat</th><td><?= sanitize($org['habitat'] ?? '—') ?></td></tr>
    </table>
    <?php if ($org['description']): ?>
    <h2>Description</h2>
    <p><?= nl2br(sanitize($org['description'])) ?></p>
    <?php endif; ?>
    <?php if (!empty($compounds)): ?>
    <h2>Derived Compounds (<?= count($compounds) ?>)</h2>
    <table>
        <tr><th>Name</th><th>Formula</th><th>Molecular Weight</th></tr>
        <?php foreach ($compounds as $c): ?>
        <tr><td><?= sanitize($c['name']) ?></td><td class="formula"><?= sanitize($c['formula']) ?></td><td><?= number_format($c['molecular_weight'], 4) ?> g/mol</td></tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>
    <div class="footer"><?= APP_FULL_NAME ?> | Report generated on <?= date('Y-m-d H:i:s') ?></div>
</body></html>
<?php
}

function generateSummaryReport($compoundModel, $orgModel, $refModel, $userModel) {
    $stats = $compoundModel->getStats();
    $compoundCount = $compoundModel->countAll();
    $orgCount = $orgModel->countAll();
    $refCount = $refModel->countAll();
    $userCount = $userModel->countAll();
    $byKingdom = $compoundModel->getByKingdom();
    $compounds = $compoundModel->getAll(0, 999);
    $organisms = $orgModel->getAll(0, 999);
    ?>
<!DOCTYPE html>
<html><head>
<meta charset="UTF-8">
<title>Database Summary Report</title>
<style>
    body { font-family: 'Segoe UI', Tahoma, sans-serif; margin: 2cm; color: #333; line-height: 1.6; }
    h1 { color: #0d6efd; border-bottom: 3px solid #0d6efd; padding-bottom: 10px; }
    h2 { color: #495057; margin-top: 30px; border-bottom: 1px solid #eee; padding-bottom: 5px; }
    table { width: 100%; border-collapse: collapse; margin: 15px 0; font-size: 0.9em; }
    th, td { padding: 8px 12px; border: 1px solid #dee2e6; text-align: left; }
    th { background: #f8f9fa; font-weight: 600; }
    .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin: 20px 0; }
    .stat-box { text-align: center; padding: 15px; border: 1px solid #dee2e6; border-radius: 8px; }
    .stat-box .num { font-size: 2em; font-weight: bold; color: #0d6efd; }
    .stat-box .label { font-size: 0.85em; color: #6c757d; }
    .formula { font-family: monospace; color: #0d6efd; }
    .footer { margin-top: 40px; border-top: 1px solid #dee2e6; padding-top: 15px; font-size: 0.85em; color: #6c757d; }
    @media print { body { margin: 1.5cm; } .no-print { display: none; } }
</style>
</head><body>
    <div class="no-print" style="margin-bottom:20px;padding:10px;background:#cfe2ff;border-radius:8px;">
        <strong>Tip:</strong> Press <kbd>Ctrl+P</kbd> to save as PDF.
        <button onclick="window.print()" style="margin-left:15px;padding:5px 15px;cursor:pointer">🖨️ Print</button>
    </div>
    <h1><?= APP_FULL_NAME ?> — Database Summary Report</h1>
    <p><em>Generated: <?= date('F j, Y \a\t H:i') ?></em></p>
    <div class="stats-grid">
        <div class="stat-box"><div class="num"><?= $compoundCount ?></div><div class="label">Compounds</div></div>
        <div class="stat-box"><div class="num"><?= $orgCount ?></div><div class="label">Organisms</div></div>
        <div class="stat-box"><div class="num"><?= $refCount ?></div><div class="label">References</div></div>
        <div class="stat-box"><div class="num"><?= $userCount ?></div><div class="label">Users</div></div>
    </div>
    <h2>Compound Statistics</h2>
    <table>
        <tr><th style="width:40%">Average Molecular Weight</th><td><?= number_format((float)($stats['avg_mw'] ?? 0), 4) ?> g/mol</td></tr>
        <tr><th>Lightest Compound</th><td><?= number_format((float)($stats['min_mw'] ?? 0), 4) ?> g/mol</td></tr>
        <tr><th>Heaviest Compound</th><td><?= number_format((float)($stats['max_mw'] ?? 0), 4) ?> g/mol</td></tr>
    </table>
    <h2>Compounds by Kingdom</h2>
    <table><tr><th>Kingdom</th><th>Count</th></tr>
    <?php foreach ($byKingdom as $k): ?><tr><td><?= sanitize($k['kingdom']) ?></td><td><?= $k['cnt'] ?></td></tr><?php endforeach; ?>
    </table>
    <h2>All Compounds (<?= $compoundCount ?>)</h2>
    <table><tr><th>Name</th><th>Formula</th><th>MW (g/mol)</th><th>Organism</th></tr>
    <?php foreach ($compounds as $c): ?><tr><td><?= sanitize($c['name']) ?></td><td class="formula"><?= sanitize($c['formula']) ?></td><td><?= number_format($c['molecular_weight'], 2) ?></td><td><em><?= sanitize($c['organism_name'] ?? '—') ?></em></td></tr><?php endforeach; ?>
    </table>
    <h2>All Organisms (<?= $orgCount ?>)</h2>
    <table><tr><th>Scientific Name</th><th>Kingdom</th><th>Family</th><th>Cell Type</th></tr>
    <?php foreach ($organisms as $o): ?><tr><td><em><?= sanitize($o['scientific_name']) ?></em></td><td><?= sanitize($o['kingdom']) ?></td><td><?= sanitize($o['family'] ?? '—') ?></td><td><?= ucfirst($o['cell_type'] ?? '—') ?></td></tr><?php endforeach; ?>
    </table>
    <div class="footer"><?= APP_FULL_NAME ?> | Summary Report | Generated on <?= date('Y-m-d H:i:s') ?></div>
</body></html>
<?php
}
