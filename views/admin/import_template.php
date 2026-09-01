<?php
/**
 * CSV Template Download
 */
require_once __DIR__ . '/../../config/config.php';
requireAdmin();

$type = sanitize($_GET['type'] ?? '');

if ($type === 'compounds') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="compounds_import_template.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['name', 'formula', 'molecular_weight', 'description', 'organism']);
    fputcsv($out, ['Quercetin', 'C15H10O7', '302.2357', 'A plant flavonoid with antioxidant properties.', 'Camellia sinensis']);
    fputcsv($out, ['Caffeine', 'C8H10N4O2', '194.19', 'A purine alkaloid found in coffee and tea.', 'Camellia sinensis']);
    fclose($out);
    exit;
}

if ($type === 'organisms') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="organisms_import_template.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['scientific_name', 'kingdom', 'phylum', 'class', 'order_name', 'family', 'genus', 'species', 'cell_type', 'habitat', 'description']);
    fputcsv($out, ['Camellia sinensis', 'Plantae', 'Tracheophyta', 'Magnoliopsida', 'Ericales', 'Theaceae', 'Camellia', 'sinensis', 'eukaryotic', 'Tropical Asia', 'Tea plant']);
    fputcsv($out, ['Streptomyces griseus', 'Bacteria', 'Actinobacteria', 'Actinomycetia', 'Streptomycetales', 'Streptomycetaceae', 'Streptomyces', 'griseus', 'prokaryotic', 'Soil worldwide', 'Antibiotic producer']);
    fclose($out);
    exit;
}

redirect('views/admin/import.php');
