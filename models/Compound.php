<?php
/**
 * Compound Model — with version control, advanced search, duplicate detection
 */
class Compound {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll(
        int    $offset  = 0,
        int    $limit   = RECORDS_PER_PAGE,
        string $search  = '',
        string $field   = '',
        string $sortBy  = 'name',
        string $sortDir = 'ASC',
        int    $orgId   = 0,
        float  $mwMin   = 0,
        float  $mwMax   = 0
    ): array {
        $allowed = ['name','formula','molecular_weight','created_at'];
        $sortBy  = in_array($sortBy, $allowed) ? $sortBy : 'name';
        $sortDir = strtoupper($sortDir) === 'DESC' ? 'DESC' : 'ASC';

        $sql = "SELECT c.*, o.scientific_name AS organism_name
                FROM compounds c LEFT JOIN organisms o ON c.organism_id = o.id WHERE 1=1";
        $searchParams = [];

        if ($search) {
            $term = "%{$search}%";
            if ($field === 'formula') {
                $sql .= " AND c.formula LIKE ?";
                $searchParams[] = $term;
            } elseif ($field === 'taxonomy') {
                $sql .= " AND (o.kingdom LIKE ? OR o.phylum LIKE ? OR o.class LIKE ? OR o.scientific_name LIKE ?)";
                $searchParams = array_merge($searchParams, [$term, $term, $term, $term]);
            } else {
                $sql .= " AND (c.name LIKE ? OR c.formula LIKE ? OR c.description LIKE ?)";
                $searchParams = array_merge($searchParams, [$term, $term, $term]);
            }
        }
        if ($orgId)       { $sql .= " AND c.organism_id = ?";          $searchParams[] = $orgId; }
        if ($mwMin > 0)   { $sql .= " AND c.molecular_weight >= ?";    $searchParams[] = $mwMin; }
        if ($mwMax > 0)   { $sql .= " AND c.molecular_weight <= ?";    $searchParams[] = $mwMax; }

        $sql .= " ORDER BY c.{$sortBy} {$sortDir} LIMIT ? OFFSET ?";
        $stmt = $this->db->prepare($sql);
        $idx = 1;
        foreach ($searchParams as $v) {
            $stmt->bindValue($idx++, $v);
        }
        $stmt->bindValue($idx++, $limit, PDO::PARAM_INT);
        $stmt->bindValue($idx, $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countAll(string $search = '', string $field = '', int $orgId = 0, float $mwMin = 0, float $mwMax = 0): int {
        $sql = "SELECT COUNT(*) FROM compounds c LEFT JOIN organisms o ON c.organism_id = o.id WHERE 1=1";
        $params = [];
        if ($search) {
            $term = "%{$search}%";
            if ($field === 'formula') {
                $sql .= " AND c.formula LIKE ?";
                $params[] = $term;
            } elseif ($field === 'taxonomy') {
                $sql .= " AND (o.kingdom LIKE ? OR o.phylum LIKE ? OR o.class LIKE ? OR o.scientific_name LIKE ?)";
                $params = array_merge($params, [$term, $term, $term, $term]);
            } else {
                $sql .= " AND (c.name LIKE ? OR c.formula LIKE ? OR c.description LIKE ?)";
                $params = array_merge($params, [$term, $term, $term]);
            }
        }
        if ($orgId)     { $sql .= " AND c.organism_id = ?";         $params[] = $orgId; }
        if ($mwMin > 0) { $sql .= " AND c.molecular_weight >= ?";   $params[] = $mwMin; }
        if ($mwMax > 0) { $sql .= " AND c.molecular_weight <= ?";   $params[] = $mwMax; }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare(
            "SELECT c.*, o.scientific_name AS organism_name, o.kingdom, o.phylum, o.class,
                    o.order_name AS organism_order, o.family AS organism_family,
                    o.genus AS organism_genus, o.structure_image AS organism_image
             FROM compounds c LEFT JOIN organisms o ON c.organism_id = o.id
             WHERE c.id = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare(
            "INSERT INTO compounds (name, formula, molecular_weight, description, structure_image, organism_id, created_by, version, created_at)
             VALUES (:name, :formula, :mw, :desc, :img, :oid, :uid, 1, NOW())"
        );
        $stmt->execute([
            ':name'   => $data['name'],
            ':formula'=> $data['formula'],
            ':mw'     => $data['molecular_weight'],
            ':desc'   => $data['description'],
            ':img'    => $data['structure_image'] ?? null,
            ':oid'    => $data['organism_id'] ?: null,
            ':uid'    => $data['created_by']  ?: null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $stmt = $this->db->prepare(
            "UPDATE compounds SET name=:name, formula=:formula, molecular_weight=:mw,
             description=:desc, structure_image=:img, organism_id=:oid, version=version+1 WHERE id=:id"
        );
        return $stmt->execute([
            ':name'   => $data['name'],
            ':formula'=> $data['formula'],
            ':mw'     => $data['molecular_weight'],
            ':desc'   => $data['description'],
            ':img'    => $data['structure_image'] ?? null,
            ':oid'    => $data['organism_id'] ?: null,
            ':id'     => $id,
        ]);
    }

    public function rollback(int $id, array $versionData): bool {
        $stmt = $this->db->prepare(
            "UPDATE compounds SET name=:name, formula=:formula, molecular_weight=:mw,
             description=:desc, organism_id=:oid, version=version+1 WHERE id=:id"
        );
        return $stmt->execute([
            ':name'   => $versionData['name'],
            ':formula'=> $versionData['formula'],
            ':mw'     => $versionData['molecular_weight'],
            ':desc'   => $versionData['description'],
            ':oid'    => $versionData['organism_id'] ?: null,
            ':id'     => $id,
        ]);
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM compounds WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function updateStructureImage(int $id, ?string $filename): bool {
        $stmt = $this->db->prepare("UPDATE compounds SET structure_image = ? WHERE id = ?");
        return $stmt->execute([$filename, $id]);
    }

    public function getReferences(int $compoundId): array {
        $stmt = $this->db->prepare(
            "SELECT r.* FROM `references` r
             JOIN compound_reference cr ON r.id = cr.reference_id
             WHERE cr.compound_id = ?"
        );
        $stmt->execute([$compoundId]);
        return $stmt->fetchAll();
    }

    public function syncReferences(int $compoundId, array $referenceIds): void {
        $this->db->prepare("DELETE FROM compound_reference WHERE compound_id = ?")->execute([$compoundId]);
        if (empty($referenceIds)) return;
        $stmt = $this->db->prepare("INSERT IGNORE INTO compound_reference (compound_id, reference_id) VALUES (?, ?)");
        foreach ($referenceIds as $refId) $stmt->execute([$compoundId, (int)$refId]);
    }

    /** Validate molecular formula (e.g. C15H10O7, C6H10OS2) */
    public static function validateFormula(string $formula): bool {
        return (bool) preg_match('/^([A-Z][a-z]?\d*)+$/', $formula);
    }

    /** Rough MW estimate from formula for plausibility check */
    public static function estimateMolecularWeight(string $formula): float {
        $weights = ['C'=>12.011,'H'=>1.008,'O'=>15.999,'N'=>14.007,'S'=>32.06,'P'=>30.974,'F'=>18.998,'Cl'=>35.45,'Br'=>79.904,'I'=>126.904];
        preg_match_all('/([A-Z][a-z]?)(\d*)/', $formula, $matches, PREG_SET_ORDER);
        $mw = 0.0;
        foreach ($matches as $m) {
            $el  = $m[1];
            $cnt = $m[2] !== '' ? (int)$m[2] : 1;
            $mw += ($weights[$el] ?? 0) * $cnt;
        }
        return round($mw, 4);
    }

    /** Detect potential duplicates by name or formula */
    public function findDuplicates(string $name, string $formula, int $excludeId = 0): array {
        $stmt = $this->db->prepare(
            "SELECT id, name, formula FROM compounds
             WHERE (name = :name OR formula = :formula) AND id != :eid"
        );
        $stmt->execute([':name' => $name, ':formula' => $formula, ':eid' => $excludeId]);
        return $stmt->fetchAll();
    }

    public function getStats(): array {
        $stmt = $this->db->query(
            "SELECT COUNT(*) AS total,
                    AVG(molecular_weight) AS avg_mw,
                    MIN(molecular_weight) AS min_mw,
                    MAX(molecular_weight) AS max_mw
             FROM compounds"
        );
        return $stmt->fetch();
    }

    public function getByKingdom(): array {
        $stmt = $this->db->query(
            "SELECT COALESCE(o.kingdom, 'Unknown') AS kingdom, COUNT(c.id) AS cnt
             FROM compounds c LEFT JOIN organisms o ON c.organism_id = o.id
             GROUP BY kingdom ORDER BY cnt DESC"
        );
        return $stmt->fetchAll();
    }

    public function getMonthlyTrend(int $months = 12): array {
        $stmt = $this->db->prepare(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, COUNT(*) AS cnt
             FROM compounds WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? MONTH)
             GROUP BY month ORDER BY month ASC"
        );
        $stmt->execute([$months]);
        return $stmt->fetchAll();
    }
}
