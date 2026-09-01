<?php
/**
 * Recommendation Controller — with approval comments and notifications
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Recommendation.php';
require_once __DIR__ . '/../models/ActivityLog.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../models/Compound.php';

class RecommendationController {
    private Recommendation $model;
    private ActivityLog    $logModel;
    private Notification   $notifModel;

    public function __construct() {
        $this->model      = new Recommendation();
        $this->logModel   = new ActivityLog();
        $this->notifModel = new Notification();
    }

    public function store(): void {
        requireResearcher();
        if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Invalid security token.');
            redirect('views/researcher/recommendations/create.php');
        }

        $compoundId     = (int)($_POST['compound_id'] ?? 0);
        $fieldToChange  = sanitize($_POST['field_to_change'] ?? '');
        $suggestedValue = sanitize($_POST['suggested_value'] ?? '');
        $allowedFields  = ['name','formula','molecular_weight','description'];

        $errors = [];
        if (!$compoundId)                              $errors[] = 'Please select a compound.';
        if (!in_array($fieldToChange, $allowedFields)) $errors[] = 'Invalid field selected.';
        if (strlen($suggestedValue) < 1)               $errors[] = 'Suggested value is required.';

        // Scientific validation for formula/MW fields
        if ($fieldToChange === 'formula' && !Compound::validateFormula($suggestedValue)) {
            $errors[] = 'Invalid molecular formula format (e.g. C15H10O7).';
        }
        if ($fieldToChange === 'molecular_weight' && (!is_numeric($suggestedValue) || $suggestedValue <= 0)) {
            $errors[] = 'Molecular weight must be a positive number.';
        }

        if ($errors) { setFlash('error', implode('<br>', $errors)); redirect('views/researcher/recommendations/create.php'); }

        $id = $this->model->create([
            'user_id'         => $_SESSION['user_id'],
            'compound_id'     => $compoundId,
            'field_to_change' => $fieldToChange,
            'suggested_value' => $suggestedValue,
        ]);

        $this->notifModel->notifyAdmins(
            'recommendation',
            'New Recommendation Submitted',
            sanitize(currentUser()['name']) . " suggested a change to the '{$fieldToChange}' field.",
            BASE_URL . "views/admin/recommendations/view.php?id={$id}"
        );

        $this->logModel->log($_SESSION['user_id'], 'recommendation_submit', "Submitted recommendation for compound ID: {$compoundId}", 'recommendation', $id);
        setFlash('success', 'Recommendation submitted and is pending admin review.');
        redirect('views/researcher/recommendations/index.php');
    }

    public function approve(int $id): void {
        requireAdmin();
        if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Invalid security token.');
            redirect('views/admin/recommendations/index.php');
        }
        $comment = sanitize($_POST['admin_comment'] ?? '');
        $rec = $this->model->findById($id);
        if (!$rec) { setFlash('error', 'Recommendation not found.'); redirect('views/admin/recommendations/index.php'); }

        $this->model->updateStatus($id, STATUS_APPROVED, $_SESSION['user_id'], $comment);

        $this->notifModel->send(
            $rec['user_id'], 'success',
            'Recommendation Approved',
            'Your recommendation for "' . $rec['compound_name'] . '" (' . $rec['field_to_change'] . ') has been approved.' . ($comment ? " Note: {$comment}" : ''),
            BASE_URL . "views/researcher/recommendations/index.php"
        );

        $this->logModel->log($_SESSION['user_id'], 'recommendation_approve', "Approved recommendation ID: {$id}", 'recommendation', $id);
        setFlash('success', 'Recommendation approved.');
        redirect('views/admin/recommendations/index.php');
    }

    public function reject(int $id): void {
        requireAdmin();
        if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Invalid security token.');
            redirect('views/admin/recommendations/index.php');
        }
        $comment = sanitize($_POST['admin_comment'] ?? '');
        $rec = $this->model->findById($id);
        if (!$rec) { setFlash('error', 'Recommendation not found.'); redirect('views/admin/recommendations/index.php'); }

        $this->model->updateStatus($id, STATUS_REJECTED, $_SESSION['user_id'], $comment);

        $this->notifModel->send(
            $rec['user_id'], 'danger',
            'Recommendation Rejected',
            'Your recommendation for "' . $rec['compound_name'] . '" was not approved.' . ($comment ? " Reason: {$comment}" : ''),
            BASE_URL . "views/researcher/recommendations/index.php"
        );

        $this->logModel->log($_SESSION['user_id'], 'recommendation_reject', "Rejected recommendation ID: {$id}", 'recommendation', $id);
        setFlash('success', 'Recommendation rejected.');
        redirect('views/admin/recommendations/index.php');
    }

    public function delete(int $id): void {
        requireAdmin();
        if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Invalid security token.');
            redirect('views/admin/recommendations/index.php');
        }
        $this->model->delete($id);
        $this->logModel->log($_SESSION['user_id'], 'recommendation_delete', "Deleted recommendation ID: {$id}", 'recommendation', $id);
        setFlash('success', 'Recommendation deleted.');
        redirect('views/admin/recommendations/index.php');
    }
}
