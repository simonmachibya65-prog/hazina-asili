<?php
/**
 * Global Helper Functions
 */

// ─── Security ────────────────────────────────────────────────────────────────

function sanitize(string $input): string {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function hashPassword(string $password): string {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

function verifyPassword(string $password, string $hash): bool {
    return password_verify($password, $hash);
}

function generateToken(int $length = 32): string {
    return bin2hex(random_bytes($length));
}

function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = generateToken();
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// ─── Session / Auth ───────────────────────────────────────────────────────────

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function currentUser(): ?array {
    return $_SESSION['user'] ?? null;
}

function isAdmin(): bool {
    return isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === ROLE_ADMIN;
}

function isResearcher(): bool {
    return isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === ROLE_RESEARCHER;
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        redirect('views/auth/login.php');
    }
}

function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        redirect('views/errors/403.php');
    }
}

function requireResearcher(): void {
    requireLogin();
    if (!isResearcher() && !isAdmin()) {
        redirect('views/errors/403.php');
    }
}

// ─── Redirect ─────────────────────────────────────────────────────────────────

function redirect(string $path): void {
    $base = rtrim(BASE_URL, '/');
    $path = ltrim($path, '/');
    header("Location: {$base}/{$path}");
    exit;
}

// ─── Flash Messages ───────────────────────────────────────────────────────────

function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function renderFlash(): string {
    $flash = getFlash();
    if (!$flash) return '';
    $type = $flash['type'] === 'error' ? 'danger' : sanitize($flash['type']);
    $msg  = sanitize($flash['message']);
    return "<div class=\"alert alert-{$type} alert-dismissible fade show\" role=\"alert\">
                {$msg}
                <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\"></button>
            </div>";
}

// ─── Pagination ───────────────────────────────────────────────────────────────

function paginate(int $total, int $page, int $perPage = RECORDS_PER_PAGE): array {
    $totalPages = (int) ceil($total / $perPage);
    $page       = max(1, min($page, $totalPages));
    $offset     = ($page - 1) * $perPage;
    return [
        'total'       => $total,
        'per_page'    => $perPage,
        'current'     => $page,
        'total_pages' => $totalPages,
        'offset'      => $offset,
    ];
}

// ─── Misc ─────────────────────────────────────────────────────────────────────

function formatDate(string $date): string {
    return date('M d, Y', strtotime($date));
}

function statusBadge(string $status): string {
    $map = [
        STATUS_PENDING  => 'warning',
        STATUS_APPROVED => 'success',
        STATUS_REJECTED => 'danger',
    ];
    $color = $map[$status] ?? 'secondary';
    return "<span class=\"badge bg-{$color}\">" . ucfirst($status) . "</span>";
}

function activeNav(string $page): string {
    $current = basename($_SERVER['PHP_SELF']);
    return $current === $page ? 'active' : '';
}

// ─── API Response ─────────────────────────────────────────────────────────────

function jsonResponse(int $code, array $data): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// ─── Time Ago ─────────────────────────────────────────────────────────────────

function timeAgo(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return formatDate($datetime);
}

// ─── File Size Format ─────────────────────────────────────────────────────────

function formatFileSize(int $bytes): string {
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 1) . ' ' . $units[$i];
}

// ─── Truncate Text ────────────────────────────────────────────────────────────

function truncate(string $text, int $length = 100, string $suffix = '…'): string {
    if (mb_strlen($text) <= $length) return $text;
    return mb_substr($text, 0, $length) . $suffix;
}

// ─── Generate Breadcrumbs ─────────────────────────────────────────────────────

function breadcrumbs(array $items): string {
    $html = '<nav aria-label="breadcrumb"><ol class="breadcrumb mb-0">';
    $last = count($items) - 1;
    foreach ($items as $i => [$label, $url]) {
        if ($i === $last) {
            $html .= '<li class="breadcrumb-item active" aria-current="page">' . sanitize($label) . '</li>';
        } else {
            $html .= '<li class="breadcrumb-item"><a href="' . sanitize($url) . '">' . sanitize($label) . '</a></li>';
        }
    }
    return $html . '</ol></nav>';
}
