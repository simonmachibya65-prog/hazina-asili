<?php
/**
 * REST API Controller — provides JSON endpoints for external tool integration.
 * Supports: compounds, organisms, references, stats.
 * Auth: API key via X-API-Key header or session-based.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Compound.php';
require_once __DIR__ . '/../models/Organism.php';
require_once __DIR__ . '/../models/Reference.php';

class ApiController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Handle incoming API request.
     */
    public function handle(): void {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, X-API-Key');

        // Handle CORS preflight
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }

        // Authenticate
        if (!$this->authenticate()) {
            $this->respond(401, ['error' => 'Unauthorized. Provide valid session or X-API-Key header.']);
        }

        $resource = sanitize($_GET['resource'] ?? '');
        $action   = sanitize($_GET['action'] ?? 'list');
        $id       = (int)($_GET['id'] ?? 0);

        try {
            match ($resource) {
                'compounds'  => $this->handleCompounds($action, $id),
                'organisms'  => $this->handleOrganisms($action, $id),
                'references' => $this->handleReferences($action, $id),
                'stats'      => $this->handleStats(),
                default      => $this->respond(404, ['error' => 'Unknown resource. Available: compounds, organisms, references, stats'])
            };
        } catch (Throwable $e) {
            error_log("API Error: " . $e->getMessage());
            $this->respond(500, ['error' => 'Internal server error.']);
        }
    }

    private function authenticate(): bool {
        // Session-based auth
        if (isLoggedIn()) return true;

        // API key auth (stored in users table as api_key column)
        $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
        if ($apiKey) {
            $stmt = $this->db->prepare("SELECT id FROM users WHERE api_key = ? LIMIT 1");
            $stmt->execute([$apiKey]);
            if ($stmt->fetch()) return true;
        }

        return false;
    }

    // ── Compounds ─────────────────────────────────────────────────────────────

    private function handleCompounds(string $action, int $id): void {
        $model = new Compound();

        switch ($action) {
            case 'list':
                $page   = max(1, (int)($_GET['page'] ?? 1));
                $limit  = min(100, max(1, (int)($_GET['limit'] ?? 20)));
                $search = sanitize($_GET['search'] ?? '');
                $field  = sanitize($_GET['field'] ?? '');
                $sortBy = sanitize($_GET['sort'] ?? 'name');
                $sortDir= sanitize($_GET['dir'] ?? 'ASC');
                $offset = ($page - 1) * $limit;

                $total = $model->countAll($search, $field);
                $items = $model->getAll($offset, $limit, $search, $field, $sortBy, $sortDir);

                $this->respond(200, [
                    'data'       => $items,
                    'pagination' => [
                        'total'        => $total,
                        'page'         => $page,
                        'per_page'     => $limit,
                        'total_pages'  => (int)ceil($total / $limit),
                    ]
                ]);
                break;

            case 'get':
                if (!$id) $this->respond(400, ['error' => 'ID is required.']);
                $item = $model->findById($id);
                if (!$item) $this->respond(404, ['error' => 'Compound not found.']);
                $item['references'] = $model->getReferences($id);
                $this->respond(200, ['data' => $item]);
                break;

            case 'search':
                $q = sanitize($_GET['q'] ?? '');
                if (strlen($q) < 2) $this->respond(400, ['error' => 'Query must be at least 2 characters.']);
                $items = $model->getAll(0, 20, $q);
                $this->respond(200, ['data' => $items, 'count' => count($items)]);
                break;

            case 'stats':
                $stats = $model->getStats();
                $byKingdom = $model->getByKingdom();
                $this->respond(200, ['stats' => $stats, 'by_kingdom' => $byKingdom]);
                break;

            default:
                $this->respond(400, ['error' => 'Unknown action. Available: list, get, search, stats']);
        }
    }

    // ── Organisms ─────────────────────────────────────────────────────────────

    private function handleOrganisms(string $action, int $id): void {
        $model = new Organism();

        switch ($action) {
            case 'list':
                $page   = max(1, (int)($_GET['page'] ?? 1));
                $limit  = min(100, max(1, (int)($_GET['limit'] ?? 20)));
                $search = sanitize($_GET['search'] ?? '');
                $offset = ($page - 1) * $limit;

                $total = $model->countAll($search);
                $items = $model->getAll($offset, $limit, $search);

                $this->respond(200, [
                    'data'       => $items,
                    'pagination' => [
                        'total'       => $total,
                        'page'        => $page,
                        'per_page'    => $limit,
                        'total_pages' => (int)ceil($total / $limit),
                    ]
                ]);
                break;

            case 'get':
                if (!$id) $this->respond(400, ['error' => 'ID is required.']);
                $item = $model->findById($id);
                if (!$item) $this->respond(404, ['error' => 'Organism not found.']);
                $item['compounds'] = $model->getCompounds($id);
                $this->respond(200, ['data' => $item]);
                break;

            case 'kingdoms':
                $kingdoms = $model->getDistinctKingdoms();
                $this->respond(200, ['data' => $kingdoms]);
                break;

            default:
                $this->respond(400, ['error' => 'Unknown action. Available: list, get, kingdoms']);
        }
    }

    // ── References ────────────────────────────────────────────────────────────

    private function handleReferences(string $action, int $id): void {
        $model = new Reference();

        switch ($action) {
            case 'list':
                $page   = max(1, (int)($_GET['page'] ?? 1));
                $limit  = min(100, max(1, (int)($_GET['limit'] ?? 20)));
                $search = sanitize($_GET['search'] ?? '');
                $offset = ($page - 1) * $limit;

                $total = $model->countAll($search);
                $items = $model->getAll($offset, $limit, $search);

                $this->respond(200, [
                    'data'       => $items,
                    'pagination' => [
                        'total'       => $total,
                        'page'        => $page,
                        'per_page'    => $limit,
                        'total_pages' => (int)ceil($total / $limit),
                    ]
                ]);
                break;

            case 'get':
                if (!$id) $this->respond(400, ['error' => 'ID is required.']);
                $item = $model->findById($id);
                if (!$item) $this->respond(404, ['error' => 'Reference not found.']);
                $this->respond(200, ['data' => $item]);
                break;

            default:
                $this->respond(400, ['error' => 'Unknown action. Available: list, get']);
        }
    }

    // ── Stats ─────────────────────────────────────────────────────────────────

    private function handleStats(): void {
        $compoundModel = new Compound();
        $orgModel      = new Organism();

        $this->respond(200, [
            'compounds' => [
                'total' => $compoundModel->countAll(),
                'stats' => $compoundModel->getStats(),
                'by_kingdom' => $compoundModel->getByKingdom(),
            ],
            'organisms' => [
                'total' => $orgModel->countAll(),
                'by_kingdom' => $orgModel->getByKingdom(),
            ],
        ]);
    }

    // ── Response Helper ───────────────────────────────────────────────────────

    private function respond(int $code, array $data): void {
        http_response_code($code);
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// ── Route if called directly ──────────────────────────────────────────────────
if (basename($_SERVER['SCRIPT_FILENAME']) === 'ApiController.php') {
    (new ApiController())->handle();
}
