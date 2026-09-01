<?php
/**
 * Authentication Controller — with rate limiting, lockout, and email support
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/ActivityLog.php';

class AuthController {
    private User $userModel;
    private ActivityLog $logModel;
    private RateLimiter $rateLimiter;
    private Mailer $mailer;

    public function __construct() {
        $this->userModel   = new User();
        $this->logModel    = new ActivityLog();
        $this->rateLimiter = new RateLimiter(LOGIN_MAX_ATTEMPTS, LOGIN_LOCKOUT_MINUTES);
        $this->mailer      = new Mailer();
    }

    // ─── Register ─────────────────────────────────────────────────────────────

    public function register(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Invalid security token. Please try again.');
            redirect('views/auth/register.php');
        }

        $name        = sanitize($_POST['name'] ?? '');
        $email       = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $password    = $_POST['password'] ?? '';
        $confirm     = $_POST['confirm_password'] ?? '';
        $institution = sanitize($_POST['institution'] ?? '');
        // Always register as researcher — admin role is never publicly accessible
        $role = ROLE_RESEARCHER;

        $errors = [];
        if (strlen($name) < 2)                 $errors[] = 'Name must be at least 2 characters.';
        if (!$email)                            $errors[] = 'Valid email is required.';
        if (strlen($password) < 8)             $errors[] = 'Password must be at least 8 characters.';
        if ($password !== $confirm)            $errors[] = 'Passwords do not match.';
        if (!preg_match('/[A-Z]/', $password)) $errors[] = 'Password must contain at least one uppercase letter.';
        if (!preg_match('/[0-9]/', $password)) $errors[] = 'Password must contain at least one number.';
        if (!preg_match('/[^A-Za-z0-9]/', $password)) $errors[] = 'Password must contain at least one special character.';

        if ($email && $this->userModel->emailExists($email)) {
            $errors[] = 'This email is already registered.';
        }

        if ($errors) {
            setFlash('error', implode('<br>', $errors));
            redirect('views/auth/register.php');
        }

        $id = $this->userModel->create([
            'name'        => $name,
            'email'       => $email,
            'password'    => $password,
            'role'        => $role,
            'institution' => $institution,
        ]);

        $roleLabel = $role === ROLE_ADMIN ? 'Admin' : 'Researcher';
        $this->logModel->log($id, 'register', "New {$roleLabel} registered: {$email}", 'user', $id);

        setFlash('success', "Registration successful as <strong>{$roleLabel}</strong>! Please log in.");
        redirect('views/auth/login.php');
    }

    // ─── Admin Login (secret, password-only) ─────────────────────────────────

    public function adminLogin(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Invalid security token.');
            redirect('views/auth/login.php');
        }

        $password = $_POST['admin_password'] ?? '';
        $username = trim($_POST['admin_username'] ?? '');

        if (!$password || !$username) {
            setFlash('error', 'Username and password are required.');
            redirect('views/auth/login.php');
        }

        // Rate limiting for admin login too
        $adminEmail = '__admin_access__';
        if ($this->rateLimiter->isLocked($adminEmail)) {
            $remaining = $this->rateLimiter->getLockoutRemaining($adminEmail);
            setFlash('error', "Too many attempts. Try again in {$remaining} minute(s).");
            redirect('views/auth/login.php');
        }

        // Verify against ADMIN_USERNAME and ADMIN_PASSWORD from .env
        if ($username !== ADMIN_USERNAME || $password !== ADMIN_PASSWORD) {
            $this->rateLimiter->recordFailedAttempt($adminEmail);
            $this->logModel->log(0, 'admin_login_failed', 'Failed admin login attempt from IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            setFlash('error', 'Invalid admin credentials.');
            redirect('views/auth/login.php');
        }

        // Find the first admin user in the database
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE role = ? LIMIT 1");
        $stmt->execute([ROLE_ADMIN]);
        $admin = $stmt->fetch();

        if (!$admin) {
            setFlash('error', 'No admin account found in the system.');
            redirect('views/auth/login.php');
        }

        // Clear rate limiter and log in
        $this->rateLimiter->clearAttempts($adminEmail);
        session_regenerate_id(true);

        $_SESSION['user_id'] = $admin['id'];
        $_SESSION['user'] = [
            'id'    => $admin['id'],
            'name'  => $admin['name'],
            'email' => $admin['email'],
            'role'  => $admin['role'],
        ];
        $_SESSION['last_activity'] = time();
        $_SESSION['admin_secret_access'] = true;

        $this->logModel->log($admin['id'], 'admin_login', 'Admin logged in via secret access');
        redirect('views/admin/dashboard.php');
    }

    // ─── Login ────────────────────────────────────────────────────────────────

    public function login(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Invalid security token.');
            redirect('views/auth/login.php');
        }

        $email    = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';

        if (!$email || !$password) {
            setFlash('error', 'Email and password are required.');
            redirect('views/auth/login.php');
        }

        // Rate limiting check
        if ($this->rateLimiter->isLocked($email)) {
            $remaining = $this->rateLimiter->getLockoutRemaining($email);
            setFlash('error', "Too many failed login attempts. Please try again in {$remaining} minute(s).");
            $this->logModel->log(0, 'login_blocked', "Login blocked (rate limit) for: {$email}");
            redirect('views/auth/login.php');
        }

        $user = $this->userModel->findByEmail($email);

        if (!$user || !verifyPassword($password, $user['password'])) {
            // Record failed attempt
            $this->rateLimiter->recordFailedAttempt($email);
            $attemptsLeft = $this->rateLimiter->remainingAttempts($email);

            $msg = 'Invalid email or password.';
            if ($attemptsLeft > 0 && $attemptsLeft <= 3) {
                $msg .= " {$attemptsLeft} attempt(s) remaining before lockout.";
            }
            setFlash('error', $msg);
            redirect('views/auth/login.php');
        }

        // Successful login — clear rate limiter
        $this->rateLimiter->clearAttempts($email);

        // Regenerate session ID to prevent fixation
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user']    = [
            'id'    => $user['id'],
            'name'  => $user['name'],
            'email' => $user['email'],
            'role'  => $user['role'],
        ];
        $_SESSION['last_activity'] = time();

        $this->logModel->log($user['id'], 'login', "User logged in: {$email}");

        // Normal login always goes to researcher dashboard
        // Admin can only access admin panel via secret login
        if ($user['role'] === ROLE_ADMIN) {
            // Admin trying normal login → treat as researcher view
            redirect('views/researcher/dashboard.php');
        } else {
            redirect('views/researcher/dashboard.php');
        }
    }

    // ─── Logout ───────────────────────────────────────────────────────────────

    public function logout(): void {
        if (isLoggedIn()) {
            $this->logModel->log($_SESSION['user_id'], 'logout', 'User logged out');
        }
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
        redirect('views/auth/login.php');
    }

    // ─── Password Reset Request ───────────────────────────────────────────────

    public function requestReset(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Invalid security token.');
            redirect('views/auth/forgot_password.php');
        }

        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        if (!$email) {
            setFlash('error', 'Valid email is required.');
            redirect('views/auth/forgot_password.php');
        }

        // Rate limit password reset requests too
        if ($this->rateLimiter->isLocked($email)) {
            $remaining = $this->rateLimiter->getLockoutRemaining($email);
            setFlash('error', "Too many attempts. Please try again in {$remaining} minute(s).");
            redirect('views/auth/forgot_password.php');
        }

        $user = $this->userModel->findByEmail($email);
        // Always show success to prevent email enumeration
        if ($user) {
            $token  = generateToken();
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
            $this->userModel->saveResetToken($user['id'], $token, $expiry);

            // Send email (or demo mode)
            $this->mailer->sendPasswordReset($email, $user['name'], $token);

            $this->logModel->log($user['id'], 'password_reset_request', "Reset requested for: {$email}");
        } else {
            // Record attempt for non-existent email (prevents enumeration brute force)
            $this->rateLimiter->recordFailedAttempt($email);
        }

        setFlash('success', 'If that email is registered, a reset link has been sent. Check your inbox (or spam folder).');
        redirect('views/auth/forgot_password.php');
    }

    // ─── Reset Password ───────────────────────────────────────────────────────

    public function resetPassword(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Invalid security token.');
            redirect('views/auth/reset_password.php');
        }

        $token    = sanitize($_POST['token'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        $errors = [];
        if (strlen($password) < 8)              $errors[] = 'Password must be at least 8 characters.';
        if ($password !== $confirm)             $errors[] = 'Passwords do not match.';
        if (!preg_match('/[A-Z]/', $password))  $errors[] = 'Password must contain at least one uppercase letter.';
        if (!preg_match('/[0-9]/', $password))  $errors[] = 'Password must contain at least one number.';

        if ($errors) {
            setFlash('error', implode('<br>', $errors));
            redirect("views/auth/reset_password.php?token={$token}");
        }

        $user = $this->userModel->findByResetToken($token);
        if (!$user) {
            setFlash('error', 'Invalid or expired reset token.');
            redirect('views/auth/forgot_password.php');
        }

        $this->userModel->updatePassword($user['id'], $password);
        $this->userModel->clearResetToken($user['id']);
        $this->logModel->log($user['id'], 'password_reset', 'Password was reset');

        setFlash('success', 'Password reset successfully. Please log in with your new password.');
        redirect('views/auth/login.php');
    }
}
