<?php
/**
 * Unit Tests for Helper Functions
 */

use PHPUnit\Framework\TestCase;

class HelpersTest extends TestCase {

    // ── sanitize() ────────────────────────────────────────────

    public function testSanitizeRemovesHtmlTags(): void {
        $this->assertEquals('hello', sanitize('<script>hello</script>'));
    }

    public function testSanitizeTrimsWhitespace(): void {
        $this->assertEquals('test', sanitize('  test  '));
    }

    public function testSanitizeEncodesSpecialChars(): void {
        $result = sanitize('a "quote" & <tag>');
        $this->assertStringNotContainsString('<tag>', $result);
        $this->assertStringContainsString('&amp;', $result);
        $this->assertStringContainsString('&quot;', $result);
    }

    public function testSanitizeHandlesEmptyString(): void {
        $this->assertEquals('', sanitize(''));
    }

    // ── hashPassword() / verifyPassword() ─────────────────────

    public function testHashPasswordReturnsBcrypt(): void {
        $hash = hashPassword('TestPass123');
        $this->assertStringStartsWith('$2y$12$', $hash);
    }

    public function testVerifyPasswordMatchesCorrectPassword(): void {
        $hash = hashPassword('Secret@99');
        $this->assertTrue(verifyPassword('Secret@99', $hash));
    }

    public function testVerifyPasswordRejectsWrongPassword(): void {
        $hash = hashPassword('Secret@99');
        $this->assertFalse(verifyPassword('wrong', $hash));
    }

    // ── generateToken() ───────────────────────────────────────

    public function testGenerateTokenReturnsCorrectLength(): void {
        $token = generateToken(16);
        $this->assertEquals(32, strlen($token)); // hex = 2x bytes
    }

    public function testGenerateTokenDefaultLength(): void {
        $token = generateToken();
        $this->assertEquals(64, strlen($token)); // 32 bytes = 64 hex chars
    }

    public function testGenerateTokenIsUnique(): void {
        $token1 = generateToken();
        $token2 = generateToken();
        $this->assertNotEquals($token1, $token2);
    }

    // ── formatDate() ──────────────────────────────────────────

    public function testFormatDateReturnsFormattedString(): void {
        $result = formatDate('2024-03-15');
        $this->assertEquals('Mar 15, 2024', $result);
    }

    public function testFormatDateHandlesDatetime(): void {
        $result = formatDate('2024-12-01 14:30:00');
        $this->assertEquals('Dec 01, 2024', $result);
    }

    // ── timeAgo() ─────────────────────────────────────────────

    public function testTimeAgoJustNow(): void {
        $result = timeAgo(date('Y-m-d H:i:s'));
        $this->assertEquals('just now', $result);
    }

    public function testTimeAgoMinutes(): void {
        $result = timeAgo(date('Y-m-d H:i:s', strtotime('-5 minutes')));
        $this->assertEquals('5m ago', $result);
    }

    public function testTimeAgoHours(): void {
        $result = timeAgo(date('Y-m-d H:i:s', strtotime('-3 hours')));
        $this->assertEquals('3h ago', $result);
    }

    public function testTimeAgoDays(): void {
        $result = timeAgo(date('Y-m-d H:i:s', strtotime('-2 days')));
        $this->assertEquals('2d ago', $result);
    }

    // ── truncate() ────────────────────────────────────────────

    public function testTruncateShortStringUnchanged(): void {
        $this->assertEquals('hello', truncate('hello', 10));
    }

    public function testTruncateLongString(): void {
        $result = truncate('This is a very long string', 10);
        $this->assertEquals('This is a …', $result);
    }

    public function testTruncateCustomSuffix(): void {
        $result = truncate('Hello World Test', 5, '...');
        $this->assertEquals('Hello...', $result);
    }

    // ── formatFileSize() ──────────────────────────────────────

    public function testFormatFileSizeBytes(): void {
        $this->assertEquals('500 B', formatFileSize(500));
    }

    public function testFormatFileSizeKilobytes(): void {
        $this->assertEquals('1.5 KB', formatFileSize(1536));
    }

    public function testFormatFileSizeMegabytes(): void {
        $this->assertEquals('5 MB', formatFileSize(5 * 1024 * 1024));
    }

    // ── paginate() ────────────────────────────────────────────

    public function testPaginateBasic(): void {
        $result = paginate(100, 1, 10);
        $this->assertEquals(100, $result['total']);
        $this->assertEquals(10, $result['per_page']);
        $this->assertEquals(1, $result['current']);
        $this->assertEquals(10, $result['total_pages']);
        $this->assertEquals(0, $result['offset']);
    }

    public function testPaginateMiddlePage(): void {
        $result = paginate(100, 5, 10);
        $this->assertEquals(5, $result['current']);
        $this->assertEquals(40, $result['offset']);
    }

    public function testPaginateExceedsMax(): void {
        $result = paginate(50, 999, 10);
        $this->assertEquals(5, $result['current']);
    }

    public function testPaginateBelowMin(): void {
        $result = paginate(50, 0, 10);
        $this->assertEquals(1, $result['current']);
    }

    // ── statusBadge() ─────────────────────────────────────────

    public function testStatusBadgePending(): void {
        $result = statusBadge('pending');
        $this->assertStringContainsString('bg-warning', $result);
        $this->assertStringContainsString('Pending', $result);
    }

    public function testStatusBadgeApproved(): void {
        $result = statusBadge('approved');
        $this->assertStringContainsString('bg-success', $result);
    }

    public function testStatusBadgeRejected(): void {
        $result = statusBadge('rejected');
        $this->assertStringContainsString('bg-danger', $result);
    }

    public function testStatusBadgeUnknown(): void {
        $result = statusBadge('unknown');
        $this->assertStringContainsString('bg-secondary', $result);
    }
}
