<?php
/**
 * Mailer Helper — wraps PHPMailer for password reset emails and notifications.
 * Falls back gracefully to demo mode when MAIL_ENABLED=false or PHPMailer isn't installed.
 */
class Mailer {
    private bool $enabled;

    public function __construct() {
        $this->enabled = defined('MAIL_ENABLED') && MAIL_ENABLED;
    }

    /**
     * Send a password reset email.
     * Returns true on success, or the reset URL string in demo mode.
     */
    public function sendPasswordReset(string $email, string $name, string $token): bool|string {
        $resetUrl = BASE_URL . "views/auth/reset_password.php?token={$token}";

        if (!$this->enabled) {
            // Demo mode — store token for UI display
            $_SESSION['demo_reset_token'] = $token;
            $_SESSION['demo_reset_url'] = $resetUrl;
            return $resetUrl;
        }

        $subject = APP_NAME . ' — Password Reset Request';
        $body = $this->buildResetEmailHtml($name, $resetUrl);
        $textBody = "Hi {$name},\n\nYou requested a password reset.\nClick here to reset: {$resetUrl}\n\nThis link expires in 1 hour.\n\nIf you didn't request this, ignore this email.";

        return $this->send($email, $name, $subject, $body, $textBody);
    }

    /**
     * Send a notification email (insight approved/rejected, etc.)
     */
    public function sendNotification(string $email, string $name, string $subject, string $message, string $actionUrl = ''): bool {
        if (!$this->enabled) {
            return false;
        }

        $body = $this->buildNotificationHtml($name, $message, $actionUrl);
        $textBody = "Hi {$name},\n\n{$message}" . ($actionUrl ? "\n\nView details: {$actionUrl}" : '');

        return $this->send($email, $name, $subject, $body, $textBody);
    }

    /**
     * Core send method using PHPMailer.
     */
    private function send(string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody): bool {
        if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            // PHPMailer not installed, log and return false
            error_log("Mailer: PHPMailer class not found. Install via composer.");
            return false;
        }

        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = defined('MAIL_HOST') ? MAIL_HOST : 'localhost';
            $mail->SMTPAuth   = true;
            $mail->Username   = defined('MAIL_USERNAME') ? MAIL_USERNAME : '';
            $mail->Password   = defined('MAIL_PASSWORD') ? MAIL_PASSWORD : '';
            $mail->SMTPSecure = defined('MAIL_ENCRYPTION') ? MAIL_ENCRYPTION : 'tls';
            $mail->Port       = defined('MAIL_PORT') ? MAIL_PORT : 587;

            $mail->setFrom(
                defined('MAIL_FROM_ADDRESS') ? MAIL_FROM_ADDRESS : 'noreply@hazina-asili.com',
                defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : APP_NAME
            );
            $mail->addAddress($toEmail, $toName);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = $textBody;

            $mail->send();
            return true;
        } catch (\Exception $e) {
            error_log("Mailer error: " . $e->getMessage());
            return false;
        }
    }

    private function buildResetEmailHtml(string $name, string $resetUrl): string {
        return <<<HTML
<!DOCTYPE html>
<html><head><meta charset="UTF-8"></head>
<body style="font-family:'Segoe UI',sans-serif;background:#f8f9fa;padding:40px 20px">
<div style="max-width:500px;margin:0 auto;background:#fff;border-radius:12px;padding:40px;box-shadow:0 2px 12px rgba(0,0,0,0.08)">
    <h2 style="color:#198754;margin-top:0">🔑 Password Reset</h2>
    <p>Hi <strong>{$name}</strong>,</p>
    <p>You requested a password reset for your HAZINA ASILI account. Click the button below to set a new password:</p>
    <div style="text-align:center;margin:30px 0">
        <a href="{$resetUrl}" style="background:#198754;color:#fff;padding:12px 30px;border-radius:6px;text-decoration:none;font-weight:600">Reset Password</a>
    </div>
    <p style="font-size:0.9em;color:#6c757d">This link will expire in <strong>1 hour</strong>. If you didn't request this reset, you can safely ignore this email.</p>
    <hr style="border:none;border-top:1px solid #eee;margin:25px 0">
    <p style="font-size:0.8em;color:#adb5bd;text-align:center">HAZINA ASILI — Natural Organic Compounds Database</p>
</div>
</body></html>
HTML;
    }

    private function buildNotificationHtml(string $name, string $message, string $actionUrl): string {
        $button = $actionUrl
            ? "<div style='text-align:center;margin:25px 0'><a href='{$actionUrl}' style='background:#0d6efd;color:#fff;padding:10px 25px;border-radius:6px;text-decoration:none;font-weight:500'>View Details</a></div>"
            : '';
        return <<<HTML
<!DOCTYPE html>
<html><head><meta charset="UTF-8"></head>
<body style="font-family:'Segoe UI',sans-serif;background:#f8f9fa;padding:40px 20px">
<div style="max-width:500px;margin:0 auto;background:#fff;border-radius:12px;padding:40px;box-shadow:0 2px 12px rgba(0,0,0,0.08)">
    <h2 style="color:#0d6efd;margin-top:0">📬 Notification</h2>
    <p>Hi <strong>{$name}</strong>,</p>
    <p>{$message}</p>
    {$button}
    <hr style="border:none;border-top:1px solid #eee;margin:25px 0">
    <p style="font-size:0.8em;color:#adb5bd;text-align:center">HAZINA ASILI — Natural Organic Compounds Database</p>
</div>
</body></html>
HTML;
    }
}
