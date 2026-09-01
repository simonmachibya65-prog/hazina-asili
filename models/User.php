<?php
/**
 * User Model — with profile management
 */
class User {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findByEmail(string $email): ?array {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare(
            "SELECT id, name, email, role, bio, institution, avatar, passport_document, created_at, updated_at FROM users WHERE id = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare(
            "INSERT INTO users (name, email, password, role, institution, created_at)
             VALUES (:name, :email, :password, :role, :institution, NOW())"
        );
        $stmt->execute([
            ':name'        => $data['name'],
            ':email'       => $data['email'],
            ':password'    => hashPassword($data['password']),
            ':role'        => $data['role'] ?? ROLE_RESEARCHER,
            ':institution' => $data['institution'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $fields = [];
        $params = [':id' => $id];
        foreach (['name','email','role','bio','institution','avatar'] as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }
        if (empty($fields)) return false;
        return $this->db->prepare("UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id")->execute($params);
    }

    public function updateAvatar(int $id, string $filename): bool {
        return $this->db->prepare("UPDATE users SET avatar = ? WHERE id = ?")->execute([$filename, $id]);
    }

    public function removeAvatar(int $id): bool {
        return $this->db->prepare("UPDATE users SET avatar = NULL WHERE id = ?")->execute([$id]);
    }

    public function updatePassport(int $id, string $filename): bool {
        return $this->db->prepare("UPDATE users SET passport_document = ? WHERE id = ?")->execute([$filename, $id]);
    }

    public function removePassport(int $id): bool {
        return $this->db->prepare("UPDATE users SET passport_document = NULL WHERE id = ?")->execute([$id]);
    }

    public function updatePassword(int $id, string $newPassword): bool {
        $stmt = $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");
        return $stmt->execute([hashPassword($newPassword), $id]);
    }

    public function verifyCurrentPassword(int $id, string $password): bool {
        $stmt = $this->db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row && verifyPassword($password, $row['password']);
    }

    public function delete(int $id): bool {
        return $this->db->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
    }

    public function getAll(int $offset = 0, int $limit = RECORDS_PER_PAGE, string $search = ''): array {
        $sql = "SELECT id, name, email, role, institution, created_at FROM users";
        $params = [];
        if ($search) { $sql .= " WHERE name LIKE :s OR email LIKE :s"; $params[':s'] = "%{$search}%"; }
        $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countAll(string $search = ''): int {
        $sql = "SELECT COUNT(*) FROM users";
        $params = [];
        if ($search) { $sql .= " WHERE name LIKE :s OR email LIKE :s"; $params[':s'] = "%{$search}%"; }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function emailExists(string $email, int $excludeId = 0): bool {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $excludeId]);
        return (bool) $stmt->fetch();
    }

    public function saveResetToken(int $id, string $token, string $expiry): bool {
        return $this->db->prepare("UPDATE users SET reset_token=?, reset_expires=? WHERE id=?")->execute([$token, $expiry, $id]);
    }

    public function findByResetToken(string $token): ?array {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE reset_token = ? AND reset_expires > NOW() LIMIT 1");
        $stmt->execute([$token]);
        return $stmt->fetch() ?: null;
    }

    public function clearResetToken(int $id): bool {
        return $this->db->prepare("UPDATE users SET reset_token=NULL, reset_expires=NULL WHERE id=?")->execute([$id]);
    }

    public function getContributionStats(int $userId): array {
        $ins = $this->db->prepare("SELECT COUNT(*) AS total, SUM(status='approved') AS approved, SUM(status='pending') AS pending, SUM(status='rejected') AS rejected FROM researcher_insights WHERE user_id=?");
        $ins->execute([$userId]);
        $rec = $this->db->prepare("SELECT COUNT(*) AS total, SUM(status='approved') AS approved, SUM(status='pending') AS pending, SUM(status='rejected') AS rejected FROM researcher_recommendations WHERE user_id=?");
        $rec->execute([$userId]);
        return ['insights' => $ins->fetch(), 'recommendations' => $rec->fetch()];
    }
}
