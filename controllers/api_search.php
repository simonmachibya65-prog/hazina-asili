<?php
/**
 * API: Smart Search Autocomplete
 * Returns JSON results across compounds, organisms, and references
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Compound.php';
require_once __DIR__ . '/../models/Organism.php';
require_once __DIR__ . '/../models/Reference.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$q = sanitize($_GET['q'] ?? '');
if (strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

$results = [];
$db = Database::getInstance()->getConnection();
$term = "%{$q}%";
$role = $_SESSION['user']['role'] ?? 'researcher';
$basePrefix = $role === 'admin' ? 'views/admin' : 'views/researcher';

// Search compounds (limit 5)
$stmt = $db->prepare(
    "SELECT c.id, c.name, c.formula, o.scientific_name AS organism 
     FROM compounds c LEFT JOIN organisms o ON c.organism_id = o.id
     WHERE c.name LIKE ? OR c.formula LIKE ?
     ORDER BY c.name LIMIT 5"
);
$stmt->execute([$term, $term]);
foreach ($stmt->fetchAll() as $r) {
    $results[] = [
        'name'   => $r['name'],
        'type'   => 'Compound',
        'color'  => 'success',
        'detail' => $r['formula'] . ($r['organism'] ? ' · ' . $r['organism'] : ''),
        'url'    => BASE_URL . "views/admin/compounds/view.php?id={$r['id']}",
    ];
}

// Search organisms (limit 5)
$stmt = $db->prepare(
    "SELECT id, scientific_name, kingdom, family 
     FROM organisms 
     WHERE scientific_name LIKE ? OR kingdom LIKE ? OR family LIKE ? OR genus LIKE ?
     ORDER BY scientific_name LIMIT 5"
);
$stmt->execute([$term, $term, $term, $term]);
foreach ($stmt->fetchAll() as $r) {
    $results[] = [
        'name'   => $r['scientific_name'],
        'type'   => 'Organism',
        'color'  => 'warning',
        'detail' => $r['kingdom'] . ($r['family'] ? ' · ' . $r['family'] : ''),
        'url'    => BASE_URL . "{$basePrefix}/organisms/view.php?id={$r['id']}",
    ];
}

// Search references (limit 3)
$stmt = $db->prepare(
    "SELECT id, title, author, year FROM `references`
     WHERE title LIKE ? OR author LIKE ?
     ORDER BY title LIMIT 3"
);
$stmt->execute([$term, $term]);
foreach ($stmt->fetchAll() as $r) {
    $results[] = [
        'name'   => $r['title'],
        'type'   => 'Reference',
        'color'  => 'info',
        'detail' => $r['author'] . ' (' . $r['year'] . ')',
        'url'    => BASE_URL . "views/admin/references/index.php",
    ];
}

echo json_encode(array_slice($results, 0, 10));
