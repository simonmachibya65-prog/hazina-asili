<?php
/**
 * Organism Model — Full Taxonomy Structure Support
 */
class Organism {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll(int $offset = 0, int $limit = RECORDS_PER_PAGE, string $search = ''): array {
        $sql = "SELECT * FROM organisms WHERE 1=1";
        $params = [];
        if ($search) {
            $sql .= " AND (kingdom LIKE ? OR phylum LIKE ? OR class LIKE ? 
                      OR order_name LIKE ? OR family LIKE ? OR genus LIKE ? 
                      OR species LIKE ? OR scientific_name LIKE ? OR habitat LIKE ?)";
            $term = "%{$search}%";
            $params = [$term, $term, $term, $term, $term, $term, $term, $term, $term];
        }
        $sql .= " ORDER BY scientific_name ASC LIMIT ? OFFSET ?";
        $stmt = $this->db->prepare($sql);
        $idx = 1;
        foreach ($params as $v) {
            $stmt->bindValue($idx++, $v);
        }
        $stmt->bindValue($idx++, $limit, PDO::PARAM_INT);
        $stmt->bindValue($idx, $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countAll(string $search = ''): int {
        $sql = "SELECT COUNT(*) FROM organisms WHERE 1=1";
        $params = [];
        if ($search) {
            $sql .= " AND (kingdom LIKE ? OR phylum LIKE ? OR class LIKE ? 
                      OR order_name LIKE ? OR family LIKE ? OR genus LIKE ? 
                      OR species LIKE ? OR scientific_name LIKE ? OR habitat LIKE ?)";
            $term = "%{$search}%";
            $params = [$term, $term, $term, $term, $term, $term, $term, $term, $term];
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM organisms WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findByScientificName(string $name, int $excludeId = 0): ?array {
        $sql = "SELECT * FROM organisms WHERE scientific_name = ?";
        $params = [$name];
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        $stmt = $this->db->prepare($sql . " LIMIT 1");
        $stmt->execute($params);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare(
            "INSERT INTO organisms (kingdom, phylum, class, order_name, family, genus, species,
             scientific_name, cell_type, habitat, description, structure_image)
             VALUES (:kingdom, :phylum, :class, :order_name, :family, :genus, :species,
             :scientific_name, :cell_type, :habitat, :description, :structure_image)"
        );
        $stmt->execute([
            ':kingdom'         => $data['kingdom'],
            ':phylum'          => $data['phylum'],
            ':class'           => $data['class'],
            ':order_name'      => $data['order_name'] ?? null,
            ':family'          => $data['family'] ?? null,
            ':genus'           => $data['genus'] ?? null,
            ':species'         => $data['species'] ?? null,
            ':scientific_name' => $data['scientific_name'],
            ':cell_type'       => $data['cell_type'] ?? null,
            ':habitat'         => $data['habitat'] ?? null,
            ':description'     => $data['description'] ?? null,
            ':structure_image' => $data['structure_image'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $stmt = $this->db->prepare(
            "UPDATE organisms SET kingdom=:kingdom, phylum=:phylum, class=:class,
             order_name=:order_name, family=:family, genus=:genus, species=:species,
             scientific_name=:scientific_name, cell_type=:cell_type, habitat=:habitat,
             description=:description, structure_image=:structure_image
             WHERE id=:id"
        );
        return $stmt->execute([
            ':kingdom'         => $data['kingdom'],
            ':phylum'          => $data['phylum'],
            ':class'           => $data['class'],
            ':order_name'      => $data['order_name'] ?? null,
            ':family'          => $data['family'] ?? null,
            ':genus'           => $data['genus'] ?? null,
            ':species'         => $data['species'] ?? null,
            ':scientific_name' => $data['scientific_name'],
            ':cell_type'       => $data['cell_type'] ?? null,
            ':habitat'         => $data['habitat'] ?? null,
            ':description'     => $data['description'] ?? null,
            ':structure_image' => $data['structure_image'] ?? null,
            ':id'              => $id,
        ]);
    }

    public function updateImage(int $id, ?string $filename): bool {
        $stmt = $this->db->prepare("UPDATE organisms SET structure_image = ? WHERE id = ?");
        return $stmt->execute([$filename, $id]);
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM organisms WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getAllSimple(): array {
        return $this->db->query("SELECT id, scientific_name FROM organisms ORDER BY scientific_name")->fetchAll();
    }

    /**
     * Get compounds linked to this organism (with structure image)
     */
    public function getCompounds(int $organismId): array {
        $stmt = $this->db->prepare(
            "SELECT id, name, formula, molecular_weight, structure_image FROM compounds WHERE organism_id = ? ORDER BY name"
        );
        $stmt->execute([$organismId]);
        return $stmt->fetchAll();
    }

    /**
     * Count organisms grouped by kingdom (for dashboard chart)
     */
    public function getByKingdom(): array {
        return $this->db->query(
            "SELECT kingdom, COUNT(*) as cnt FROM organisms GROUP BY kingdom ORDER BY cnt DESC"
        )->fetchAll();
    }

    /**
     * Get all organisms filtered by kingdom
     */
    public function findByKingdom(string $kingdom): array {
        $stmt = $this->db->prepare("SELECT * FROM organisms WHERE kingdom = ? ORDER BY scientific_name");
        $stmt->execute([$kingdom]);
        return $stmt->fetchAll();
    }

    /**
     * Get distinct kingdoms for filter dropdown
     */
    public function getDistinctKingdoms(): array {
        return $this->db->query("SELECT DISTINCT kingdom FROM organisms ORDER BY kingdom")->fetchAll(PDO::FETCH_COLUMN);
    }
}
