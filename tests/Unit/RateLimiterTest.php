<?php
/**
 * Unit Tests for RateLimiter
 * Note: Requires database connection (integration-style).
 */

use PHPUnit\Framework\TestCase;

class RateLimiterTest extends TestCase {
    private RateLimiter $limiter;

    protected function setUp(): void {
        $this->limiter = new RateLimiter(3, 1); // 3 attempts, 1 min lockout for fast testing
        // Clean test data
        $db = Database::getInstance()->getConnection();
        $db->exec("DELETE FROM login_attempts WHERE email LIKE '%@test.limiter%'");
    }

    protected function tearDown(): void {
        $db = Database::getInstance()->getConnection();
        $db->exec("DELETE FROM login_attempts WHERE email LIKE '%@test.limiter%'");
    }

    public function testNotLockedInitially(): void {
        $this->assertFalse($this->limiter->isLocked('fresh@test.limiter'));
    }

    public function testRemainingAttemptsInitially(): void {
        $remaining = $this->limiter->remainingAttempts('new@test.limiter');
        $this->assertEquals(3, $remaining);
    }

    public function testRecordReducesRemaining(): void {
        $email = 'attempt@test.limiter';
        $this->limiter->recordFailedAttempt($email);
        $remaining = $this->limiter->remainingAttempts($email);
        $this->assertEquals(2, $remaining);
    }

    public function testLockedAfterMaxAttempts(): void {
        $email = 'locked@test.limiter';
        $this->limiter->recordFailedAttempt($email);
        $this->limiter->recordFailedAttempt($email);
        $this->limiter->recordFailedAttempt($email);
        $this->assertTrue($this->limiter->isLocked($email));
    }

    public function testClearAttemptsUnlocks(): void {
        $email = 'clear@test.limiter';
        $this->limiter->recordFailedAttempt($email);
        $this->limiter->recordFailedAttempt($email);
        $this->limiter->recordFailedAttempt($email);
        $this->assertTrue($this->limiter->isLocked($email));

        $this->limiter->clearAttempts($email);
        $this->assertFalse($this->limiter->isLocked($email));
    }

    public function testRemainingIsZeroWhenLocked(): void {
        $email = 'zero@test.limiter';
        $this->limiter->recordFailedAttempt($email);
        $this->limiter->recordFailedAttempt($email);
        $this->limiter->recordFailedAttempt($email);
        $this->assertEquals(0, $this->limiter->remainingAttempts($email));
    }
}
