<?php
/**
 * Unit Tests — Compound Structure Image Feature
 * Run: php tests/CompoundStructureTest.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Compound.php';
require_once __DIR__ . '/../models/Organism.php';

class CompoundStructureTest {
    private Compound $model;
    private int $passed = 0;
    private int $failed = 0;

    public function __construct() {
        $this->model = new Compound();
    }

    public function run(): void {
        echo "╔══════════════════════════════════════════════════╗\n";
        echo "║   COMPOUND STRUCTURE IMAGE — UNIT TESTS         ║\n";
        echo "╚══════════════════════════════════════════════════╝\n\n";

        $this->section("DB SCHEMA");
        $this->testColumnExists();

        $this->section("MODEL: CREATE with structure_image");
        $this->testCreateWithImage();
        $this->testCreateWithoutImage();

        $this->section("MODEL: UPDATE structure_image");
        $this->testUpdateWithImage();
        $this->testUpdateStructureImageMethod();
        $this->testRemoveStructureImage();

        $this->section("MODEL: READ with structure_image");
        $this->testFindByIdHasStructureImage();
        $this->testFindByIdHasOrganismFields();

        $this->section("ORGANISM: getCompounds includes structure_image");
        $this->testOrganismCompoundsHaveImage();

        $this->section("VIEWS: Page Render Tests");
        $this->testCompoundViewRenders();
        $this->testCompoundCreateRenders();
        $this->testCompoundEditRenders();
        $this->testOrganismViewRenders();
        $this->testResearcherOrganismViewRenders();

        echo "\n╔══════════════════════════════════════════════════╗\n";
        $total = $this->passed + $this->failed;
        if ($this->failed === 0) {
            echo "║  ✅ ALL {$total} TESTS PASSED                       ║\n";
        } else {
            echo "║  ❌ {$this->passed} PASSED, {$this->failed} FAILED (total: {$total})         ║\n";
        }
        echo "╚══════════════════════════════════════════════════╝\n";
    }

    private function assert(bool $condition, string $message): void {
        if ($condition) { $this->passed++; echo "  ✅ {$message}\n"; }
        else { $this->failed++; echo "  ❌ {$message}\n"; }
    }

    private function section(string $title): void {
        echo "\n  ── {$title} ─────────────────────────────────\n";
    }

    private function testColumnExists(): void {
        $db = Database::getInstance()->getConnection();
        $cols = $db->query("DESCRIBE compounds")->fetchAll(PDO::FETCH_COLUMN);
        $this->assert(in_array('structure_image', $cols), "compounds table has 'structure_image' column");
    }

    private function testCreateWithImage(): void {
        $data = [
            'name' => 'TestCompound_' . time(),
            'formula' => 'C6H12O6',
            'molecular_weight' => 180.16,
            'description' => 'Test compound with structure image',
            'structure_image' => 'test_structure.png',
            'organism_id' => 1,
            'created_by' => 1,
        ];
        $id = $this->model->create($data);
        $compound = $this->model->findById($id);
        $this->assert($compound['structure_image'] === 'test_structure.png', "Create with structure_image saves correctly");
        $this->model->delete($id);
    }

    private function testCreateWithoutImage(): void {
        $data = [
            'name' => 'TestNoImage_' . time(),
            'formula' => 'C2H6O',
            'molecular_weight' => 46.07,
            'description' => 'Test without image',
            'structure_image' => null,
            'organism_id' => null,
            'created_by' => 1,
        ];
        $id = $this->model->create($data);
        $compound = $this->model->findById($id);
        $this->assert($compound['structure_image'] === null, "Create without structure_image saves as NULL");
        $this->model->delete($id);
    }

    private function testUpdateWithImage(): void {
        $data = [
            'name' => 'UpdateTest_' . time(),
            'formula' => 'C3H8O',
            'molecular_weight' => 60.10,
            'description' => 'Will be updated',
            'structure_image' => null,
            'organism_id' => null,
            'created_by' => 1,
        ];
        $id = $this->model->create($data);

        $data['structure_image'] = 'new_structure.svg';
        $this->model->update($id, $data);
        $compound = $this->model->findById($id);
        $this->assert($compound['structure_image'] === 'new_structure.svg', "Update sets structure_image correctly");
        $this->model->delete($id);
    }

    private function testUpdateStructureImageMethod(): void {
        $data = [
            'name' => 'ImgMethod_' . time(),
            'formula' => 'C4H10',
            'molecular_weight' => 58.12,
            'description' => 'Test updateStructureImage',
            'structure_image' => null,
            'organism_id' => null,
            'created_by' => 1,
        ];
        $id = $this->model->create($data);
        $result = $this->model->updateStructureImage($id, 'method_test.jpg');
        $compound = $this->model->findById($id);
        $this->assert($result === true, "updateStructureImage() returns true");
        $this->assert($compound['structure_image'] === 'method_test.jpg', "updateStructureImage() persists value");
        $this->model->delete($id);
    }

    private function testRemoveStructureImage(): void {
        $data = [
            'name' => 'RemoveImg_' . time(),
            'formula' => 'C5H12',
            'molecular_weight' => 72.15,
            'description' => 'Test remove image',
            'structure_image' => 'to_be_removed.png',
            'organism_id' => null,
            'created_by' => 1,
        ];
        $id = $this->model->create($data);
        $this->model->updateStructureImage($id, null);
        $compound = $this->model->findById($id);
        $this->assert($compound['structure_image'] === null, "updateStructureImage(null) removes image");
        $this->model->delete($id);
    }

    private function testFindByIdHasStructureImage(): void {
        $compound = $this->model->findById(1);
        $this->assert(array_key_exists('structure_image', $compound), "findById() result includes structure_image field");
    }

    private function testFindByIdHasOrganismFields(): void {
        $compound = $this->model->findById(1); // Quercetin linked to Camellia sinensis
        $this->assert(array_key_exists('organism_order', $compound), "findById() includes organism_order");
        $this->assert(array_key_exists('organism_family', $compound), "findById() includes organism_family");
        $this->assert(array_key_exists('organism_genus', $compound), "findById() includes organism_genus");
        $this->assert(array_key_exists('organism_image', $compound), "findById() includes organism_image");
    }

    private function testOrganismCompoundsHaveImage(): void {
        $orgModel = new Organism();
        $compounds = $orgModel->getCompounds(1);
        $this->assert(count($compounds) > 0, "Organism has linked compounds");
        $this->assert(array_key_exists('structure_image', $compounds[0]), "getCompounds() includes structure_image field");
        $this->assert(array_key_exists('formula', $compounds[0]), "getCompounds() includes formula field");
    }

    // Page render tests (via include)
    private function testCompoundViewRenders(): void {
        $this->renderPage(
            __DIR__ . '/../views/admin/compounds/view.php',
            'Compound view page renders',
            ['id' => '1']
        );
    }

    private function testCompoundCreateRenders(): void {
        $this->renderPage(
            __DIR__ . '/../views/admin/compounds/create.php',
            'Compound create page (with image upload field) renders'
        );
    }

    private function testCompoundEditRenders(): void {
        $this->renderPage(
            __DIR__ . '/../views/admin/compounds/edit.php',
            'Compound edit page (with image field) renders',
            ['id' => '1']
        );
    }

    private function testOrganismViewRenders(): void {
        $this->renderPage(
            __DIR__ . '/../views/admin/organisms/view.php',
            'Organism view (with compound structures) renders',
            ['id' => '1']
        );
    }

    private function testResearcherOrganismViewRenders(): void {
        // Switch to researcher
        $_SESSION['user'] = ['id' => 2, 'name' => 'Researcher', 'email' => 'r@test.com', 'role' => 'researcher'];
        $_SESSION['user_id'] = 2;
        $this->renderPage(
            __DIR__ . '/../views/researcher/organisms/view.php',
            'Researcher organism view (with compound structures) renders',
            ['id' => '1']
        );
        // Switch back to admin
        $_SESSION['user'] = ['id' => 1, 'name' => 'Admin', 'email' => 'admin@hazina-asili.com', 'role' => 'admin'];
        $_SESSION['user_id'] = 1;
    }

    private function renderPage(string $file, string $label, array $get = []): void {
        $_GET = $get;
        $_POST = [];
        ob_start();
        $error = null;
        try {
            set_error_handler(function($errno, $errstr, $errfile, $errline) use (&$error) {
                $error = "{$errstr} in {$errfile}:{$errline}";
                return true;
            });
            include $file;
            restore_error_handler();
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
        $output = ob_get_clean();

        $hasPhpError = (
            stripos($output, 'Fatal error') !== false ||
            stripos($output, 'Parse error') !== false
        );

        $pass = !$error && !$hasPhpError && strlen($output) > 500;
        if ($pass) { $this->passed++; echo "  ✅ {$label} (" . strlen($output) . " bytes)\n"; }
        else {
            $this->failed++;
            echo "  ❌ {$label}\n";
            if ($error) echo "     Error: {$error}\n";
        }
    }
}

// Setup session
if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user'] = ['id' => 1, 'name' => 'Admin', 'email' => 'admin@hazina-asili.com', 'role' => 'admin'];

$test = new CompoundStructureTest();
$test->run();
