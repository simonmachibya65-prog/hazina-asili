<?php
/**
 * Import Controller — CSV import with validation preview before committing.
 * Supports: compounds, organisms, references
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Compound.php';
require_once __DIR__ . '/../models/Organism.php';
require_once __DIR__ . '/../models/Reference.php';
require_once __DIR__ . '/../models/ActivityLog.php';

class ImportController {
    private ActivityLog $logModel;

    public function __construct() {
        $this->logModel = new ActivityLog();
    }

    /**
     * Preview CSV data — validate without importing.
     * Returns array of validated rows with errors/warnings.
     */
    public function preview(string $type): array {
        requireAdmin();

        if (empty($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'No file uploaded or upload error.'];
        }

        $file = $_FILES['csv_file'];
        if ($file['size'] > 10 * 1024 * 1024) {
            return ['success' => false, 'error' => 'File too large. Maximum 10MB.'];
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'csv') {
            return ['success' => false, 'error' => 'Only CSV files are accepted.'];
        }

        $handle = fopen($file['tmp_name'], 'r');
        if (!$handle) {
            return ['success' => false, 'error' => 'Failed to read file.'];
        }

        // Read header row
        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            return ['success' => false, 'error' => 'Empty CSV file.'];
        }
        $headers = array_map('trim', array_map('strtolower', $headers));

        // Validate headers based on type
        $requiredHeaders = $this->getRequiredHeaders($type);
        $missing = array_diff($requiredHeaders, $headers);
        if ($missing) {
            fclose($handle);
            return ['success' => false, 'error' => 'Missing required columns: ' . implode(', ', $missing)];
        }

        // Parse and validate each row
        $rows = [];
        $rowNum = 1;
        $errorCount = 0;
        $warningCount = 0;

        while (($data = fgetcsv($handle)) !== false && $rowNum <= 500) {
            $rowNum++;
            if (count($data) < count($headers)) {
                $data = array_pad($data, count($headers), '');
            }
            $row = array_combine($headers, array_slice($data, 0, count($headers)));
            $validation = $this->validateRow($type, $row, $rowNum);

            $rows[] = [
                'row_number' => $rowNum,
                'data'       => $row,
                'errors'     => $validation['errors'],
                'warnings'   => $validation['warnings'],
                'valid'      => empty($validation['errors']),
            ];

            if (!empty($validation['errors'])) $errorCount++;
            if (!empty($validation['warnings'])) $warningCount++;
        }

        fclose($handle);

        // Save preview data in session for commit step
        $_SESSION['import_preview'] = [
            'type'    => $type,
            'headers' => $headers,
            'rows'    => $rows,
            'file'    => $file['tmp_name'],
        ];

        return [
            'success'      => true,
            'total_rows'   => count($rows),
            'valid_rows'   => count($rows) - $errorCount,
            'error_count'  => $errorCount,
            'warning_count'=> $warningCount,
            'headers'      => $headers,
            'rows'         => $rows,
        ];
    }

    /**
     * Commit validated import — only imports valid rows.
     */
    public function commit(): array {
        requireAdmin();

        if (empty($_SESSION['import_preview'])) {
            return ['success' => false, 'error' => 'No pending import. Please upload and preview first.'];
        }

        $preview = $_SESSION['import_preview'];
        $type    = $preview['type'];
        $rows    = $preview['rows'];

        $imported = 0;
        $skipped  = 0;

        foreach ($rows as $row) {
            if (!$row['valid']) {
                $skipped++;
                continue;
            }

            try {
                $this->importRow($type, $row['data']);
                $imported++;
            } catch (Throwable $e) {
                $skipped++;
                error_log("Import error row {$row['row_number']}: " . $e->getMessage());
            }
        }

        $this->logModel->log(
            $_SESSION['user_id'],
            'bulk_import',
            "Imported {$imported} {$type}(s), skipped {$skipped}",
            $type,
            0
        );

        unset($_SESSION['import_preview']);

        return [
            'success'  => true,
            'imported' => $imported,
            'skipped'  => $skipped,
            'total'    => count($rows),
        ];
    }

    private function getRequiredHeaders(string $type): array {
        return match ($type) {
            'compounds'  => ['name', 'formula', 'molecular_weight'],
            'organisms'  => ['scientific_name', 'kingdom', 'phylum', 'class'],
            'references' => ['title', 'author', 'year', 'citation'],
            default      => [],
        };
    }

    private function validateRow(string $type, array $row, int $rowNum): array {
        $errors = [];
        $warnings = [];

        switch ($type) {
            case 'compounds':
                if (strlen(trim($row['name'] ?? '')) < 2) $errors[] = 'Name too short.';
                if (strlen(trim($row['formula'] ?? '')) < 1) $errors[] = 'Formula is required.';
                if (!empty($row['formula']) && !Compound::validateFormula(trim($row['formula']))) {
                    $errors[] = 'Invalid formula format.';
                }
                $mw = $row['molecular_weight'] ?? '';
                if (!is_numeric($mw) || (float)$mw <= 0) $errors[] = 'Invalid molecular weight.';

                // Check duplicates
                if (empty($errors)) {
                    $model = new Compound();
                    $dupes = $model->findDuplicates(trim($row['name']), trim($row['formula']));
                    if ($dupes) $warnings[] = 'Possible duplicate: ' . $dupes[0]['name'];
                }
                break;

            case 'organisms':
                if (strlen(trim($row['scientific_name'] ?? '')) < 2) $errors[] = 'Scientific name required.';
                if (strlen(trim($row['kingdom'] ?? '')) < 2) $errors[] = 'Kingdom required.';
                if (strlen(trim($row['phylum'] ?? '')) < 2) $errors[] = 'Phylum required.';
                if (strlen(trim($row['class'] ?? '')) < 2) $errors[] = 'Class required.';

                if (empty($errors)) {
                    $model = new Organism();
                    $existing = $model->findByScientificName(trim($row['scientific_name']));
                    if ($existing) $warnings[] = 'Organism already exists, will be skipped.';
                }
                break;

            case 'references':
                if (strlen(trim($row['title'] ?? '')) < 2) $errors[] = 'Title required.';
                if (strlen(trim($row['author'] ?? '')) < 2) $errors[] = 'Author required.';
                $year = (int)($row['year'] ?? 0);
                if ($year < 1900 || $year > (int)date('Y') + 1) $errors[] = 'Invalid year.';
                if (strlen(trim($row['citation'] ?? '')) < 5) $errors[] = 'Citation required.';
                break;
        }

        return ['errors' => $errors, 'warnings' => $warnings];
    }

    private function importRow(string $type, array $row): void {
        switch ($type) {
            case 'compounds':
                $model = new Compound();
                $model->create([
                    'name'             => trim($row['name']),
                    'formula'          => trim($row['formula']),
                    'molecular_weight' => (float)$row['molecular_weight'],
                    'description'      => trim($row['description'] ?? ''),
                    'organism_id'      => null,
                    'created_by'       => $_SESSION['user_id'],
                ]);
                break;

            case 'organisms':
                $model = new Organism();
                // Skip if already exists
                if ($model->findByScientificName(trim($row['scientific_name']))) return;
                $model->create([
                    'scientific_name' => trim($row['scientific_name']),
                    'kingdom'         => trim($row['kingdom']),
                    'phylum'          => trim($row['phylum']),
                    'class'           => trim($row['class']),
                    'order_name'      => trim($row['order_name'] ?? '') ?: null,
                    'family'          => trim($row['family'] ?? '') ?: null,
                    'genus'           => trim($row['genus'] ?? '') ?: null,
                    'species'         => trim($row['species'] ?? '') ?: null,
                    'cell_type'       => trim($row['cell_type'] ?? '') ?: null,
                    'habitat'         => trim($row['habitat'] ?? '') ?: null,
                    'description'     => trim($row['description'] ?? '') ?: null,
                    'structure_image' => null,
                ]);
                break;

            case 'references':
                $model = new Reference();
                $model->create([
                    'title'    => trim($row['title']),
                    'author'   => trim($row['author']),
                    'year'     => (int)$row['year'],
                    'citation' => trim($row['citation']),
                ]);
                break;
        }
    }
}
