<?php
/**
 * Organism Controller — handles validation, image upload, and CRUD
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Organism.php';
require_once __DIR__ . '/../models/ActivityLog.php';

class OrganismController {
    private Organism    $model;
    private ActivityLog $logModel;

    public function __construct() {
        $this->model    = new Organism();
        $this->logModel = new ActivityLog();
    }

    public function store(): void {
        requireAdmin();
        if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Invalid security token.');
            redirect('views/admin/organisms/create.php');
        }

        [$data, $errors] = $this->validateInput();
        if ($errors) {
            setFlash('error', implode('<br>', $errors));
            redirect('views/admin/organisms/create.php');
        }

        // Handle image upload
        $data['structure_image'] = $this->handleImageUpload();

        $id = $this->model->create($data);

        $this->logModel->log(
            $_SESSION['user_id'],
            'organism_create',
            "Created organism: {$data['scientific_name']}",
            'organism',
            $id,
            [],
            $data
        );

        setFlash('success', "Organism <strong>{$data['scientific_name']}</strong> created successfully.");
        redirect('views/admin/organisms/index.php');
    }

    public function update(int $id): void {
        requireAdmin();
        if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Invalid security token.');
            redirect("views/admin/organisms/edit.php?id={$id}");
        }

        $old = $this->model->findById($id);
        if (!$old) {
            setFlash('error', 'Organism not found.');
            redirect('views/admin/organisms/index.php');
        }

        [$data, $errors] = $this->validateInput($id);
        if ($errors) {
            setFlash('error', implode('<br>', $errors));
            redirect("views/admin/organisms/edit.php?id={$id}");
        }

        // Handle image upload (keep old if no new upload)
        $newImage = $this->handleImageUpload();
        $data['structure_image'] = $newImage ?: $old['structure_image'];

        // Check if image removal was requested
        if (isset($_POST['remove_image']) && $_POST['remove_image'] === '1') {
            $this->deleteImageFile($old['structure_image']);
            $data['structure_image'] = null;
        }

        // Capture old values for audit
        $oldValues = [
            'kingdom' => $old['kingdom'], 'phylum' => $old['phylum'], 'class' => $old['class'],
            'order_name' => $old['order_name'], 'family' => $old['family'],
            'genus' => $old['genus'], 'species' => $old['species'],
            'scientific_name' => $old['scientific_name'], 'cell_type' => $old['cell_type'],
            'habitat' => $old['habitat'], 'description' => $old['description'],
        ];

        $this->model->update($id, $data);

        $newValues = array_intersect_key($data, $oldValues);
        $this->logModel->log(
            $_SESSION['user_id'],
            'organism_update',
            "Updated organism: {$data['scientific_name']}",
            'organism',
            $id,
            $oldValues,
            $newValues
        );

        setFlash('success', 'Organism updated successfully.');
        redirect('views/admin/organisms/index.php');
    }

    public function delete(int $id): void {
        requireAdmin();
        if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Invalid security token.');
            redirect('views/admin/organisms/index.php');
        }

        $org = $this->model->findById($id);
        if (!$org) {
            setFlash('error', 'Organism not found.');
            redirect('views/admin/organisms/index.php');
        }

        // Delete image file
        $this->deleteImageFile($org['structure_image']);

        $this->model->delete($id);

        $this->logModel->log(
            $_SESSION['user_id'],
            'organism_delete',
            "Deleted organism: {$org['scientific_name']}",
            'organism',
            $id,
            $org,
            []
        );

        setFlash('success', 'Organism deleted.');
        redirect('views/admin/organisms/index.php');
    }

    private function validateInput(int $excludeId = 0): array {
        $errors = [];

        $data = [
            'scientific_name' => sanitize($_POST['scientific_name'] ?? ''),
            'kingdom'         => sanitize($_POST['kingdom'] ?? ''),
            'phylum'          => sanitize($_POST['phylum'] ?? ''),
            'class'           => sanitize($_POST['class'] ?? ''),
            'order_name'      => sanitize($_POST['order_name'] ?? ''),
            'family'          => sanitize($_POST['family'] ?? ''),
            'genus'           => sanitize($_POST['genus'] ?? ''),
            'species'         => sanitize($_POST['species'] ?? ''),
            'cell_type'       => sanitize($_POST['cell_type'] ?? ''),
            'habitat'         => sanitize($_POST['habitat'] ?? ''),
            'description'     => sanitize($_POST['description'] ?? ''),
        ];

        // Required fields
        if (strlen($data['scientific_name']) < 2) $errors[] = 'Scientific name is required (min 2 characters).';
        if (strlen($data['kingdom']) < 2)         $errors[] = 'Kingdom is required.';
        if (strlen($data['phylum']) < 2)          $errors[] = 'Phylum is required.';
        if (strlen($data['class']) < 2)           $errors[] = 'Class is required.';

        // Validate cell_type enum
        if ($data['cell_type'] && !in_array($data['cell_type'], ['eukaryotic', 'prokaryotic'])) {
            $errors[] = 'Cell type must be eukaryotic or prokaryotic.';
            $data['cell_type'] = null;
        }

        // Empty strings to null for optional fields
        foreach (['order_name','family','genus','species','habitat','description'] as $field) {
            if ($data[$field] === '') $data[$field] = null;
        }
        if ($data['cell_type'] === '') $data['cell_type'] = null;

        // Duplicate check
        $duplicate = $this->model->findByScientificName($data['scientific_name'], $excludeId);
        if ($duplicate) {
            $errors[] = "An organism with the name \"{$data['scientific_name']}\" already exists.";
        }

        return [$data, $errors];
    }

    private function handleImageUpload(): ?string {
        if (empty($_FILES['structure_image']) || $_FILES['structure_image']['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $file     = $_FILES['structure_image'];
        $maxSize  = 5 * 1024 * 1024; // 5MB
        $allowed  = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $extMap   = ['jpeg' => 'jpg', 'jpg' => 'jpg', 'png' => 'png', 'gif' => 'gif', 'webp' => 'webp'];
        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($file['size'] > $maxSize) {
            setFlash('error', 'Image too large. Maximum size is 5MB.');
            return null;
        }
        if (!in_array($file['type'], $allowed)) {
            setFlash('error', 'Only JPG, PNG, GIF, and WEBP images are allowed.');
            return null;
        }
        if (!array_key_exists($ext, $extMap)) {
            setFlash('error', 'Invalid image file extension.');
            return null;
        }
        $imgInfo = @getimagesize($file['tmp_name']);
        if (!$imgInfo) {
            setFlash('error', 'Uploaded file is not a valid image.');
            return null;
        }

        $uploadDir = __DIR__ . '/../assets/uploads/organisms/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filename    = 'organism_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extMap[$ext];
        $destination = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            setFlash('error', 'Failed to save image. Please try again.');
            return null;
        }

        return $filename;
    }

    private function deleteImageFile(?string $filename): void {
        if (!$filename) return;
        $path = __DIR__ . '/../assets/uploads/organisms/' . $filename;
        if (file_exists($path)) {
            @unlink($path);
        }
    }
}
