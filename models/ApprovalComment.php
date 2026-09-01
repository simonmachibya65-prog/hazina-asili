<?php
/**
 * Approval Comment Model — Workflow history with comments
 */
class ApprovalComment {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function add(string $entityType, int $entityId, int $userId, string $comment, string $action = 'comment'): void {
        $stmt = $this->db->prepare(
            "INSERT INTO approval_comments (entity_type, entity_id, user_id, comment, action, created_at)
             VALUES (?, ?, ?, ?, ?, NOW())"
        );
        $stmt->execute([$entityType, $entityId, $userId, $comment, $action]);
    }

    public function getHistory(string $entityType, int $entityId): array {
        $stmt = $this->db->prepare(
            "SELECT ac.*, u.name AS user_name, u.role
             FROM approval_comments ac
             JOIN users u ON ac.user_id = u.id
             WHERE ac.entity_type = ? AND ac.entity_id = ?
             ORDER BY ac.created_at ASC"
        );
        $stmt->execute([$entityType, $entityId]);
        return $stmt->fetchAll();
    }
}
