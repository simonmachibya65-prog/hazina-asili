<?php
/**
 * Researcher Recommendation Model — with approval comments
 */
class Recommendation {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll(int $offset = 0, int $limit = RECORDS_PER_PAGE, string $status = '', int $userId = 0): array {
        $sql = "SELECT rr.*, u.name AS researcher_name, c.name AS compound_name,
                       rv.name AS reviewer_name
                FROM researcher_recommendations rr
                JOIN users u ON rr.user_id = u.id
                JOIN compounds c ON rr.compound_id = c.id
                LEFT JOIN users rv ON rr.reviewed_by = rv.id
                WHERE 1=1";
        $params = [];
        if ($status) { $sql .= " AND rr.status = :status"; $params[':status'] = $status; }
        if ($userId) { $sql .= " AND rr.user_id = :uid";   $params[':uid']    = $userId; }
        $sql .= " ORDER BY rr.created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countAll(string $status = '', int $userId = 0): int {
        $sql = "SELECT COUNT(*) FROM researcher_recommendations WHERE 1=1";
        $params = [];
        if ($status) { $sql .= " AND status = :status"; $params[':status'] = $status; }
        if ($userId) { $sql .= " AND user_id = :uid";   $params[':uid']    = $userId; }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare(
            "SELECT rr.*, u.name AS researcher_name, c.name AS compound_name,
                    rv.name AS reviewer_name
             FROM researcher_recommendations rr
             JOIN users u ON rr.user_id = u.id
             JOIN compounds c ON rr.compound_id = c.id
             LEFT JOIN users rv ON rr.reviewed_by = rv.id
             WHERE rr.id = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare(
            "INSERT INTO researcher_recommendations (user_id, compound_id, field_to_change, suggested_value, status, created_at)
             VALUES (:user_id, :compound_id, :field, :value, 'pending', NOW())"
        );
        $stmt->execute([
            ':user_id'    => $data['user_id'],
            ':compound_id'=> $data['compound_id'],
            ':field'      => $data['field_to_change'],
            ':value'      => $data['suggested_value'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function updateStatus(int $id, string $status, int $reviewerId, string $comment = ''): bool {
        $stmt = $this->db->prepare(
            "UPDATE researcher_recommendations
             SET status=:status, reviewed_by=:rid, reviewed_at=NOW(), admin_comment=:comment
             WHERE id=:id"
        );
        return $stmt->execute([':status'=>$status, ':rid'=>$reviewerId, ':comment'=>$comment, ':id'=>$id]);
    }

    public function delete(int $id): bool {
        return $this->db->prepare("DELETE FROM researcher_recommendations WHERE id=?")->execute([$id]);
    }

    public function countPending(): int {
        return (int) $this->db->query("SELECT COUNT(*) FROM researcher_recommendations WHERE status='pending'")->fetchColumn();
    }
}
