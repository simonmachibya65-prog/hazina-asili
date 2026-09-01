<?php
/**
 * User Controller (Admin)
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/ActivityLog.php';

class UserController {
    private User $model;
    private ActivityLog $logModel;

    public function __construct() {
        $this->model    = new User();
        $this->logModel = new ActivityLog();
    }

    public function store(): void {
        requireAdmin();
        if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Invalid security token.');
            redirect('views/admin/users/create.php');
        }

        $name     = sanitize($_POST['name'] ?? '');
        $email    = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';
        $role     = in_array($_POST['role'] ?? '', [ROLE_ADMIN, ROLE_RESEARCHER]) ? $_POST['role'] : ROLE_RESEARCHER;

        $errors = [];
        if (strlen($name) < 2)    $errors[] = 'Name must be at least 2 characters.';
        if (!$email)              $errors[] = 'Valid email is required.';
        if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
        if ($email && $this->model->emailExists($email)) $errors[] = 'Email already exists.';

        if ($errors) {
            setFlash('error', implode('<br>', $errors));
            redirect('views/admin/users/create.php');
        }

        $id = $this->model->create(['name' => $name, 'email' => $email, 'password' => $password, 'role' => $role]);
        $this->logModel->log($_SESSION['user_id'], 'user_create', "Admin created user: {$email}");
        setFlash('success', 'User created successfully.');
        redirect('views/admin/users/index.php');
    }

    public function update(int $id): void {
        requireAdmin();
        if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Invalid security token.');
            redirect("views/admin/users/edit.php?id={$id}");
        }

        $name  = sanitize($_POST['name'] ?? '');
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $role  = in_array($_POST['role'] ?? '', [ROLE_ADMIN, ROLE_RESEARCHER]) ? $_POST['role'] : ROLE_RESEARCHER;

        $errors = [];
        if (strlen($name) < 2) $errors[] = 'Name is required.';
        if (!$email)           $errors[] = 'Valid email is required.';
        if ($email && $this->model->emailExists($email, $id)) $errors[] = 'Email already in use.';

        if ($errors) {
            setFlash('error', implode('<br>', $errors));
            redirect("views/admin/users/edit.php?id={$id}");
        }

        $this->model->update($id, ['name' => $name, 'email' => $email, 'role' => $role]);

        // Optional password change
        if (!empty($_POST['password']) && strlen($_POST['password']) >= 8) {
            $this->model->updatePassword($id, $_POST['password']);
        }

        $this->logModel->log($_SESSION['user_id'], 'user_update', "Updated user ID: {$id}");
        setFlash('success', 'User updated successfully.');
        redirect('views/admin/users/index.php');
    }

    public function delete(int $id): void {
        requireAdmin();
        if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Invalid security token.');
            redirect('views/admin/users/index.php');
        }
        // Prevent self-deletion
        if ($id === (int) $_SESSION['user_id']) {
            setFlash('error', 'You cannot delete your own account.');
            redirect('views/admin/users/index.php');
        }
        $this->model->delete($id);
        $this->logModel->log($_SESSION['user_id'], 'user_delete', "Deleted user ID: {$id}");
        setFlash('success', 'User deleted.');
        redirect('views/admin/users/index.php');
    }
}
