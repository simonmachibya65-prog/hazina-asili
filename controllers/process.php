<?php
/**
 * Central POST Request Router
 * All form submissions are handled here.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Compound.php';
require_once __DIR__ . '/../models/Organism.php';
require_once __DIR__ . '/../models/Reference.php';
require_once __DIR__ . '/../models/Insight.php';
require_once __DIR__ . '/../models/Recommendation.php';
require_once __DIR__ . '/../models/ActivityLog.php';
require_once __DIR__ . '/../models/CompoundVersion.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../models/ErrorLog.php';
require_once __DIR__ . '/AuthController.php';
require_once __DIR__ . '/CompoundController.php';
require_once __DIR__ . '/OrganismController.php';
require_once __DIR__ . '/InsightController.php';
require_once __DIR__ . '/RecommendationController.php';
require_once __DIR__ . '/UserController.php';
require_once __DIR__ . '/ImportController.php';

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('views/errors/404.php');
}

$action = sanitize($_POST['action'] ?? '');

switch ($action) {

    // ── Auth ──────────────────────────────────────────────────────────────────
    case 'login':
        (new AuthController())->login();
        break;

    case 'admin_login':
        (new AuthController())->adminLogin();
        break;

    case 'register':
        (new AuthController())->register();
        break;

    case 'logout':
        (new AuthController())->logout();
        break;

    case 'admin_logout':
        requireLogin();
        if (!verifyCsrf($_POST['csrf_token'] ?? '')) { setFlash('error', 'Invalid token.'); redirect('views/researcher/dashboard.php'); }
        unset($_SESSION['admin_secret_access']);
        (new ActivityLog())->log($_SESSION['user_id'], 'admin_logout', 'Exited admin panel');
        setFlash('success', 'Exited admin panel.');
        redirect('views/researcher/dashboard.php');
        break;

    case 'request_reset':
        (new AuthController())->requestReset();
        break;

    case 'reset_password':
        (new AuthController())->resetPassword();
        break;

    // ── Compounds ─────────────────────────────────────────────────────────────
    case 'create_compound':
        (new CompoundController())->store();
        break;

    case 'update_compound':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { setFlash('error', 'Invalid compound ID.'); redirect('views/admin/compounds/index.php'); }
        (new CompoundController())->update($id);
        break;

    case 'delete_compound':
        requireAdmin();
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { setFlash('error', 'Invalid ID.'); redirect('views/admin/compounds/index.php'); }
        (new CompoundController())->delete($id);
        break;

    case 'rollback_compound':
        requireAdmin();
        $id      = (int)($_POST['id'] ?? 0);
        $version = (int)($_POST['version'] ?? 0);
        if (!$id || !$version) { setFlash('error', 'Invalid parameters.'); redirect('views/admin/compounds/index.php'); }
        (new CompoundController())->rollback($id, $version);
        break;

    // ── Organisms ─────────────────────────────────────────────────────────────
    case 'create_organism':
        (new OrganismController())->store();
        break;

    case 'update_organism':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { setFlash('error', 'Invalid organism ID.'); redirect('views/admin/organisms/index.php'); }
        (new OrganismController())->update($id);
        break;

    case 'delete_organism':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { setFlash('error', 'Invalid ID.'); redirect('views/admin/organisms/index.php'); }
        (new OrganismController())->delete($id);
        break;

    // ── References ────────────────────────────────────────────────────────────
    case 'create_reference':
        requireAdmin();
        if (!verifyCsrf($_POST['csrf_token'] ?? '')) { setFlash('error', 'Invalid token.'); redirect('views/admin/references/create.php'); }
        $data = [
            'title'    => sanitize($_POST['title'] ?? ''),
            'author'   => sanitize($_POST['author'] ?? ''),
            'year'     => (int)($_POST['year'] ?? 0),
            'citation' => sanitize($_POST['citation'] ?? ''),
        ];
        $errors = [];
        if (strlen($data['title']) < 2)    $errors[] = 'Title is required.';
        if (strlen($data['author']) < 2)   $errors[] = 'Author is required.';
        if ($data['year'] < 1900 || $data['year'] > (int)date('Y')) $errors[] = 'Valid year is required.';
        if (strlen($data['citation']) < 5) $errors[] = 'Citation is required.';
        if ($errors) { setFlash('error', implode('<br>', $errors)); redirect('views/admin/references/create.php'); }
        (new Reference())->create($data);
        (new ActivityLog())->log($_SESSION['user_id'], 'reference_create', "Created: {$data['title']}");
        setFlash('success', 'Reference created.');
        redirect('views/admin/references/index.php');
        break;

    case 'update_reference':
        requireAdmin();
        if (!verifyCsrf($_POST['csrf_token'] ?? '')) { setFlash('error', 'Invalid token.'); redirect('views/admin/references/index.php'); }
        $id   = (int)($_POST['id'] ?? 0);
        $data = [
            'title'    => sanitize($_POST['title'] ?? ''),
            'author'   => sanitize($_POST['author'] ?? ''),
            'year'     => (int)($_POST['year'] ?? 0),
            'citation' => sanitize($_POST['citation'] ?? ''),
        ];
        (new Reference())->update($id, $data);
        (new ActivityLog())->log($_SESSION['user_id'], 'reference_update', "Updated reference ID: {$id}");
        setFlash('success', 'Reference updated.');
        redirect('views/admin/references/index.php');
        break;

    case 'delete_reference':
        requireAdmin();
        if (!verifyCsrf($_POST['csrf_token'] ?? '')) { setFlash('error', 'Invalid token.'); redirect('views/admin/references/index.php'); }
        $id = (int)($_POST['id'] ?? 0);
        (new Reference())->delete($id);
        (new ActivityLog())->log($_SESSION['user_id'], 'reference_delete', "Deleted reference ID: {$id}");
        setFlash('success', 'Reference deleted.');
        redirect('views/admin/references/index.php');
        break;

    // ── Users ─────────────────────────────────────────────────────────────────
    case 'create_user':
        (new UserController())->store();
        break;

    case 'update_user':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { setFlash('error', 'Invalid user ID.'); redirect('views/admin/users/index.php'); }
        (new UserController())->update($id);
        break;

    case 'delete_user':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { setFlash('error', 'Invalid user ID.'); redirect('views/admin/users/index.php'); }
        (new UserController())->delete($id);
        break;

    // ── Insights ──────────────────────────────────────────────────────────────
    case 'submit_insight':
        (new InsightController())->store();
        break;

    case 'approve_insight':
        $id = (int)($_POST['id'] ?? 0);
        (new InsightController())->approve($id);
        break;

    case 'reject_insight':
        $id = (int)($_POST['id'] ?? 0);
        (new InsightController())->reject($id);
        break;

    case 'delete_insight':
        $id = (int)($_POST['id'] ?? 0);
        (new InsightController())->delete($id);
        break;

    // ── Recommendations ───────────────────────────────────────────────────────
    case 'submit_recommendation':
        (new RecommendationController())->store();
        break;

    case 'approve_recommendation':
        $id = (int)($_POST['id'] ?? 0);
        (new RecommendationController())->approve($id);
        break;

    case 'reject_recommendation':
        $id = (int)($_POST['id'] ?? 0);
        (new RecommendationController())->reject($id);
        break;

    case 'delete_recommendation':
        $id = (int)($_POST['id'] ?? 0);
        (new RecommendationController())->delete($id);
        break;

    // ── Profile ───────────────────────────────────────────────────────────────
    case 'update_profile':
        requireLogin();
        if (!verifyCsrf($_POST['csrf_token'] ?? '')) { setFlash('error', 'Invalid token.'); redirect('views/profile.php'); }
        $uid  = $_SESSION['user_id'];
        $name = sanitize($_POST['name'] ?? '');
        $email= filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $bio  = sanitize($_POST['bio'] ?? '');
        $inst = sanitize($_POST['institution'] ?? '');
        $errors = [];
        if (strlen($name) < 2) $errors[] = 'Name is required.';
        if (!$email)           $errors[] = 'Valid email is required.';
        $userModel = new User();
        if ($email && $userModel->emailExists($email, $uid)) $errors[] = 'Email already in use.';
        if ($errors) { setFlash('error', implode('<br>', $errors)); redirect('views/profile.php'); }
        $userModel->update($uid, ['name'=>$name,'email'=>$email,'bio'=>$bio,'institution'=>$inst]);
        // Refresh session
        $updated = $userModel->findById($uid);
        $_SESSION['user'] = ['id'=>$updated['id'],'name'=>$updated['name'],'email'=>$updated['email'],'role'=>$updated['role']];
        (new ActivityLog())->log($uid, 'profile_update', 'Updated profile');
        setFlash('success', 'Profile updated successfully.');
        redirect('views/profile.php');
        break;

    case 'upload_avatar':
        requireLogin();
        if (!verifyCsrf($_POST['csrf_token'] ?? '')) { setFlash('error', 'Invalid token.'); redirect('views/profile.php'); }
        $uid = $_SESSION['user_id'];

        if (empty($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            setFlash('error', 'No file uploaded or upload error occurred.');
            redirect('views/profile.php');
        }

        $file     = $_FILES['avatar'];
        $maxSize  = 2 * 1024 * 1024; // 2MB
        $allowed  = ['image/jpeg','image/png','image/gif','image/webp'];
        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $extMap   = ['jpeg'=>'jpg','jpg'=>'jpg','png'=>'png','gif'=>'gif','webp'=>'webp'];

        $errors = [];
        if ($file['size'] > $maxSize)                    $errors[] = 'File too large. Maximum size is 2MB.';
        if (!in_array($file['type'], $allowed))          $errors[] = 'Only JPG, PNG, GIF, and WEBP images are allowed.';
        if (!array_key_exists($ext, $extMap))            $errors[] = 'Invalid file extension.';

        // Verify it is actually an image
        $imgInfo = @getimagesize($file['tmp_name']);
        if (!$imgInfo)                                   $errors[] = 'Uploaded file is not a valid image.';

        if ($errors) { setFlash('error', implode('<br>', $errors)); redirect('views/profile.php'); }

        $userModel  = new User();
        $oldUser    = $userModel->findById($uid);
        $uploadDir  = __DIR__ . '/../assets/uploads/avatars/';
        $filename   = 'avatar_' . $uid . '_' . time() . '.' . $extMap[$ext];
        $destination= $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            setFlash('error', 'Failed to save the image. Please try again.');
            redirect('views/profile.php');
        }

        // Delete old avatar file if exists
        if (!empty($oldUser['avatar'])) {
            $oldFile = $uploadDir . $oldUser['avatar'];
            if (file_exists($oldFile)) @unlink($oldFile);
        }

        $userModel->updateAvatar($uid, $filename);
        (new ActivityLog())->log($uid, 'avatar_upload', 'Updated profile photo');
        setFlash('success', 'Profile photo updated successfully.');
        redirect('views/profile.php');
        break;

    case 'remove_avatar':
        requireLogin();
        if (!verifyCsrf($_POST['csrf_token'] ?? '')) { setFlash('error', 'Invalid token.'); redirect('views/profile.php'); }
        $uid       = $_SESSION['user_id'];
        $userModel = new User();
        $oldUser   = $userModel->findById($uid);
        if (!empty($oldUser['avatar'])) {
            $oldFile = __DIR__ . '/../assets/uploads/avatars/' . $oldUser['avatar'];
            if (file_exists($oldFile)) @unlink($oldFile);
        }
        $userModel->removeAvatar($uid);
        (new ActivityLog())->log($uid, 'avatar_remove', 'Removed profile photo');
        setFlash('success', 'Profile photo removed.');
        redirect('views/profile.php');
        break;

    case 'upload_passport':
        requireLogin();
        if (!verifyCsrf($_POST['csrf_token'] ?? '')) { setFlash('error', 'Invalid token.'); redirect('views/profile.php'); }
        $uid = $_SESSION['user_id'];

        if (empty($_FILES['passport']) || $_FILES['passport']['error'] !== UPLOAD_ERR_OK) {
            setFlash('error', 'No file uploaded or upload error occurred.');
            redirect('views/profile.php');
        }

        $file    = $_FILES['passport'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        $allowed = ['application/pdf', 'image/jpeg', 'image/png'];
        $extMap  = ['pdf' => 'pdf', 'jpg' => 'jpg', 'jpeg' => 'jpg', 'png' => 'png'];
        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        $errors = [];
        if ($file['size'] > $maxSize)               $errors[] = 'File too large. Maximum size is 5MB.';
        if (!in_array($file['type'], $allowed))     $errors[] = 'Only PDF, JPG, and PNG files are allowed.';
        if (!array_key_exists($ext, $extMap))       $errors[] = 'Invalid file extension.';

        // For images, verify they are actually images
        if (in_array($ext, ['jpg','jpeg','png']) && !@getimagesize($file['tmp_name'])) {
            $errors[] = 'Uploaded image file is not valid.';
        }

        if ($errors) { setFlash('error', implode('<br>', $errors)); redirect('views/profile.php'); }

        $userModel   = new User();
        $oldUser     = $userModel->findById($uid);
        $uploadDir   = __DIR__ . '/../assets/uploads/passports/';
        $filename    = 'passport_' . $uid . '_' . time() . '.' . $extMap[$ext];
        $destination = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            setFlash('error', 'Failed to save the file. Please try again.');
            redirect('views/profile.php');
        }

        // Delete old passport file if exists
        if (!empty($oldUser['passport_document'])) {
            $oldFile = $uploadDir . $oldUser['passport_document'];
            if (file_exists($oldFile)) @unlink($oldFile);
        }

        $userModel->updatePassport($uid, $filename);
        (new ActivityLog())->log($uid, 'passport_upload', 'Uploaded passport document');
        setFlash('success', 'Passport document uploaded successfully.');
        redirect('views/profile.php');
        break;

    case 'remove_passport':
        requireLogin();
        if (!verifyCsrf($_POST['csrf_token'] ?? '')) { setFlash('error', 'Invalid token.'); redirect('views/profile.php'); }
        $uid       = $_SESSION['user_id'];
        $userModel = new User();
        $oldUser   = $userModel->findById($uid);
        if (!empty($oldUser['passport_document'])) {
            $oldFile = __DIR__ . '/../assets/uploads/passports/' . $oldUser['passport_document'];
            if (file_exists($oldFile)) @unlink($oldFile);
        }
        $userModel->removePassport($uid);
        (new ActivityLog())->log($uid, 'passport_remove', 'Removed passport document');
        setFlash('success', 'Passport document removed.');
        redirect('views/profile.php');
        break;

    case 'change_password':
        requireLogin();
        if (!verifyCsrf($_POST['csrf_token'] ?? '')) { setFlash('error', 'Invalid token.'); redirect('views/profile.php'); }
        $uid     = $_SESSION['user_id'];
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $userModel = new User();
        $errors = [];
        if (!$userModel->verifyCurrentPassword($uid, $current)) $errors[] = 'Current password is incorrect.';
        if (strlen($new) < 8)    $errors[] = 'New password must be at least 8 characters.';
        if ($new !== $confirm)   $errors[] = 'Passwords do not match.';
        if (!preg_match('/[A-Z]/', $new)) $errors[] = 'Password must contain at least one uppercase letter.';
        if (!preg_match('/[0-9]/', $new)) $errors[] = 'Password must contain at least one number.';
        if ($errors) { setFlash('error', implode('<br>', $errors)); redirect('views/profile.php'); }
        $userModel->updatePassword($uid, $new);
        (new ActivityLog())->log($uid, 'password_change', 'Changed own password');
        setFlash('success', 'Password changed successfully.');
        redirect('views/profile.php');
        break;

    case 'mark_all_read':
        requireLogin();
        if (!verifyCsrf($_POST['csrf_token'] ?? '')) { setFlash('error', 'Invalid token.'); redirect('views/notifications.php'); }
        (new Notification())->markAllRead($_SESSION['user_id']);
        setFlash('success', 'All notifications marked as read.');
        redirect('views/notifications.php');
        break;

    case 'delete_notification':
        requireLogin();
        if (!verifyCsrf($_POST['csrf_token'] ?? '')) { setFlash('error', 'Invalid token.'); redirect('views/notifications.php'); }
        $nid = (int)($_POST['id'] ?? 0);
        (new Notification())->delete($nid, $_SESSION['user_id']);
        redirect('views/notifications.php');
        break;

    case 'clear_error_log':
        requireAdmin();
        if (!verifyCsrf($_POST['csrf_token'] ?? '')) { setFlash('error', 'Invalid token.'); redirect('views/admin/error_log.php'); }
        $days = (int)($_POST['days'] ?? 30);
        $deleted = (new ErrorLog())->clearOld($days);
        setFlash('success', "Cleared {$deleted} error log entries older than {$days} days.");
        redirect('views/admin/error_log.php');
        break;

    // ── Import ────────────────────────────────────────────────────────────────
    case 'import_preview':
        requireAdmin();
        if (!verifyCsrf($_POST['csrf_token'] ?? '')) { setFlash('error', 'Invalid token.'); redirect('views/admin/import.php'); }
        $importType = sanitize($_POST['import_type'] ?? 'compounds');
        $result = (new ImportController())->preview($importType);
        if (!$result['success']) {
            setFlash('error', $result['error']);
            redirect('views/admin/import.php');
        }
        $_SESSION['import_result'] = $result;
        redirect('views/admin/import.php?step=preview');
        break;

    case 'import_commit':
        requireAdmin();
        if (!verifyCsrf($_POST['csrf_token'] ?? '')) { setFlash('error', 'Invalid token.'); redirect('views/admin/import.php'); }
        $result = (new ImportController())->commit();
        if ($result['success']) {
            setFlash('success', "Import complete: {$result['imported']} imported, {$result['skipped']} skipped.");
        } else {
            setFlash('error', $result['error']);
        }
        redirect('views/admin/import.php');
        break;

    case 'import_cancel':
        requireAdmin();
        unset($_SESSION['import_preview'], $_SESSION['import_result']);
        setFlash('info', 'Import cancelled.');
        redirect('views/admin/import.php');
        break;

    // ── API Key ───────────────────────────────────────────────────────────────
    case 'generate_api_key':
        requireLogin();
        if (!verifyCsrf($_POST['csrf_token'] ?? '')) { setFlash('error', 'Invalid token.'); redirect('views/profile.php'); }
        $uid = $_SESSION['user_id'];
        $apiKey = bin2hex(random_bytes(32));
        $db = Database::getInstance()->getConnection();
        $db->prepare("UPDATE users SET api_key = ? WHERE id = ?")->execute([$apiKey, $uid]);
        (new ActivityLog())->log($uid, 'api_key_generate', 'Generated new API key');
        setFlash('success', 'API key generated: <code>' . $apiKey . '</code><br><small class="text-muted">Copy it now — it won\'t be shown again.</small>');
        redirect('views/profile.php');
        break;

    case 'revoke_api_key':
        requireLogin();
        if (!verifyCsrf($_POST['csrf_token'] ?? '')) { setFlash('error', 'Invalid token.'); redirect('views/profile.php'); }
        $uid = $_SESSION['user_id'];
        $db = Database::getInstance()->getConnection();
        $db->prepare("UPDATE users SET api_key = NULL WHERE id = ?")->execute([$uid]);
        (new ActivityLog())->log($uid, 'api_key_revoke', 'Revoked API key');
        setFlash('success', 'API key revoked.');
        redirect('views/profile.php');
        break;

    // ── Default ───────────────────────────────────────────────────────────────
    default:
        setFlash('error', 'Unknown action.');
        redirect('views/researcher/dashboard.php');
}

// ── Notification helpers ──────────────────────────────────────────────────────
// Mark notification read (AJAX-friendly GET)
if (isset($_GET['mark_read'])) {
    requireLogin();
    $nid = (int)($_GET['mark_read']);
    (new Notification())->markRead($nid, $_SESSION['user_id']);
    exit;
}
