<?php
/**
 * Activity Log Model — Full Audit Trail with old/new values
 */
class ActivityLog {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function log(
        int    $userId,
        string $action,
        string $details    = '',
        string $entityType = '',
        int    $entityId   = 0,
        array  $oldValues  = [],
        array  $newValues  = []
    ): void {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO activity_log
                    (user_id, action, entity_type, entity_id, old_values, new_values, details, ip_address, user_agent, created_at)
                 VALUES (:uid, :action, :etype, :eid, :old, :new, :details, :ip, :ua, NOW())"
            );
            $stmt->execute([
                ':uid'     => $userId ?: null,
                ':action'  => $action,
                ':etype'   => $entityType ?: null,
                ':eid'     => $entityId   ?: null,
                ':old'     => $oldValues  ? json_encode($oldValues)  : null,
                ':new'     => $newValues  ? json_encode($newValues)  : null,
                ':details' => $details    ?: null,
                ':ip'      => $_SERVER['REMOTE_ADDR'] ?? null,
                ':ua'      => isset($_SERVER['HTTP_USER_AGENT'])
                              ? mb_substr($_SERVER['HTTP_USER_AGENT'], 0, 300) : null,
            ]);
        } catch (Exception $e) {
            error_log("ActivityLog::log failed: " . $e->getMessage());
        }
    }

    public function getAll(int $offset = 0, int $limit = RECORDS_PER_PAGE, string $action = '', int $userId = 0): array {
        $sql = "SELECT al.*, u.name AS user_name, u.role
                FROM activity_log al LEFT JOIN users u ON al.user_id = u.id WHERE 1=1";
        $params = [];
        if ($action) { $sql .= " AND al.action LIKE :action"; $params[':action'] = "%{$action}%"; }
        if ($userId) { $sql .= " AND al.user_id = :uid";      $params[':uid']    = $userId; }
        $sql .= " ORDER BY al.created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countAll(string $action = '', int $userId = 0): int {
        $sql = "SELECT COUNT(*) FROM activity_log WHERE 1=1";
        $params = [];
        if ($action) { $sql .= " AND action LIKE :action"; $params[':action'] = "%{$action}%"; }
        if ($userId) { $sql .= " AND user_id = :uid";      $params[':uid']    = $userId; }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function getEntityHistory(string $entityType, int $entityId): array {
        $stmt = $this->db->prepare(
            "SELECT al.*, u.name AS user_name FROM activity_log al
             LEFT JOIN users u ON al.user_id = u.id
             WHERE al.entity_type = ? AND al.entity_id = ?
             ORDER BY al.created_at DESC"
        );
        $stmt->execute([$entityType, $entityId]);
        return $stmt->fetchAll();
    }

    public function getUserActivity(int $userId, int $limit = 20): array {
        $stmt = $this->db->prepare(
            "SELECT * FROM activity_log WHERE user_id = ? ORDER BY created_at DESC LIMIT ?"
        );
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit,  PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getStats(): array {
        $stmt = $this->db->query(
            "SELECT COUNT(*) AS total,
                    SUM(DATE(created_at) = CURDATE()) AS today,
                    SUM(created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) AS this_week,
                    COUNT(DISTINCT user_id) AS unique_users
             FROM activity_log"
        );
        return $stmt->fetch();
    }

    public function getActionFrequency(int $days = 30): array {
        $stmt = $this->db->prepare(
            "SELECT action, COUNT(*) AS cnt FROM activity_log
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
             GROUP BY action ORDER BY cnt DESC LIMIT 10"
        );
        $stmt->execute([$days]);
        return $stmt->fetchAll();
    }

    public function getDailyActivity(int $days = 14): array {
        $stmt = $this->db->prepare(
            "SELECT DATE(created_at) AS day, COUNT(*) AS cnt FROM activity_log
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
             GROUP BY DATE(created_at) ORDER BY day ASC"
        );
        $stmt->execute([$days]);
        return $stmt->fetchAll();
    }
}
