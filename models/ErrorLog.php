<?php
/**
 * Error Log Model — system-level error storage
 */
class ErrorLog {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function log(string $level, string $message, string $file = '', int $line = 0, string $trace = ''): void {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO error_log (level, message, file, line, trace, user_id, url, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
            );
            $stmt->execute([
                $level,
                mb_substr($message, 0, 65535),
                mb_substr($file, 0, 400),
                $line ?: null,
                $trace ? mb_substr($trace, 0, 65535) : null,
                $_SESSION['user_id'] ?? null,
                isset($_SERVER['REQUEST_URI']) ? mb_substr($_SERVER['REQUEST_URI'], 0, 500) : null,
            ]);
        } catch (Throwable $e) {
            error_log("ErrorLog::log failed: " . $e->getMessage());
        }
    }

    public function getAll(int $offset = 0, int $limit = RECORDS_PER_PAGE, string $level = ''): array {
        $sql = "SELECT * FROM error_log WHERE 1=1";
        $params = [];
        if ($level) { $sql .= " AND level = :level"; $params[':level'] = $level; }
        $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countAll(string $level = ''): int {
        $sql = "SELECT COUNT(*) FROM error_log WHERE 1=1";
        $params = [];
        if ($level) { $sql .= " AND level = :level"; $params[':level'] = $level; }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function clearOld(int $days = 30): int {
        $stmt = $this->db->prepare("DELETE FROM error_log WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
        $stmt->execute([$days]);
        return $stmt->rowCount();
    }

    public function getLevelCounts(): array {
        $stmt = $this->db->query("SELECT level, COUNT(*) AS cnt FROM error_log GROUP BY level");
        $result = ['critical' => 0, 'warning' => 0, 'notice' => 0];
        foreach ($stmt->fetchAll() as $row) $result[$row['level']] = (int)$row['cnt'];
        return $result;
    }
}
