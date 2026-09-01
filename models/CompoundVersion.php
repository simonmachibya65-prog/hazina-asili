<?php
/**
 * Compound Version Model — Version control & rollback
 */
class CompoundVersion {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function snapshot(array $compound, int $changedBy, string $summary, array $oldValues, array $newValues): void {
        $stmt = $this->db->prepare(
            "INSERT INTO compound_versions
                (compound_id, version, name, formula, molecular_weight, description,
                 organism_id, changed_by, change_summary, old_values, new_values, created_at)
             VALUES (:cid,:ver,:name,:formula,:mw,:desc,:oid,:uid,:summary,:old,:new,NOW())"
        );
        $stmt->execute([
            ':cid'     => $compound['id'],
            ':ver'     => $compound['version'],
            ':name'    => $compound['name'],
            ':formula' => $compound['formula'],
            ':mw'      => $compound['molecular_weight'],
            ':desc'    => $compound['description'],
            ':oid'     => $compound['organism_id'],
            ':uid'     => $changedBy,
            ':summary' => $summary,
            ':old'     => json_encode($oldValues),
            ':new'     => json_encode($newValues),
        ]);
    }

    public function getHistory(int $compoundId): array {
        $stmt = $this->db->prepare(
            "SELECT cv.*, u.name AS changed_by_name
             FROM compound_versions cv LEFT JOIN users u ON cv.changed_by = u.id
             WHERE cv.compound_id = ? ORDER BY cv.version DESC"
        );
        $stmt->execute([$compoundId]);
        return $stmt->fetchAll();
    }

    public function findVersion(int $compoundId, int $version): ?array {
        $stmt = $this->db->prepare(
            "SELECT cv.*, u.name AS changed_by_name
             FROM compound_versions cv LEFT JOIN users u ON cv.changed_by = u.id
             WHERE cv.compound_id = ? AND cv.version = ? LIMIT 1"
        );
        $stmt->execute([$compoundId, $version]);
        return $stmt->fetch() ?: null;
    }

    public function countVersions(int $compoundId): int {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM compound_versions WHERE compound_id = ?");
        $stmt->execute([$compoundId]);
        return (int) $stmt->fetchColumn();
    }
}
