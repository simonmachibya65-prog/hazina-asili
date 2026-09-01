<?php
/**
 * Compound Controller — version control, scientific validation, notifications
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Compound.php';
require_once __DIR__ . '/../models/CompoundVersion.php';
require_once __DIR__ . '/../models/Organism.php';
require_once __DIR__ . '/../models/Reference.php';
require_once __DIR__ . '/../models/ActivityLog.php';
require_once __DIR__ . '/../models/Notification.php';

class CompoundController {
    private Compound        $model;
    private CompoundVersion $versionModel;
    private ActivityLog     $logModel;
    private Notification    $notifModel;

    public function __construct() {
        $this->model        = new Compound();
        $this->versionModel = new CompoundVersion();
        $this->logModel     = new ActivityLog();
        $this->notifModel   = new Notification();
    }

    public function store(): void {
        requireAdmin();
        if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Invalid security token.');
            redirect('views/admin/compounds/create.php');
        }

        [$data, $errors] = $this->validateCompoundInput();
        if ($errors) { setFlash('error', implode('<br>', $errors)); redirect('views/admin/compounds/create.php'); }

        $data['created_by'] = $_SESSION['user_id'];
        $data['structure_image'] = $this->handleStructureImageUpload();

        $id = $this->model->create($data);

        $refIds = array_filter(array_map('intval', $_POST['reference_ids'] ?? []));
        if ($refIds) $this->model->syncReferences($id, $refIds);

        $this->logModel->log($_SESSION['user_id'], 'compound_create', "Created: {$data['name']}", 'compound', $id, [], $data);
        setFlash('success', "Compound <strong>{$data['name']}</strong> created successfully.");
        redirect('views/admin/compounds/index.php');
    }

    public function update(int $id): void {
        requireAdmin();
        if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Invalid security token.');
            redirect("views/admin/compounds/edit.php?id={$id}");
        }

        $old = $this->model->findById($id);
        if (!$old) { setFlash('error', 'Compound not found.'); redirect('views/admin/compounds/index.php'); }

        [$data, $errors] = $this->validateCompoundInput($id);
        if ($errors) { setFlash('error', implode('<br>', $errors)); redirect("views/admin/compounds/edit.php?id={$id}"); }

        // Handle structure image
        $newImage = $this->handleStructureImageUpload();
        $data['structure_image'] = $newImage ?: $old['structure_image'];

        // Check if image removal was requested
        if (isset($_POST['remove_structure_image']) && $_POST['remove_structure_image'] === '1') {
            $this->deleteStructureImageFile($old['structure_image']);
            $data['structure_image'] = null;
        }

        $oldValues = array_intersect_key($old, array_flip(['name','formula','molecular_weight','description','organism_id']));
        $newValues = array_intersect_key($data, array_flip(['name','formula','molecular_weight','description','organism_id']));

        // Snapshot before update
        $this->versionModel->snapshot(
            $old,
            $_SESSION['user_id'],
            sanitize($_POST['change_summary'] ?? 'Updated via admin panel'),
            $oldValues,
            $newValues
        );

        $this->model->update($id, $data);

        $refIds = array_filter(array_map('intval', $_POST['reference_ids'] ?? []));
        $this->model->syncReferences($id, $refIds);

        $this->logModel->log($_SESSION['user_id'], 'compound_update', "Updated: {$data['name']}", 'compound', $id, $oldValues, $newValues);
        setFlash('success', "Compound updated. Version history saved.");
        redirect('views/admin/compounds/index.php');
    }

    public function rollback(int $id, int $version): void {
        requireAdmin();
        if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Invalid security token.');
            redirect("views/admin/compounds/history.php?id={$id}");
        }

        $current = $this->model->findById($id);
        if (!$current) { setFlash('error', 'Compound not found.'); redirect('views/admin/compounds/index.php'); }

        $snap = $this->versionModel->findVersion($id, $version);
        if (!$snap) { setFlash('error', 'Version not found.'); redirect("views/admin/compounds/history.php?id={$id}"); }

        $oldValues = array_intersect_key($current, array_flip(['name','formula','molecular_weight','description','organism_id']));

        // Snapshot current state before rollback
        $this->versionModel->snapshot(
            $current,
            $_SESSION['user_id'],
            "Rolled back to version {$version}",
            $oldValues,
            array_intersect_key($snap, array_flip(['name','formula','molecular_weight','description','organism_id']))
        );

        $this->model->rollback($id, $snap);
        $this->logModel->log($_SESSION['user_id'], 'compound_rollback', "Rolled back compound ID {$id} to version {$version}", 'compound', $id);
        setFlash('success', "Compound rolled back to version {$version}.");
        redirect("views/admin/compounds/view.php?id={$id}");
    }

    public function delete(int $id): void {
        requireAdmin();
        if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Invalid security token.');
            redirect('views/admin/compounds/index.php');
        }
        $compound = $this->model->findById($id);
        $this->model->delete($id);
        $this->logModel->log($_SESSION['user_id'], 'compound_delete', "Deleted: " . ($compound['name'] ?? "ID {$id}"), 'compound', $id);
        setFlash('success', 'Compound deleted.');
        redirect('views/admin/compounds/index.php');
    }

    private function validateCompoundInput(int $excludeId = 0): array {
        $errors = [];
        $name   = sanitize($_POST['name'] ?? '');
        $formula= sanitize($_POST['formula'] ?? '');
        $mw     = $_POST['molecular_weight'] ?? '';
        $desc   = sanitize($_POST['description'] ?? '');
        $orgId  = (int)($_POST['organism_id'] ?? 0);

        if (strlen($name) < 2)    $errors[] = 'Compound name is required (min 2 chars).';
        if (strlen($formula) < 1) $errors[] = 'Molecular formula is required.';
        if (!Compound::validateFormula($formula)) $errors[] = 'Invalid molecular formula format (e.g. C15H10O7).';
        if (!is_numeric($mw) || $mw <= 0) $errors[] = 'Valid molecular weight is required.';

        // Plausibility check: estimated vs entered MW (allow 20% tolerance)
        if (is_numeric($mw) && $mw > 0 && Compound::validateFormula($formula)) {
            $estimated = Compound::estimateMolecularWeight($formula);
            if ($estimated > 0) {
                $diff = abs((float)$mw - $estimated) / $estimated;
                if ($diff > 0.20) {
                    $errors[] = "Molecular weight {$mw} seems inconsistent with formula {$formula} (estimated ~{$estimated}). Please verify.";
                }
            }
        }

        // Duplicate detection
        if (strlen($name) >= 2 && strlen($formula) >= 1) {
            $dupes = (new Compound())->findDuplicates($name, $formula, $excludeId);
            if (!empty($dupes)) {
                $names = implode(', ', array_column($dupes, 'name'));
                $errors[] = "Possible duplicate detected: <strong>{$names}</strong>. Please verify before saving.";
            }
        }

        return [
            [
                'name'             => $name,
                'formula'          => $formula,
                'molecular_weight' => (float)$mw,
                'description'      => $desc,
                'organism_id'      => $orgId ?: null,
            ],
            $errors,
        ];
    }

    private function handleStructureImageUpload(): ?string {
        if (empty($_FILES['structure_image']) || $_FILES['structure_image']['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $file     = $_FILES['structure_image'];
        $maxSize  = 5 * 1024 * 1024; // 5MB
        $allowed  = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
        $extMap   = ['jpeg' => 'jpg', 'jpg' => 'jpg', 'png' => 'png', 'gif' => 'gif', 'webp' => 'webp', 'svg' => 'svg'];
        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($file['size'] > $maxSize) {
            setFlash('error', 'Structure image too large. Maximum size is 5MB.');
            return null;
        }
        if (!in_array($file['type'], $allowed)) {
            setFlash('error', 'Only JPG, PNG, GIF, WEBP, and SVG images are allowed for structure.');
            return null;
        }
        if (!array_key_exists($ext, $extMap)) {
            setFlash('error', 'Invalid image file extension.');
            return null;
        }

        // For non-SVG, verify it's actually an image
        if ($ext !== 'svg') {
            $imgInfo = @getimagesize($file['tmp_name']);
            if (!$imgInfo) {
                setFlash('error', 'Uploaded file is not a valid image.');
                return null;
            }
        }

        $uploadDir = __DIR__ . '/../assets/uploads/compounds/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filename    = 'compound_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extMap[$ext];
        $destination = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            setFlash('error', 'Failed to save structure image.');
            return null;
        }

        return $filename;
    }

    private function deleteStructureImageFile(?string $filename): void {
        if (!$filename) return;
        $path = __DIR__ . '/../assets/uploads/compounds/' . $filename;
        if (file_exists($path)) {
            @unlink($path);
        }
    }
}
