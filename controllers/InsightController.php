<?php
/**
 * Insight Controller — with approval comments and notifications
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Insight.php';
require_once __DIR__ . '/../models/ActivityLog.php';
require_once __DIR__ . '/../models/Notification.php';

class InsightController {
    private Insight      $model;
    private ActivityLog  $logModel;
    private Notification $notifModel;

    public function __construct() {
        $this->model      = new Insight();
        $this->logModel   = new ActivityLog();
        $this->notifModel = new Notification();
    }

    public function store(): void {
        requireResearcher();
        if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Invalid security token.');
            redirect('views/researcher/insights/create.php');
        }

        $compoundId  = (int)($_POST['compound_id'] ?? 0);
        $insightText = sanitize($_POST['insight_text'] ?? '');

        $errors = [];
        if (!$compoundId)              $errors[] = 'Please select a compound.';
        if (strlen($insightText) < 10) $errors[] = 'Insight text must be at least 10 characters.';

        if ($errors) { setFlash('error', implode('<br>', $errors)); redirect('views/researcher/insights/create.php'); }

        $id = $this->model->create([
            'user_id'      => $_SESSION['user_id'],
            'compound_id'  => $compoundId,
            'insight_text' => $insightText,
        ]);

        // Notify all admins
        $this->notifModel->notifyAdmins(
            'insight',
            'New Insight Submitted',
            sanitize(currentUser()['name']) . ' submitted a new insight for review.',
            BASE_URL . "views/admin/insights/view.php?id={$id}"
        );

        $this->logModel->log($_SESSION['user_id'], 'insight_submit', "Submitted insight for compound ID: {$compoundId}", 'insight', $id);
        setFlash('success', 'Insight submitted and is pending admin review.');
        redirect('views/researcher/insights/index.php');
    }

    public function approve(int $id): void {
        requireAdmin();
        if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Invalid security token.');
            redirect('views/admin/insights/index.php');
        }
        $comment = sanitize($_POST['admin_comment'] ?? '');
        $insight = $this->model->findById($id);
        if (!$insight) { setFlash('error', 'Insight not found.'); redirect('views/admin/insights/index.php'); }

        $this->model->updateStatus($id, STATUS_APPROVED, $_SESSION['user_id'], $comment);

        // Notify researcher
        $this->notifModel->send(
            $insight['user_id'], 'success',
            'Insight Approved',
            'Your insight on "' . $insight['compound_name'] . '" has been approved.' . ($comment ? " Admin note: {$comment}" : ''),
            BASE_URL . "views/researcher/insights/index.php"
        );

        $this->logModel->log($_SESSION['user_id'], 'insight_approve', "Approved insight ID: {$id}", 'insight', $id);
        setFlash('success', 'Insight approved.');
        redirect('views/admin/insights/index.php');
    }

    public function reject(int $id): void {
        requireAdmin();
        if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Invalid security token.');
            redirect('views/admin/insights/index.php');
        }
        $comment = sanitize($_POST['admin_comment'] ?? '');
        $insight = $this->model->findById($id);
        if (!$insight) { setFlash('error', 'Insight not found.'); redirect('views/admin/insights/index.php'); }

        $this->model->updateStatus($id, STATUS_REJECTED, $_SESSION['user_id'], $comment);

        $this->notifModel->send(
            $insight['user_id'], 'danger',
            'Insight Rejected',
            'Your insight on "' . $insight['compound_name'] . '" was not approved.' . ($comment ? " Reason: {$comment}" : ''),
            BASE_URL . "views/researcher/insights/index.php"
        );

        $this->logModel->log($_SESSION['user_id'], 'insight_reject', "Rejected insight ID: {$id}", 'insight', $id);
        setFlash('success', 'Insight rejected.');
        redirect('views/admin/insights/index.php');
    }

    public function delete(int $id): void {
        requireAdmin();
        if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Invalid security token.');
            redirect('views/admin/insights/index.php');
        }
        $this->model->delete($id);
        $this->logModel->log($_SESSION['user_id'], 'insight_delete', "Deleted insight ID: {$id}", 'insight', $id);
        setFlash('success', 'Insight deleted.');
        redirect('views/admin/insights/index.php');
    }
}
