<?php
/**
 * AI Assistant API — handles AJAX requests from the frontend chat widget.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Compound.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated.']);
    exit;
}

$ai = new AIAssistant();

if (!$ai->isEnabled()) {
    echo json_encode(['success' => false, 'error' => 'AI assistant is not configured. Add AI_API_KEY to .env.']);
    exit;
}

// Per-user rate limiting: 1 request per 5 seconds
$uid = $_SESSION['user_id'];
$lastKey = "ai_last_request_{$uid}";
if (isset($_SESSION[$lastKey]) && (time() - $_SESSION[$lastKey]) < 5) {
    echo json_encode(['success' => false, 'error' => 'Please wait a few seconds between requests.']);
    exit;
}
$_SESSION[$lastKey] = time();

$action = sanitize($_POST['ai_action'] ?? $_GET['ai_action'] ?? '');
$result = [];

switch ($action) {
    case 'ask':
        $question = trim($_POST['question'] ?? '');
        if (strlen($question) < 3) {
            $result = ['success' => false, 'error' => 'Question is too short.'];
            break;
        }
        $context = $_POST['context'] ?? '';
        $result = $ai->ask($question, $context);
        break;

    case 'analyze':
        $compoundId = (int)($_POST['compound_id'] ?? 0);
        if (!$compoundId) {
            $result = ['success' => false, 'error' => 'Compound ID required.'];
            break;
        }
        $compound = (new Compound())->findById($compoundId);
        if (!$compound) {
            $result = ['success' => false, 'error' => 'Compound not found.'];
            break;
        }
        $result = $ai->analyzeCompound($compound);
        break;

    case 'suggest_research':
        $compoundId = (int)($_POST['compound_id'] ?? 0);
        if (!$compoundId) {
            $result = ['success' => false, 'error' => 'Compound ID required.'];
            break;
        }
        $compound = (new Compound())->findById($compoundId);
        if (!$compound) {
            $result = ['success' => false, 'error' => 'Compound not found.'];
            break;
        }
        $result = $ai->suggestResearch($compound);
        break;

    case 'predict':
        $name    = sanitize($_POST['name'] ?? '');
        $formula = sanitize($_POST['formula'] ?? '');
        $mw      = (float)($_POST['molecular_weight'] ?? 0);
        if (!$name || !$formula || $mw <= 0) {
            $result = ['success' => false, 'error' => 'Name, formula, and MW are required.'];
            break;
        }
        $result = $ai->predictProperties($name, $formula, $mw);
        break;

    case 'compare':
        $id1 = (int)($_POST['compound_id_1'] ?? 0);
        $id2 = (int)($_POST['compound_id_2'] ?? 0);
        if (!$id1 || !$id2) {
            $result = ['success' => false, 'error' => 'Two compound IDs required.'];
            break;
        }
        $model = new Compound();
        $c1 = $model->findById($id1);
        $c2 = $model->findById($id2);
        if (!$c1 || !$c2) {
            $result = ['success' => false, 'error' => 'One or both compounds not found.'];
            break;
        }
        $result = $ai->compareCompounds($c1, $c2);
        break;

    default:
        $result = ['success' => false, 'error' => 'Unknown action. Available: ask, analyze, suggest_research, predict, compare'];
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
