<?php
/**
 * Rate Limiter — prevents brute force attacks on login/password reset
 * Uses database-backed tracking for persistence across requests.
 */
class RateLimiter {
    private PDO $db;
    private int $maxAttempts;
    private int $lockoutMinutes;

    public function __construct(int $maxAttempts = 5, int $lockoutMinutes = 15) {
        $this->db = Database::getInstance()->getConnection();
        $this->maxAttempts = $maxAttempts;
        $this->lockoutMinutes = $lockoutMinutes;
        $this->ensureTable();
    }

    private function ensureTable(): void {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS login_attempts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ip_address VARCHAR(45) NOT NULL,
                email VARCHAR(255) DEFAULT NULL,
                attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_ip_time (ip_address, attempted_at),
                INDEX idx_email_time (email, attempted_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    /**
     * Record a failed login attempt.
     */
    public function recordFailedAttempt(string $email): void {
        $ip = $this->getClientIp();
        $stmt = $this->db->prepare(
            "INSERT INTO login_attempts (ip_address, email, attempted_at) VALUES (?, ?, NOW())"
        );
        $stmt->execute([$ip, $email]);
    }

    /**
     * Check if the IP or email is currently locked out.
     */
    public function isLocked(string $email = ''): bool {
        $ip = $this->getClientIp();
        $window = date('Y-m-d H:i:s', strtotime("-{$this->lockoutMinutes} minutes"));

        // Check by IP
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempted_at > ?"
        );
        $stmt->execute([$ip, $window]);
        if ((int)$stmt->fetchColumn() >= $this->maxAttempts) {
            return true;
        }

        // Check by email
        if ($email) {
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) FROM login_attempts WHERE email = ? AND attempted_at > ?"
            );
            $stmt->execute([$email, $window]);
            if ((int)$stmt->fetchColumn() >= $this->maxAttempts) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get remaining attempts before lockout.
     */
    public function remainingAttempts(string $email = ''): int {
        $ip = $this->getClientIp();
        $window = date('Y-m-d H:i:s', strtotime("-{$this->lockoutMinutes} minutes"));

        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempted_at > ?"
        );
        $stmt->execute([$ip, $window]);
        $ipAttempts = (int)$stmt->fetchColumn();

        $emailAttempts = 0;
        if ($email) {
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) FROM login_attempts WHERE email = ? AND attempted_at > ?"
            );
            $stmt->execute([$email, $window]);
            $emailAttempts = (int)$stmt->fetchColumn();
        }

        $used = max($ipAttempts, $emailAttempts);
        return max(0, $this->maxAttempts - $used);
    }

    /**
     * Clear attempts after successful login.
     */
    public function clearAttempts(string $email): void {
        $ip = $this->getClientIp();
        $this->db->prepare("DELETE FROM login_attempts WHERE ip_address = ? OR email = ?")
                 ->execute([$ip, $email]);
    }

    /**
     * Get minutes until lockout expires.
     */
    public function getLockoutRemaining(string $email = ''): int {
        $ip = $this->getClientIp();
        $window = date('Y-m-d H:i:s', strtotime("-{$this->lockoutMinutes} minutes"));

        $stmt = $this->db->prepare(
            "SELECT MAX(attempted_at) FROM login_attempts 
             WHERE (ip_address = ? OR email = ?) AND attempted_at > ?"
        );
        $stmt->execute([$ip, $email, $window]);
        $lastAttempt = $stmt->fetchColumn();

        if (!$lastAttempt) return 0;

        $expiresAt = strtotime($lastAttempt) + ($this->lockoutMinutes * 60);
        $remaining = $expiresAt - time();
        return max(0, (int)ceil($remaining / 60));
    }

    /**
     * Clean up old entries (call periodically).
     */
    public function cleanup(int $olderThanHours = 24): int {
        $stmt = $this->db->prepare(
            "DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL ? HOUR)"
        );
        $stmt->execute([$olderThanHours]);
        return $stmt->rowCount();
    }

    private function getClientIp(): string {
        $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = explode(',', $_SERVER[$header])[0];
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return '0.0.0.0';
    }
}
